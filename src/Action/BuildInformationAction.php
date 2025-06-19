<?php

namespace App\Action;

use App\Exception\LogicException;
use App\Model\BuildInformation;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use symfony\Contracts\HttpClient\ResponseInterface;

final class BuildInformationAction {

    public static function build(string $type) : BuildInformation
    {
        if ($type !== 'ionic' && $type !== 'angular')
            throw new LogicException('Invalid build information type provided');

        $versionEndpoint = ($type === 'ionic') ? $_ENV('IONIC_VERSION_URL') : $_ENV('ANGULAR_VERSION_URL');
        $buildEndpoint = ($type === 'ionic') ? $_ENV('IONIC_BUILD_PATH') : $_ENV('ANGULAR_BUILD_PATH');

        $client = HttpClient::create();
        $buildInformation = new BuildInformation();

        $buildInformation->setVersion(self::setVersion($client, $versionEndpoint));
        $buildInformation->setBuild(self::setbuild($client, $buildEndpoint));

        return $buildInformation;
    }

    private static function setVersion(HttpClientInterface $client, string $endpoint): ?string {
        $url = $_ENV('GITHUB_USER_URL') . $endpoint;
        $token = $_ENV('GITHUB_TOKEN_VERSION');

        try {
            $response = $client->request('GET',  $url, [
                'headers' => [
                    'Authorization' => "token {$token}",
                    'Accept' => 'application/vnd.github.v3.raw'
                ]
            ]);

            return ($response->getStatusCode() !== 200) ? null : self::tryFetchResult($response,'version');

        } catch (TransportExceptionInterface) {
            return null;
        }

    }

    private static function setBuild(HttpClientInterface $client, string $endpoint): ?string {
        $url = $_ENV('APP_BUILD_URL'). $endpoint;
        $token = $_ENV('DIGITALOCEAN_TOKEN_BUILD');

        try {
            $response = $client->request('GET',  $url, [
                'headers' => [
                    'Authorization' => "Bearer {$token}",
                    'Content-Type' => 'application/json'
                ]
            ]);

            return ($response->getStatusCode() !== 200) ? null : self::tryFetchResult($response,'body');

        } catch (TransportExceptionInterface) {
            return null;
        }

    }

    private static function tryFetchResult(ResponseInterface $response, string $key): ?string {
        try {
             $result = $response->toArray();
        } catch (ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface) {
            return null;
        }

        if (!array_key_exists($key,$result))
            return null;

        return $result[$key];
    }
}