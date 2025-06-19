<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class LogicException extends HttpException{
    public function __construct(string $msg){
        parent::__construct(404, 
            $msg
        );
    }
}
