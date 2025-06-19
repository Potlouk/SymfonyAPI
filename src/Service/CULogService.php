<?php

namespace App\Service;

use App\Entity\CULog;
use App\Enum\OperationTypes;
use App\Exception\ResourceNotFoundException;
use App\Factory\CULogFactory;
use App\Interface\LogInterface;
use App\Repository\CULogRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class CULogService implements LogInterface {

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CULogRepository        $logRepository,
    ) {}

    /**
     * Creates a new log entry
     * 
     * Creates a log record for an operation performed by a user on an entity.
     * 
     * @param OperationTypes $type The type of operation performed
     * @param string $email The email of the user who performed the operation
     * @param object $attachment The entity on which the operation was performed
     * @return CULog The created log entry
     */
    public function create(OperationTypes $type, string $email, object $attachment): CULog {
       $log = CULogFactory::build(
            $this->getOperation($type),
            $email,
            $attachment,
       );
       $this->entityManager->persist($log);
       $this->entityManager->flush();
       return $log;

    }
   
    /**
     * Retrieves the email of the user who performed a specific action
     * 
     * Finds the log entry for the specified operation and entity,
     * and returns the email of the user who performed it.
     * 
     * @param OperationTypes $type The type of operation to look for
     * @param object $entity The entity on which the operation was performed
     * @return string The email of the user who performed the operation
     */
    public function getMailFromAction(OperationTypes $type, object $entity): string {
        $log = $entity->getLog()
            ->filter(fn(CULog $log) => $log->getAction() === $this->getOperation($type))
            ->first();

        if (!$log)
            throw new ResourceNotFoundException('Log');

       return $log->getMadeBy();
    }
    
    /**
     * Retrieves all logs for an entity
     * 
     * @param object $entity The entity to get logs for
     * @return array<int, CULog> Array of logs related to the entity
     */
    public function getLogFrom(object $entity): array {
        return $this->logRepository->getLogFrom($entity);
    }

    /**
     * Converts an OperationTypes enum to a string representation
     * 
     * @param OperationTypes $type The operation type to convert
     * @return string String representation of the operation
     */
    private function getOperation(OperationTypes $type): string{
        return match ($type){
            OperationTypes::CREATED => 'created',
            OperationTypes::SHARED  => 'shared',
            OperationTypes::EDITED  => 'edited',
            OperationTypes::SUBMITTED => 'submitted'
        };
    }
}