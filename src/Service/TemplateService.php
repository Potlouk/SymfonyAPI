<?php

namespace App\Service;

use App\Constraint\RequestConstraints;
use App\Entity\Template;
use App\Enum\OperationTypes;
use App\Exception\LogicException;
use App\Exception\ResourceNotFoundException;
use App\Repository\TemplateRepository;
use App\Trait\ServiceHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class TemplateService {
   use ServiceHelper;

    public function __construct(
        private readonly TemplateRepository     $repository,
        private readonly EntityManagerInterface $entityManager,
        private readonly CULogService           $logService,
        private readonly Security               $security,
      ) {}

   /**
    * Retrieves a template by UUID
    * 
    * @param string $uuid Template unique identifier
    * @return Template The requested template entity
    * @throws ResourceNotFoundException When template cannot be found
    */
   public function get(string $uuid): Template {
        return $this->repository->findByUuid($uuid);
   }
   
   /**
    * Creates a new template
    * 
    * Validates the input data and creates a new template with the specified
    * data, information, and name.
    * 
    * @param array<string, mixed> $data Template creation parameters
    * @return Template The newly created template entity
    */
    public function create(array $data): Template {
         $this->validateRequestData($data, RequestConstraints::templateConstraintCreate());

         $template = (new Template())
         ->setData($data['data'])
         ->setInfo($data['info'])
         ->setName($data['name']);

         $this->entityManager->persist($template);
         $this->entityManager->flush();

        $this->logService->create(
            OperationTypes::CREATED,
            $this->security->getUser()?->getUserIdentifier(),
            $template
        );

         return $template;
     }
      
     /**
     * Updates specific fields of a template
     * 
     * Validates the input data and updates the template's data, information,
     * or name based on the provided parameters.
     * 
     * @param string $uuid Template unique identifier
     * @param array<string, mixed> $data Fields to update
     * @return Template The updated template entity
     */
     public function patch(string $uuid, array $data): template {
         $this->validateRequestData($data, RequestConstraints::templateConstraintPatch());
   
         $template = $this->repository->findByUuid($uuid);

         $template->setData($data['data'] ?? $template->getData());
         $template->setInfo($data['info'] ?? $template->getInfo());

         if (array_key_exists('name', $data))
         $template->setName($data['name']);

         $this->entityManager->flush();

         $this->logService->create(
             OperationTypes::EDITED,
             $this->security->getUser()?->getUserIdentifier(),
             $template
         );

         return $template;
      }
 
      /**
       * Permanently deletes a template
       * 
       * Removes the template from the database.
       * 
       * @param string $uuid Template unique identifier
       * @return void
       */
      public function delete(string $uuid): void {
         $template = $this->repository->findByUuid($uuid);

         if ($template->getId() === 1)
             throw new LogicException('Deleting the default template is not allowed as it may cause system issues');

         $this->entityManager->remove($template);
         $this->entityManager->flush();
      }

    /**
     * Retrieves all templates
     * 
     * @return array List of all template entities
     */
    public function paginate(int $page, int $limit, string $search): array {
      return $this->repository->paginate($page, $limit, $search);
   }

}