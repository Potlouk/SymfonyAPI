<?php 

namespace App\Interface;

use App\Entity\CULog;
use App\Enum\OperationTypes;

interface LogInterface {
    public function create(OperationTypes $type, string $email, object $attachment): CULog ;
    public function getMailFromAction(OperationTypes $type, object $entity): string;
    public function getLogFrom(object $entity): array ;
}