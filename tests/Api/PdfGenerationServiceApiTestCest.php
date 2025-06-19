<?php

namespace App\Tests\Api;

use App\Tests\Helper\TestSetupHelper;
use App\Tests\Support\ApiTester;

final class PdfGenerationServiceApiTestCest
{
    use TestSetupHelper;
    private string $endpoint = 'utility/generate/pdf';
    private string $token;

    public function _before(ApiTester $I): void
    {
        $userSettings = [
            'email' => 'test@test.test',
            'password' => 'password',
            'roleName' => 'test',
            'tokenPermissions' => [],
            'rolePermissions' => [
                'ROLE_POST_PDF'
            ],
        ];

       $this->token = $this->createUser($userSettings, $I)["token"];
    }

    public function tryGeneratePdf(ApiTester $I): void
    {
        $testData = ['html' => 'test'];

        $I = $this->setBaseHeaders($I, $this->token);
        $I->sendPost($this->endpoint, $testData);

        $I->seeResponseCodeIsSuccessful();
        $I->seeHttpHeader('Content-Type', 'application/pdf');
    }

}
