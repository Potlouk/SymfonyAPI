<?php

declare(strict_types=1);

namespace App\Tests\Api;
use App\Tests\Helper\TestSetupHelper;
use PHPUnit\Framework\Assert;
use App\Entity\Template;
use App\Tests\Support\ApiTester;
use Codeception\Attribute\Depends;


final class TemplateApiTestCest
{
    use TestSetupHelper;
    private string $token;
    private string $endpoint = '/template';


    public function _before(ApiTester $I): void
    {
        $userSettings = [
            'email' => 'test@test.test',
            'password' => 'password',
            'roleName' => 'test',
            'tokenPermissions' => [],
            'rolePermissions' => [
                'ROLE_GET_TEMPLATE',
                'ROLE_DELETE_TEMPLATE',
                'ROLE_PATCH_TEMPLATE',
                'ROLE_CREATE_TEMPLATE',
                'ROLE_PAGINATE_TEMPLATE',
            ],
        ];

        $this->token = $this->createUser($userSettings, $I)["token"];
    }

    public function tryCreateTemplate(ApiTester $I): void {
        $endpoint = $this->endpoint;

        $I = $this->setBaseHeaders($I, $this->token);
        $I->sendPost( $endpoint, [ 'data' => ['test'], 'info' => ['test'] , 'name' => 'test']);

        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);

        $I->seeInRepository(Template::class, ['uuid' => $responseData['uuid']]);
    }

    #[Depends('tryCreateTemplate')]
    public function tryCreateAndGetTemplate(ApiTester $I): void
    {
        $templateUuid = $this->createTemplate([], $I);
        $endpoint = "/{$this->endpoint}/{$templateUuid}";

        $I = $this->setBaseHeaders($I, $this->token);
        $I->sendGet($endpoint);

        $I->seeResponseCodeIsSuccessful();
        $responseData = $this->tryFetchResponse($I);
        Assert::assertArrayHasKey('uuid', $responseData);
        Assert::assertEquals($templateUuid , $responseData['uuid']);
    }

    #[Depends('tryCreateTemplate')]
    public function tryPatchTemplate(ApiTester $I): void
    {
        $templateUuid = $this->createTemplate([], $I);
        $endpoint = "{$this->endpoint}/{$templateUuid}";

        $data = [ 
            'data' => ['new' => 'test'], 
            'info' => ['sads' => 'test'], 
            'name' => 'new' , 
            'createdAt'=> 'dsadassadsdasd dasd',
            'uuid'=> 'dsadassadsdasd dasd',
            'updatedAt'=> 'dsadassadsdasd dasd',
        ];

        $I = $this->setBaseHeaders($I, $this->token);

        $I->sendPatch($endpoint, $data);

        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);

        Assert::assertEquals($data['data'], $responseData['data']);
        Assert::assertEquals($data['name'], $responseData['name']);
    }

    #[Depends('tryCreateTemplate')]
    public function tryDeleteTemplate(ApiTester $I): void
    {
        $templateUuid = $this->createTemplate([], $I);
        $endpoint = "{$this->endpoint}/{$templateUuid}";

        $I = $this->setBaseHeaders($I, $this->token);

        $I->sendDelete($endpoint);

        $I->seeResponseCodeIsSuccessful();
        $I->dontSeeInRepository(Template::class, ['uuid' => $templateUuid]);
    }

    #[Depends('tryCreateTemplate')]
    public function tryGetAllTemplates(ApiTester $I) : void
    {
         $this->createTemplate([],$I);
         $this->createTemplate([],$I);

        $endpoint = "{$this->endpoint}/1/10/null";

        $I = $this->setBaseHeaders($I, $this->token);

        $I->sendGet($endpoint);
        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);
        Assert::assertNotEmpty($responseData);
    }
}
