<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class RequestBodyException extends HttpException{
    public function __construct(array $exceptions){
        parent::__construct(404,
            json_encode($exceptions)
        );
    }
}
