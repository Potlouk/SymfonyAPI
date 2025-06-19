<?php

declare(strict_types=1);

namespace App\Tests\Api;
use App\Tests\Helper\TestSetupHelper;
use JetBrains\PhpStorm\NoReturn;
use PHPUnit\Framework\Assert;
use App\Entity\Permission;
use App\Entity\Property;
use App\Entity\Role;
use App\Entity\Template;
use App\Entity\User;
use App\Factory\TokenFactory;
use App\Tests\Support\ApiTester;
use Codeception\Attribute\Depends;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserApiTestCest
{
    use TestSetupHelper;
    private string $endpoint = '/user';
    private string $token;

    public function _before(ApiTester $I): void
    {
        $userSettings = [
            'email' => 'test@test.test',
            'password' => 'password',
            'roleName' => 'test',
            'tokenPermissions' => [],
            'rolePermissions' => [
                'ROLE_GET_USER',
                'ROLE_DELETE_USER',
                'ROLE_PATCH_USER',
                'ROLE_CREATE_USER',
                'ROLE_LIST_USER',
                'ROLE_LIST_ROLE',
                'ROLE_CREATE_ROLE',
            ],
        ];

        $this->token = $this->createUser($userSettings, $I)["token"];
    }

    public function tryCreateUser(ApiTester $I): void {
        $endpoint = $this->endpoint;
        $I = $this->setBaseHeaders($I, $this->token);

        $roleId = $this->createRole([],$I);
        $I->sendPost($endpoint, [ 'password' => 'password', 'email' => 'testt@test.com' , 'roleId' => $roleId, 'data'=>[]]);

        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);

        $I->seeInRepository(User::class, ['id' => $responseData['id'] ]);
    }

    #[Depends('tryCreateUser')]
    public function tryGetUserByToken(ApiTester $I): void
    {
        $user = $this->createUser([], $I);
        $endpoint = "/{$this->endpoint}";

        $I = $this->setBaseHeaders($I, $user['token']);

        $I->sendGet($endpoint);

        $I->seeResponseCodeIsSuccessful();
        $responseData = $this->tryFetchResponse($I);
        Assert::assertEquals($user['id'] , $responseData['id']);
    }

    #[Depends('tryCreateUser')]
    public function tryCreateAndGetUser(ApiTester $I): void
    {
        $userId = $this->createUser([],$I)['id'];
        $endpoint = "/{$this->endpoint}/{$userId}";

        $I = $this->setBaseHeaders($I, $this->token);
        $I->sendGet($endpoint);
        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);
        Assert::assertEquals($userId , $responseData['id']);
    }

    #[Depends('tryCreateUser')]
    public function tryPatchUser(ApiTester $I): void
    {
        $userId = $this->createUser([],$I)['id'];
        $endpoint = "{$this->endpoint}/{$userId}";

        $data = [ 'email' => 'newtest@test.com'];

        $I = $this->setBaseHeaders($I, $this->token);

        $I->sendPatch($endpoint, $data);
        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);

        Assert::assertEquals($data['email'], $responseData['email']);
    }

    #[Depends('tryCreateUser')]
    public function tryDeleteUser(ApiTester $I): void
    {
        $userId = $this->createUser([],$I)['id'];
        $endpoint = "{$this->endpoint}/{$userId}";

        $I = $this->setBaseHeaders($I, $this->token);

        $I->sendDelete($endpoint);
        $I->seeResponseCodeIsSuccessful();

        $I->dontSeeInRepository(User::class, ['id' => $userId]);
    }

    #[Depends('tryCreateUser')]
    public function tryGetAllUsers(ApiTester $I)
    {  
        $this->createUser([],$I);
        $this->createUser([],$I);

        $endpoint = "{$this->endpoint}/get/all";

        $I = $this->setBaseHeaders($I, $this->token);

        $I->sendGet($endpoint);
        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);
        Assert::assertNotEmpty($responseData);
    }
}
