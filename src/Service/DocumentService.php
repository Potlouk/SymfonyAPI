<?php

namespace App\Service;

use App\Constraint\RequestConstraints;
use App\Enum\DocumentStatusTypes;
use App\Enum\MailTypes;
use App\Enum\OperationTypes;
use App\Exception\ResourceNotFoundException;
use App\Factory\ReportFactory;
use App\Factory\TokenFactory;
use App\Repository\DocumentRepository;
use App\Repository\LabelRepository;
use App\Trait\ServiceHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\Document;
use App\Exception\LogicException;
use App\Factory\DocumentFactory;
use App\Repository\PropertyRepository;
use App\Repository\ReportRepository;
use App\Repository\TemplateRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class DocumentService {
    use ServiceHelper;
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DocumentRepository     $documentRepository,
        private readonly PropertyRepository     $propertyRepository,
        private readonly LabelRepository        $labelRepository,
        private readonly TemplateRepository     $templateRepository,
        private readonly CULogService           $logService,
        private readonly EmailService           $emailService,
        private readonly ReportRepository       $reportRepository,
        private readonly TokenService           $tokenService,
        private readonly SettingService         $settingService,
        private readonly Security               $security,
        private readonly UserRepository         $userRepository,
        private readonly ImageService           $imageService,
    ) {}

    /**
     * Paginates documents based on filters and user access permissions
     *
     * Retrieves a paginated list of documents that the current user has access to.
     * Filters documents based on user roles and assignments.
     *
     * @param int $page The page number to retrieve
     * @param int $limit Maximum number of items per page
     * @param array<string, mixed> $data Additional filter parameters
     * @return array<string, mixed> Paginated results with metadata
     */
    public function paginate(int $page, int $limit, array $data): array {
        $userEmail =  $this->security->getUser()?->getUserIdentifier();
        $user =  $this->userRepository->findByEmail($userEmail);

        if (!in_array('ROLE_ALL_DOCUMENT', $user->getRoles(), true))
        $assignedDocuments = $user->getAssignedDocuments()->map(fn($document) => $document->getUuId())->toArray();
        else $assignedDocuments = ['*'];

        return $this->documentRepository->paginate($data['filters'] ?? [], $page, $limit, $assignedDocuments);
    }
    
     /**
     * Creates a new document bound to a property
     * 
     * Creates a document based on a template and binds it to a specified property.
     * Validates input data and persists the new document to the database.
     * 
     * @param array<string, mixed> $data Document creation parameters
     * @return Document The newly created document entity
     */
    public function create(array $data): Document {
        $this->validateRequestData($data, RequestConstraints::documentConstraintCreate());

        $property = $this->propertyRepository->findById($data['propertyId']);
        $template = $this->templateRepository->findByUuid($data['templateUuid']);

        $document = DocumentFactory::build(
            $data,
            $property,
            $template, 
            DocumentStatusTypes::DEFAULT
        );
      
        $this->entityManager->persist($document);
        $this->entityManager->flush();

        $this->logService->create(
            OperationTypes::CREATED,
            $this->security->getUser()?->getUserIdentifier(),
            $document
        );
    
        return $document;
    }

    /**
     * Shares a document with a receiver via token
     *
     * Creates a token for document access and changes document status to in-progress.
     * Sends an email notification to the receiver with access information.
     *
     * @param string $uuid Document unique identifier
     * @param array<string, mixed> $data Share settings including permissions and receiver details
     * @return Document The updated document with sharing information
     */
    public function share(string $uuid, array $data): Document {
        $this->validateRequestData($data, RequestConstraints::documentConstraintShare());

        $document = $this->get($uuid);
        $document->setStatus(
            DocumentFactory::transformStatus(
                DocumentStatusTypes::IN_PROGRESS
            )
        );

        $document->setToken(
            TokenFactory::build(
                $data['permissions'], 
                $data['expiryDate'],
                $data['receiver'],
                $data['validDate'],
            )
        );

        $this->logService->create(OperationTypes::SHARED, $this->security->getUser()?->getUserIdentifier(), $document);
        $this->entityManager->flush();

        $this->emailService->send($data['receiver']['email'], MailTypes::DOCUMENT_SUBMISSION_CREATED, $document);
        
        return $document;
    }

    /**
     * Revokes sharing access for a document
     *
     * Deletes the current token and replaces it with an inactive token.
     * Clears document from cache to reflect the updated access state.
     *
     * @param string $uuid Document unique identifier
     * @return Document The updated document with sharing revoked
     */
    public function unshare(string $uuid): Document {
        $document = $this->get($uuid);
        $token = $document->getToken();

        if (null === $token)
            throw new ResourceNotFoundException('Token');

        $inactiveToken = TokenFactory::build()->setActive(false);
        
        $document->setToken($inactiveToken);
        
        $this->entityManager->persist($inactiveToken);

        $this->entityManager->remove($token);
        $this->entityManager->flush();

        return $document;
    }

    /**
     * Submits a document and creates a corresponding report
     * 
     * Changes document status to submitted, creates a report from the document,
     * and sends email notifications to relevant parties.
     * 
     * @param string $uuid Document unique identifier
     * @param array<string, mixed> $data Additional submission data
     * @throws LogicException When document is already submitted
     * @return void
     */
    public function submit(string $uuid, array $data): void {
        $document = $this->get($uuid);

        if (DocumentFactory::transformStatus(
            DocumentStatusTypes::SUBMITTED) === $document->getStatus())
        throw new LogicException('Document is already submitted.');

        $document = $this->patch($uuid, $data);
        $report = ReportFactory::buildFromDocument($document, $this->settingService->get());
        $report->setToken(
            TokenFactory::build([ 
                $document->getToken()?->getPermissions()?->getValue(),
                null,
                $document->getToken()?->getEmail()
            ])
        );

        $report->setProperty($document->getProperty());
        $report->setType($document->getType());

        $this->entityManager->persist($report);
        $document->addReport($report);

        $document->setStatus(
            DocumentFactory::transformStatus(
                DocumentStatusTypes::SUBMITTED
            )
        );

        $this->entityManager->flush();
        $this->logService->create(OperationTypes::CREATED,  $document->getToken()?->getEmail(), $report);

        $adminEmail = $this->logService->getMailFromAction(OperationTypes::SHARED, $document);
        $tenantEmail = $document->getToken()?->getEmail();

        $this->emailService->send($adminEmail,  MailTypes::USER_SUBMITTED_NOTIFICATION, $report);
        $this->emailService->send($tenantEmail, MailTypes::USER_SUCCESSFUL_SUBMIT, $report);
    }

    /**
     * Reopens a submitted document for further edits
     * 
     * Changes document status back to in-progress, removes associated report,
     * and sends notification about reopening to involved parties.
     * 
     * @param string $uuid Document unique identifier
     * @throws LogicException When document is not in submitted state
     * @throws ResourceNotFoundException When report cannot be found
     * @return void
     */
    public function reopen(string $uuid): void {
        $document = $this->get($uuid);
        if (DocumentFactory::transformStatus(
            DocumentStatusTypes::SUBMITTED) !== $document->getStatus())
        throw new LogicException("Unsubmitted document can not be reopened.");

        $document->setStatus(
            DocumentFactory::transformStatus(
                DocumentStatusTypes::IN_PROGRESS
            )
        );

        $report = $this->reportRepository->findByDocumentId($document->getId());

        $document->removeReport($report); 
        $report->setDocument($document);

        $this->entityManager->remove($report);
        $this->entityManager->flush();
  
        $this->emailService->send($document->getToken()?->getEmail(), MailTypes::DOCUMENT_SUBMISSION_REOPENED, $document);
        $this->logService->create(
            OperationTypes::SHARED, 
            $this->security->getUser()?->getUserIdentifier(),
            $document
        );
    }
    
    /**
     * Permanently deletes a document
     * 
     * Removes the document from the database and clears it from cache.
     * 
     * @param string $uuid Document unique identifier
     * @return void
     */
    public function delete(string $uuid): void {
        $document = $this->get($uuid);
        $this->entityManager->remove($document);
        $this->entityManager->flush();
        $this->imageService->deleteFolder($uuid);
    }
    /**
     * Updates specific fields of a document
     * 
     * Modifies document data and information fields if provided in the input.
     * Logs the edit operation and clears document from cache.
     * 
     * @param string $uuid Document unique identifier
     * @param array<string, mixed> $data Fields to update
     * @return Document The updated document
     */
    public function patch(string $uuid, array $data): Document {
        $document = $this->get($uuid);

        $document->setData($data['data'] ?? $document->getData());
        $document->setInfo($data['info'] ?? $document->getInfo());

        $this->entityManager->flush();
        $this->logService->create(
            OperationTypes::EDITED,
            $this->security->getUser()?->getUserIdentifier() ?? $document->getToken()?->getEmail(),
            $document
        );

        return $document;  
    }

    /**
     * Retrieves a document by its UUID
     * 
     * @param string $uuid Document unique identifier
     * @return Document The requested document entity
     * @throws ResourceNotFoundException When document cannot be found
     */
    public function get(string $uuid): Document {
         return $this->documentRepository->findByUuid($uuid);
    }

     /**
     * Associates labels with a document
     * 
     * Clears existing labels and assigns new ones based on provided IDs.
     * Updates cache for affected entities.
     * 
     * @param string $uuid Document unique identifier
     * @param array<string, mixed> $data Label assignment data containing labelIds
     * @return void
     */
    public function setLabels(string $uuid, array $data): void {
        $document = $this->documentRepository->findByUuid($uuid);
        $document->clearLabels();
        
        if (array_key_exists('labelIds', $data) && !empty($data['labelIds'])){
            $labels = $this->labelRepository->findBy(['id' => $data['labelIds']]);
            foreach ($labels as $label){
                $document->addLabel($label);
            }
        }

        $this->entityManager->persist($document);
        $this->entityManager->flush();
    }

    /**
     * Assigns users to a document
     * 
     * Clears existing user assignments and assigns new users based on provided IDs.
     * Updates cache for affected entities.
     * 
     * @param string $uuid Document unique identifier
     * @param array<string, mixed> $data User assignment data containing userIds
     * @return void
     */
    public function assignUsers(string $uuid, array $data) : void {
        $document = $this->get($uuid);
        $document->clearAssignedUsers();
        
        if (array_key_exists('userIds', $data) && !empty($data['userIds'])){
            $users = $this->userRepository->findBy(['id' => $data['userIds']]);
            foreach ($users as $user){
                $document->addAssignedUser($user);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * Sets images for a document at specified positions
     * 
     * Validates the input data and calls appendImages to process the uploaded files.
     * Clears document from cache after updating.
     * 
     * @param string $uuid Document unique identifier
     * @param array<string, mixed> $data Position data for image placement
     * @param UploadedFile[] $images Array of uploaded image files
     * @return void
     */
    public function setImages(string $uuid, array $data, array $images): void {
        $this->validateRequestData($data, RequestConstraints::documentConstraintImages());
        $document = $this->get($uuid);

        $this->appendImages($document, json_decode($data['positions'], true), $images);

        $this->entityManager->flush();
    }

    /**
     * Changes Document's property
     *
     *
     * @param string $uuid The document to which property will be changed
     * @param array $data
     * @return void
     */
    public function changeProperty(string $uuid, array $data): void {
        $this->validateRequestData($data, RequestConstraints::documentConstraintChangeProperty());

        $document = $this->documentRepository->findByUuid($uuid);
        $reports = $document->getReports();

        $newProperty = $this->propertyRepository->findById($data['propertyId']);
        $oldProperty = $document->getProperty();

        $oldProperty?->removeDocument($document);

        foreach ($reports as $report){
            $oldProperty?->removeReport($report);
            $newProperty->addReport($report);
        }

        $newProperty->addDocument($document);

        $this->entityManager->flush();
    }

    /**
     * Appends uploaded images to a document
     * 
     * This method processes uploaded images and attaches them to specific positions within a document's data structure.
     * It updates the document's upload status and marks files as uploaded once processed.
     * 
     * @param Document $document The document to which images will be appended
     * @param array $pos Positions for images for integration
     * @param UploadedFile[] $images Array of uploaded image files
     * @return void
     */
    public function appendImages(Document $document, array $pos, array $images): void {
        if (empty($pos) && !empty($images))
            throw new LogicException('Cannot save images without positions');

        $data = $document->getData();
        $info = $document->getInfo();
        $i = 0;
    
        foreach ($images as $image) {

            if (!isset($data[$pos[$i]['area']]['items'][$pos[$i]['item']]['files'][$pos[$i]['file']]))
            continue;

            $file = &$data[$pos[$i]['area']]['items'][$pos[$i]['item']]['files'][$pos[$i]['file']];

            if ($pos[$i]['isGroup'])
            $file = &$file['group'][$pos[$i]['groupIndex']];

            if (!isset($file['isUploaded']))
            $file['isUploaded'] = false;

            $file['src'] = $this->imageService->save([$image], $document->getUuid())[0];
            $file['isUploaded'] = true;

            $uploadProgress = &$info['uploadImageStatus'];
            $uploadProgress['size'] -= 1;
            
            if ($uploadProgress['size'] <= 0 || null === $uploadProgress['size'])
            $uploadProgress['status'] = "UPLOADED";
            else $uploadProgress['status'] = "NOT_UPLOADED";
            
            $i++;
        }

        $document->setData($data);
        $document->setInfo($info);
    }


    public function crateFromReport(string $uuid, array $data): Document {
        $this->validateRequestData($data, RequestConstraints::documentConstraintCreateFromReport());

        $report = $this->reportRepository->findByUuid($uuid);

        $document = DocumentFactory::buildFromReport(
            $data,
            $report->getDocument()?->getInfo(),
            $report->getProperty(),
            $report->getDocument()?->getTemplate(),
        );

        $document->setMadeFromReport($report);

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        $this->logService->create(
            OperationTypes::CREATED,
            $this->security->getUser()?->getUserIdentifier(),
            $document
        );
    
        return $document;
    }

}