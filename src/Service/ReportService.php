<?php

namespace App\Service;

use App\Constraint\RequestConstraints;
use App\Entity\Report;
use App\Enum\OperationTypes;
use App\Factory\ReportFactory;
use App\Factory\TokenFactory;
use App\Repository\DocumentRepository;
use App\Repository\LabelRepository;
use App\Repository\ReportRepository;
use App\Repository\UserRepository;
use App\Trait\ServiceHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class ReportService {
    use ServiceHelper;
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CULogService           $logService,
        private readonly ReportRepository       $reportRepository,
        private readonly DocumentRepository     $documentRepository,
        private readonly LabelRepository        $labelRepository,
        private readonly UserRepository         $userRepository,
        private readonly Security               $security,
        private readonly ImageService           $imageService,
    ) {}

    /**
     * Paginates reports based on filters and user access permissions
     *
     * Retrieves a paginated list of reports that the current user has access to.
     * Filters reports based on user roles and assignments.
     *
     * @param int $limit Maximum number of items per page
     * @param int $page The page number to retrieve
     * @param array<string, mixed> $data Additional filter parameters
     * @return array<string, mixed> Paginated results with metadata
     */
    public function paginate(int $limit, int $page, array $data): array {
        $userEmail =  $this->security->getUser()?->getUserIdentifier();
        $user =  $this->userRepository->findByEmail($userEmail);

        if (!in_array('ROLE_ALL_REPORT', $user->getRoles(), true))
            $assignedReports = $user->getAssignedReports()->map(fn($report) => $report->getUuId())->toArray();
        else $assignedReports = ['*'];
        
        return $this->reportRepository->paginate($data['filters'] ?? [], $limit, $page, $assignedReports);
    }

    /**
     * Creates a new report based on a document
     *
     * Validates the input data, creates a report associated with the specified document,
     * sets a token and property, and logs the creation.
     *
     * @param string $uuid Document unique identifier
     * @param array<string, mixed> $data Report creation parameters
     * @return Report The newly created report entity
     */
    public function create(string $uuid, array $data): Report {
        $this->validateRequestData($data, RequestConstraints::reportConstraintCreate());
       
        $document = $this->documentRepository->findByUuid($uuid);

        $report = ReportFactory::build($data)
        ->setToken( 
            TokenFactory::build($data['permissions'])
        );

        $report->setProperty($document->getProperty());
        $this->entityManager->persist($report);

        $document->addReport($report);
        $this->entityManager->flush();

        $this->logService->create(
            OperationTypes::CREATED,
            $this->security->getUser()?->getUserIdentifier(),
            $report
        );

        return $report;
    }

    /**
     * Permanently deletes a report
     * 
     * Removes the report from the database.
     * 
     * @param string $uuid Report unique identifier
     * @return void
     */
    public function delete(string $uuid): void {
        $report = $this->get($uuid);

        $this->entityManager->remove($report);
        $this->entityManager->flush();

        $this->imageService->deleteFolder($uuid);
    }

    /**
     * Updates specific fields of a report
     * 
     * Validates the input data and updates the report's data or information
     * based on the provided parameters. Logs the edit operation.
     * 
     * @param string $uuid Report unique identifier
     * @param array<string, mixed> $data Fields to update
     * @return Report The updated report entity
     */
    public function patch(string $uuid, array $data): Report {
        $this->validateRequestData($data, RequestConstraints::reportConstraintPatch());

        $report = $this->get($uuid);

        if (array_key_exists('data', $data))
        $report->setData($data['data']);
        if (array_key_exists('info', $data))
        $report->setInfo($data['info']); 
    
        $this->entityManager->flush();

        $this->logService->create(
            OperationTypes::EDITED,
            $this->security->getUser()?->getUserIdentifier() ??  $report->getToken()?->getEmail(),
            $report
        );

        return $report; 
    }

    /**
     * Retrieves a report by UUID
     * 
     * @param string $uuid Report unique identifier
     * @return Report The requested report entity
     */
    public function get(string $uuid): Report {
        return $this->reportRepository->findByUuid($uuid);
    }

   /**
    * Associates labels with a report
    * 
    * Clears existing labels and assigns new ones based on provided IDs.
    * 
    * @param string $uuid Report unique identifier
    * @param array<string, mixed> $data Label assignment data containing labelIds
    * @return void
    */
    public function setLabels(string $uuid, array $data): void {
        $report = $this->reportRepository->findByUuid($uuid);
        $report->clearLabels();
        
        if (array_key_exists('labelIds', $data) && !empty($data['labelIds'])){
            $labels = $this->labelRepository->findBy(['id' => $data['labelIds']]);
            foreach ($labels as $label)
            $report->addLabel($label);
        }

        $this->entityManager->flush();
    }

    /**
     * Assigns users to a report
     * 
     * Clears existing user assignments and assigns new users based on provided IDs.
     * 
     * @param string $uuid Report unique identifier
     * @param array<string, mixed> $data User assignment data containing userIds
     * @return void
     */
    public function assignUsers(string $uuid, array $data) : void {
        $report = $this->reportRepository->findByUuid($uuid);
        $report->clearAssignedUsers();
        
        if (array_key_exists('userIds', $data) && !empty($data['userIds'])){
            $users = $this->userRepository->findBy(['id' => $data['userIds']]);
            foreach ($users as $user){
                $report->addAssignedUser($user);
            }
        }

        $this->entityManager->flush();
    }
}