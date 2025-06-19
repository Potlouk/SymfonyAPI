<?php
namespace App\Trait;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

trait JsonResponseTrait {

    private array $responseFormat = [
        "status" => "success",
        "message"=> "Request completed successfully.",
        "data" => []
    ];
      
    private function responseOK(mixed $data = [], int $status = Response::HTTP_OK): Response{
        $this->responseFormat['data'] = $data;
        
        $response = (new Response())
            ->setContent(json_encode($this->responseFormat))
            ->setStatusCode($status);

        $response->headers->set('Content-Type', 'application/json');
        return $response->send();
    }

    private function responsePage(mixed $data = [], int $status = Response::HTTP_OK): Response{
        $response = (new Response())
        ->setContent(
            json_encode([
                "status"   => "success",
                "message"  => "Request completed successfully.",
                "data"     => $data["data"],
                "total"    => $data["total"],
                "lastPage" => $data["lastPage"]
            ])
        )->setStatusCode($status);

        $response->headers->set('Content-Type', 'application/json');
        return $response->send();
    }

    private function removeCookie(string $key): Response {
        $response = new Response();
        $response->headers->removeCookie($key);
        return $response;
    }

    private function responseCookie(string $key, string $value): Response {
        $cookie = Cookie::create($key)
        ->withValue($value)
        ->withExpires(strtotime('+1 day'))
        ->withSecure(false) 
        ->withHttpOnly()
        ->withSameSite(Cookie::SAMESITE_STRICT);
        
        $response = new Response();
        $response->headers->setCookie($cookie);
        return $response;
    }

    private function responsePdf(?string $file): Response {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline; filename="filename.pdf"');
        $response->setContent($file);
        return $response;
    }

}