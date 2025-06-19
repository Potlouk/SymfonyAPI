<?php

declare(strict_types=1);

namespace App\Tests\Api;
use App\Tests\Helper\TestSetupHelper;
use PHPUnit\Framework\Assert;
use App\Entity\Permission;
use App\Entity\Property;
use App\Entity\Role;
use App\Entity\User;
use App\Factory\TokenFactory;
use App\Tests\Support\ApiTester;
use Codeception\Attribute\Depends;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class PropertyApiTestCest
{
    use TestSetupHelper;
    private string $token;
    private string $endpoint = '/property';

    public function _before(ApiTester $I): void
    {
        $userSettings = array(
            'email' => 'test@test.test',
            'password' => 'password',
            'roleName' => 'tester',
            'tokenPermissions' => [],
            'rolePermissions' => [
                'ROLE_GET_PROPERTY',
                'ROLE_DELETE_PROPERTY',
                'ROLE_PATCH_PROPERTY',
                'ROLE_CREATE_PROPERTY',
                'ROLE_PAGINATE_PROPERTY',
            ],
        );
        $this->token = $this->createUser($userSettings, $I)['token'];
    }

    public function tryCreateProperty(ApiTester $I): void {
        $endpoint = $this->endpoint;

        $I = $this->setBaseHeaders($I, $this->token);
        $I->sendPost($endpoint, ['data' => []]);

        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);
        $I->seeInRepository(Property::class, ['id' => $responseData['id'] ]);
    }

    #[Depends('tryCreateProperty')]
    public function tryCreateAndGetProperty(ApiTester $I): void
    {
        $propertyId = $this->createProperty([],$I);
        $endpoint = "/{$this->endpoint}/{$propertyId}";
        $I = $this->setBaseHeaders($I, $this->token);
        $I->sendGet($endpoint);
        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);
        Assert::assertArrayHasKey('id', $responseData);
        Assert::assertEquals($propertyId, $responseData['id']);
    }

    #[Depends('tryCreateProperty')]
    public function tryPatchProperty(ApiTester $I): void
    {
        $propertyId = $this->createProperty([],$I);
        $endpoint = "/{$this->endpoint}/{$propertyId}";

        $propertyNewData = [ 'data' => ['new'], 'archived' => true];
        
        $I = $this->setBaseHeaders($I, $this->token);
        $I->sendPatch($endpoint, $propertyNewData);

        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);

        Assert::assertEquals($propertyNewData['data'], $responseData['data']);
    }

    #[Depends('tryCreateProperty')]
    public function tryDeleteProperty(ApiTester $I): void
    {
        $propertyId = $this->createProperty([],$I);
        $endpoint = "/{$this->endpoint}/{$propertyId}";

        $I = $this->setBaseHeaders($I, $this->token);
        $I->sendDelete($endpoint);

        $I->seeResponseCodeIsSuccessful();
        $I->dontSeeInRepository(Property::class, ['id' => $propertyId]);
    }

    #[Depends('tryCreateProperty')]
    public function tryPaginateProperty(ApiTester $I): void
    {  
        $this->createProperty(['data' => ['name' => 'testFirst']],$I);
        $this->createProperty(['data' => ['name' => 'testSecond']],$I);

        $endpoint = "/{$this->endpoint}/1/10/false/testFirst";
        $I = $this->setBaseHeaders($I, $this->token);
        $I->sendGet($endpoint);
        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);
        Assert::assertCount(1, $responseData);

        $endpoint = "/{$this->endpoint}/1/10/false/testSecond";
        $I->sendGet($endpoint);

        $responseData = $this->tryFetchResponse($I);
        Assert::assertCount(1, $responseData);
    }
}
