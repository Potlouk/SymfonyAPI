<?php

declare(strict_types=1);

namespace App\Tests\Api;
use App\Tests\Helper\TestSetupHelper;
use PHPUnit\Framework\Assert;
use App\Entity\Role;
use App\Tests\Support\ApiTester;
use Codeception\Attribute\Depends;

final class RoleApiTestCest
{
    use TestSetupHelper;
    private string $token;
    private string $endpoint = '/role';

    public function _before(ApiTester $I): void
    {
        $userSettings = [
            'email' => 'test@test.test',
            'password' => 'password',
            'roleName' => 'test',
            'tokenPermissions' => [],
            'rolePermissions' => [
                'ROLE_GET_ROLE',
                'ROLE_DELETE_ROLE',
                'ROLE_PATCH_ROLE',
                'ROLE_CREATE_ROLE',
                'ROLE_LIST_ROLE',
            ],
        ];

        $this->token = $this->createUser($userSettings, $I)["token"];
    }

    public function tryCreateRole(ApiTester $I): void {
        $endpoint = $this->endpoint;
        $I = $this->setBaseHeaders($I, $this->token);

        $I->sendPost($endpoint, [ 'name' => 'tester', 'permissions' => ["test","test"], 'treeIds' => []]);

        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);
        $I->seeInRepository(Role::class, ['id' => $responseData['id']]);
    }

    #[Depends('tryCreateRole')]
    public function tryCreateAndGetRole(ApiTester $I): void
    {
        $roleId = $this->createRole([],$I);
        $endpoint = "/{$this->endpoint}/{$roleId}";
        $I = $this->setBaseHeaders($I, $this->token);
        $I->sendGet($endpoint);
        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);
        Assert::assertArrayHasKey('id', $responseData);
        Assert::assertEquals($roleId, $responseData['id']);
    }

    #[Depends('tryCreateRole')]
    public function tryPatchRole(ApiTester $I): void
    {
        $roleId = $this->createRole([],$I);
        $endpoint = "/{$this->endpoint}/{$roleId}";

        $RoleNewData = [ 'name' => 'new', 'permissions' => ["new","new"]];

        $I = $this->setBaseHeaders($I, $this->token);

        $I->sendPatch($endpoint, $RoleNewData);
        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);
        Assert::assertEquals($roleId , $responseData['id']);
        Assert::assertEquals($RoleNewData['name'], $responseData['name']);
    }

    #[Depends('tryCreateRole')]
    public function tryDeleteRole(ApiTester $I): void
    {
        $roleId = $this->createRole([],$I);
        $endpoint = "/{$this->endpoint}/{$roleId}";

        $I = $this->setBaseHeaders($I, $this->token);

        $I->sendDelete($endpoint);

        $I->seeResponseCodeIsSuccessful();
        $I->dontSeeInRepository(Role::class, ['id' => $roleId]);
    }

    #[Depends('tryCreateRole')]
    public function tryGetAllRoles(ApiTester $I)
    {
        $this->createRole([],$I);
        $this->createRole([],$I);

        $endpoint = "{$this->endpoint}/get/all";
        $I = $this->setBaseHeaders($I, $this->token);

        $I->sendGet($endpoint);
        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);
        Assert::assertNotEmpty($responseData);
    }

}
