<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Label;
use App\Tests\Helper\TestSetupHelper;
use PHPUnit\Framework\Assert;
use App\Tests\Support\ApiTester;
use Codeception\Attribute\Depends;


final class LabelApiTestCest
{
    use TestSetupHelper;
    private string $endpoint = '/label';
    private string $token;

    public function _before(ApiTester $I): void
    {
        $userSettings = array(
            'email' => 'testtest@test.com',
            'password' => 'password',
            'roleName' => 'tester',
            'tokenPermissions' => [],
            'rolePermissions' => [
                'ROLE_DELETE_LABEL',
                'ROLE_PATCH_LABEL',
                'ROLE_CREATE_LABEL',
                'ROLE_LIST_LABEL',
            ],
        );

        $this->token = $this->createUser($userSettings, $I)['token'];
    }

    public function tryCreateLabel(ApiTester $I): void {
        $endpoint = $this->endpoint;
        $this->setBaseHeaders($I, $this->token);

        $I->sendPost($endpoint, ['data' => ['test']]);
        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);
        $I->seeInRepository(Label::class, ['id' => $responseData['id'] ]);
    }

    #[Depends('tryCreateLabel')]
    public function tryPatchLabel(ApiTester $I): void
    {
        $labelId = $this->createLabel([], $I);
        $endpoint = "{$this->endpoint}/{$labelId}";

        $data = [ 'data' => ['new'] ];
        
        $I = $this->setBaseHeaders($I, $this->token);
        $I->sendPut($endpoint, $data);

        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);
        Assert::assertEquals($data['data'], $responseData['data']);
    }

    #[Depends('tryCreateLabel')]
    public function tryDeleteLabel(ApiTester $I): void
    {
        $labelId = $this->createLabel([],$I);
        $endpoint = "{$this->endpoint}/{$labelId}";

        $I = $this->setBaseHeaders($I, $this->token);
        $I->sendDelete($endpoint);

        $I->seeResponseCodeIsSuccessful();
        $I->dontSeeInRepository(Label::class, ['id' => $labelId]);
    }

    #[Depends('tryCreateLabel')]
    public function tryGetAllLabels(ApiTester $I)
    {
        $this->createLabel([],$I);
        $this->createLabel([],$I);

        $endpoint = "{$this->endpoint}/get/all";

        $I = $this->setBaseHeaders($I, $this->token);
        
        $I->sendGet($endpoint);
        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);

        Assert::assertCount(2, $responseData);
    }
}
