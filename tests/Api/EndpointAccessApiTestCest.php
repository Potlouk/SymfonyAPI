<?php

declare(strict_types=1);

namespace App\Tests\Api;
use App\Tests\Helper\TestSetupHelper;
use PHPUnit\Framework\Assert;
use App\Entity\Permission;
use App\Entity\Role;
use App\Entity\User;
use App\Factory\TokenFactory;
use App\Tests\Support\ApiTester;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class EndpointAccessApiTestCest
{
    use TestSetupHelper;

    private string $token;
    private int $userId;

    public function _before(ApiTester $I): void
    {
        $userSettings = array(
            'email' => 'testtest@test.com',
            'password' => 'password',
            'roleName' => 'tester',
            'tokenPermissions' => [],
            'rolePermissions' => [
                'ROLE_GET_USER',
                'ROLE_PATCH_USER',
                'ROLE_CREATE_ROLE',
            ],
        );
        $user = $this->createUser($userSettings, $I);
        $this->token = $user['token'];
        $this->userId = $user['id'];
    }

    public function tryGiveAccessToRoute(ApiTester $I): void
    {
        $user = $this->createUser([], $I);
        $newUserToken = $user['token'];
        $newUserId = $user['id'];

        $endpoint = "/user/{$this->userId}";
        $I = $this->setBaseHeaders($I, $newUserToken);
        $I->sendGet($endpoint);
        $I->seeResponseCodeIs(401);

        $roleSettings = [
            'name' => 'endpointAccess',
            'permissions' => ['ROLE_GET_USER'],
            'treeIds' => []
        ];

        $endpoint = "/user/{$newUserId}";
        $roleId = $this->createRole($roleSettings, $I);
        $I = $this->setBaseHeaders($I, $this->token);
        $I->sendPatch($endpoint, ['roleId' => $roleId ]);

        $I = $this->setBaseHeaders($I, $newUserToken);
        $I->sendGet($endpoint);
        $I->canSeeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);
        Assert::assertEquals($newUserId, $responseData['id']);
    }


}
