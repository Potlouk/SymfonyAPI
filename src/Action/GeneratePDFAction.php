<?php

namespace App\Action;

use App\Constraint\RequestConstraints;
use App\Trait\ServiceHelper;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GeneratePDFAction {
use ServiceHelper;

    public function __construct(readonly private HttpClientInterface $httpClient) {}

    public function generate(array $data) : ?string
    {
        $this->validateRequestData($data, RequestConstraints::pdfGeneratorConstraintPOST());

        $url = 'http://node:3000/generate-pdf';

        try {
            $response = $this->httpClient->request('POST',  $url, [
                'json' => [
                    'html' => $data['html']
                ],
                'headers' => [
                    'Accept' => 'application/pdf'
                ]
            ]);

            return ($response->getStatusCode() !== 200) ? null : $response->getContent(false);

        } catch (TransportExceptionInterface|ClientExceptionInterface|ServerExceptionInterface|RedirectionExceptionInterface) {
            return null;
        }

    }

}