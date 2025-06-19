<?php
namespace App\EventListener;

use App\Exception\LogicException;
use App\Exception\RequestBodyException;
use App\Exception\ResourceNotFoundException;
use DateMalformedStringException;
use Doctrine\DBAL\Types\ConversionException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ExceptionListener {
    
    public function onKernelException(ExceptionEvent $event): void {
        $exception = $event->getThrowable();
        
        if ($exception instanceof NotFoundHttpException) 
            $event->setResponse($this->response('Endpoint not found.', $exception->getStatusCode()));

        if ($exception instanceof ResourceNotFoundException) 
            $event->setResponse($this->response($exception->getMessage(), $exception->getStatusCode()));
    
        if ($exception instanceof BadCredentialsException) 
            $event->setResponse($this->response($exception->getMessage(), 401));
        
        if ($exception instanceof UnauthorizedHttpException) 
            $event->setResponse($this->response($exception->getMessage(), 401));
       
        if ($exception instanceof AccessDeniedException) 
            $event->setResponse($this->response($exception->getMessage(), 401));

        if ($exception instanceof AuthenticationException) 
            $event->setResponse($this->response($exception->getMessage(), 401));

        if ($exception instanceof LogicException) 
            $event->setResponse($this->response($exception->getMessage(), 403));
            
        if ($exception instanceof MethodNotAllowedHttpException) 
            $event->setResponse($this->response(json_encode($exception->getMessage()), 405));

        if ($exception instanceof ConversionException) 
            $event->setResponse($this->response(json_encode($exception->getMessage()), 400));

        if ($exception instanceof RequestBodyException)
            $event->setResponse($this->response(json_decode($exception->getMessage(),true), 400));

        if ($exception instanceof DateMalformedStringException)
            $event->setResponse($this->response(json_encode($exception->getMessage()), 403));

        if ($exception instanceof BadRequestHttpException)
            $event->setResponse($this->response(json_encode($exception->getMessage()), 400));

    }

    private function response(string|array $msg, int $status): Response {
        $response = (new Response())->setStatusCode($status)
            ->setContent(json_encode([
                'status' => 'error',
                'message' => $msg
            ]));

        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

}
