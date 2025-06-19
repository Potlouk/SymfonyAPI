<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ResourceNotFoundException extends HttpException{
    public function __construct(string $entity){
        parent::__construct(404, 
            "{$entity} not found"
        );
    }
}
