<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InternalException extends HttpException{
    public function __construct(){
        parent::__construct(500, 
            "server couldn't handle request"
        );
    }
}
