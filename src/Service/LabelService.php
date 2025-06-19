<?php

namespace App\Service;

use App\Entity\Label;
use App\Repository\LabelRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class LabelService {

    public function __construct(
        private EntityManagerInterface $entityManager,
        private LabelRepository        $labelRepository,
    ) {}

   /**
    * Creates a label with the provided data and persists it to the database.
    * 
    * @param array<string, mixed> $data Label data
    * @return Label The newly created label entity
    */
    public function create(array $data): Label {
       $label = (new Label())->setData($data["data"]);
       $this->entityManager->persist($label);
       $this->entityManager->flush();
       return $label;
    }

    /**
     * Updates a label
     * 
     * Updates the label data.
     * 
     * @param int $id Label ID
     * @param array<string, mixed> $data New label data
     * @return Label The updated label entity
     */
    public function put(int $id, array $data): Label {
        $label = $this->labelRepository->findById($id);
        $label->setData($data["data"]);
        $this->entityManager->flush();
        return $label;
     }

     /**
      * Permanently deletes a label
      * 
      * Removes the label from the database.
      * 
      * @param int $id Label ID
      * @return void
      */
     public function delete(int $id): void {
        $label = $this->labelRepository->findById($id);
        $this->entityManager->remove($label);
        $this->entityManager->flush();
     }

   /**
    * Retrieves all labels
    * 
    * @return array<int, label> List of all label entities
    */
     public function all(): array {
        return $this->labelRepository->findAll();
     }
}