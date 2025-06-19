<?php

namespace App\Tests\Helper;

use App\Entity\CULog;
use App\Entity\Document;
use App\Entity\Label;
use App\Entity\Permission;
use App\Entity\Property;
use App\Entity\Report;
use App\Entity\Role;
use App\Entity\Template;
use App\Entity\Token;
use App\Entity\User;
use App\Factory\TokenFactory;
use App\Tests\Support\ApiTester;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Assert;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


trait TestSetupHelper
{

    private array $defaultLabelSettings = [
        'data' => ['test']
    ];

    private array $defaultTemplateSettings = [
            'data' => ['test'],
            'info' => ['config' => 'test'],
            'name' => 'test',
    ];

    private array $defaultPropertySettings = [
        'data' => [
            'name'    => 'test',
            'manager' => 'test',
            'logo'    => 'test'
        ],
    ];

    private array $defaultRoleSettings = [
        'name' => 'tester',
        'permissions' => ["test","test"],
        'treeIds' => []
    ];

    private array $defaultUserSettings = [
        'email' => 'test.test@test.test',
        'password' => 'password',
        'roleName' => 'test',
        'tokenPermissions' => [],
        'rolePermissions' => [],
    ];

    private array $defaultReportSettings = [
        'data' => [],
        'info' => [
            'assessmentType' => 1
        ],
        'permissions' => ['ROLE_GET_REPORT', 'ROLE_PATCH_REPORT', 'ROLE_POST_PDF'],
        'expiryDate' => null,
        'type'=> 'assessment',
    ];

    private function tryFetchResponse(ApiTester $I): array {
        try {
           $response = json_decode($I->grabResponse(), true, 512, JSON_THROW_ON_ERROR);
           Assert::assertArrayHasKey('data', $response);
           return $response['data'];
        } catch (\JsonException $e) {
            Assert::fail();
        }
    }

    /**
     * Sets repeated request parameters (auth and headers).
     *
     * @param ApiTester $I The Codeception tester instance.
     * @param string $authToken
     * @return ApiTester The modified Codeception tester instance.
     */
    private function setBaseHeaders(ApiTester $I, string $authToken): ApiTester {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->setCookie('AUTH_TOKEN', $authToken);
        return $I;
    }

    /**
     * Creates a user based on specified settings, saves them in database, and returns authentication token.
     *
     * @param array{
     *     email:            string,
     *     password:         string,
     *     roleName:         string,
     *     rolePermissions:  array<int,string>,
     *     tokenPermissions: array<int,string>,
     * } $settings settings for user creation.
     *
     * @param ApiTester $I The Codeception tester instance.
     * @return array{ token: string , id: int } The authentication token for the logged-in user.
     */
    private function createUser(array $settings, ApiTester $I): array
    {
        $settings =  empty($settings) ? $this->defaultUserSettings : $settings;

        $entityManager = $I->grabService(EntityManagerInterface::class);
        $hasher = $I->grabService(UserPasswordHasherInterface::class);

        $user = (new User)->setEmail($settings['email']);
        $role = (new Role)->setName($settings['roleName'])
            ->setPermissions((new Permission)->setValue($settings['rolePermissions'] ?? []));

        $user->setPassword($hasher->hashPassword($user, $settings['password']));
        $user->setToken(TokenFactory::build(['permissions' => $settings['tokenPermissions']]));
        $user->setRole($role);

        $entityManager->persist($user);
        $entityManager->flush();

        $I->seeInRepository(User::class, ['email' => $settings['email']]);
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPost('/login', [
            'email'     => $settings['email'],
            'password'  => $settings['password']
        ]);

        $I->seeResponseCodeIsSuccessful();
        $I->seeCookie('AUTH_TOKEN');

        return [ 'token' => $I->grabCookie('AUTH_TOKEN'), 'id' => $user->getId() ];
    }

    /**
     * Creates a property based on specified settings, saves them in database, and returns its id.
     *
     * @param array{
     *     data: array{
     *         name: string,
     *         manager: string,
     *         logo: string,
     *     },
     * } $settings Settings for property creation.
     *
     * @param ApiTester $I The Codeception tester instance.
     * @return int The id of created property.
     */
    private function createProperty(array $settings, ApiTester $I): int {
        $entityManager = $I->grabService(EntityManagerInterface::class);

        $settings = empty($settings) ? $this->defaultPropertySettings : $settings;

        $property = (new property())->setArchived(false)->setData($settings['data']);
        $entityManager->persist($property);
        $entityManager->flush();

        $I->seeInRepository(Property::class, ['id' =>  $property->getId() ]);
        return $property->getId();
    }

    /**
     * Creates a template based on specified settings, saves them in database, and returns its uuid.
     *
     * @param array{
     *     data: <string,mixed>,
     *     info: <string,mixed>,
     *     name: string,
     * } $settings Settings for template creation.
     *
     * @param ApiTester $I The Codeception tester instance.
     * @return string The uuid of created template.
     */
    private function createTemplate(array $settings, ApiTester $I): string {
        $entityManager = $I->grabService(EntityManagerInterface::class);

        $settings = empty($settings) ? $this->defaultTemplateSettings : $settings;

        $template = (new Template())->setInfo($settings['info'])
            ->setData($settings['data'])
            ->setName($settings['name']);
        $entityManager->persist($template);
        $entityManager->flush();

        $I->seeInRepository(Template::class, ['id' =>  $template->getId()]);
        return $template->getUuid();
    }

    /**
     * Creates a label based on specified settings, saves them in database, and returns its uuid.
     *
     * @param array{
     *     data: <string,mixed>,
     * } $settings Settings for label creation.
     *
     * @param ApiTester $I The Codeception tester instance.
     * @return int The id of created label.
     */
    private function createLabel(array $settings, ApiTester $I): int {
        $entityManager = $I->grabService(EntityManagerInterface::class);

        $settings = empty($settings) ? $this->defaultLabelSettings : $settings;

        $label = (new Label())->setData($settings['data']);

        $entityManager->persist($label);
        $entityManager->flush();

        $I->seeInRepository(Label::class, ['id' =>  $label->getId()]);
        return $label->getId();
    }

    /**
     * Creates a document based on specified settings, saves them in database, and returns its uuid.
     *
     * @param array{
     *     info: <string,mixed>,
     *     type: string,
     *     propertyId: int,
     *     templateUuid: string,
     *     createdBy: string,
     * } $settings Settings for document creation.
     *
     * @param ApiTester $I The Codeception tester instance.
     * @return string The uuid of created document.
     */
    private function createDocument(array $settings, ApiTester $I): string
    {
        $entityManager = $I->grabService(EntityManagerInterface::class);

        $settings['templateUuid'] ??= $this->createTemplate([], $I);
        $settings['propertyId'] ??= $this->createProperty([], $I);

        $permissions = (new Permission())->setValue(['ROLE_GET_DOCUMENT', 'ROLE_PATCH_DOCUMENT', 'ROLE_POST_PDF', 'ROLE_SUBMIT_DOCUMENT']);
        $document = (new Document())->setData([])
            ->setInfo($settings['info'] ?? [])
            ->setType($settings['type'] ?? '')
            ->setToken(
                (new Token())->setValue('DocumentToken')->setActive(false)->setReceiver([])->setPermissions($permissions))
            ->setStatus('DEFAULT')
            ->setProperty(
                $I->grabEntityFromRepository(Property::class, ['id' => $settings['propertyId']])
            )->setTemplate(
                $I->grabEntityFromRepository(Template::class, ['uuid' => $settings['templateUuid']])
            );

        $log = (new CULog())->setAction('created')->setMadeBy($settings['createdBy'] ?? '')->setDocument($document);

        $entityManager->persist($document);
        $entityManager->persist($log);
        $entityManager->flush();

        $I->seeInRepository(Document::class, ['uuid' => $document->getUuid()]);
        return $document->getUuid();
    }

    /**
     * Creates a role based on specified settings, saves them in database, and returns its id.
     *
     * @param array{
     *     permissions: <string,mixed>,
     *     name: string,
     * } $settings Settings for document creation.
     *
     * @param ApiTester $I The Codeception tester instance.
     * @return int The id of created role.
     */
    private function createRole(array $settings, ApiTester $I): int {
        $entityManager = $I->grabService(EntityManagerInterface::class);

        $settings = empty($settings) ? $this->defaultRoleSettings : $settings;

        $role = (new Role())->setName($settings['name'])->setPermissions(
            (new Permission())->setValue($settings['permissions'])
        )->setTreeIds($settings['treeIds']);

        $entityManager->persist($role);
        $entityManager->flush();

        $I->seeInRepository(Role::class, ['id' => $role->getId() ]);
        return $role->getId();
    }

    /**
     * Creates a report based on specified settings, saves them in database, and returns its id.
     *
     * @param array{
     *     data: <string,mixed>,
     *     info: <string, mixed>,
     *     type: string,
     *     propertyId: int,
     *     templateUuid: string,
     *     documentUuid: string,
     *
     * } $settings Settings for document creation.
     *
     * @param ApiTester $I The Codeception tester instance.
     * @return string The uuid of created report.
     */
    private function createReport(array $settings, ApiTester $I): string {
        $entityManager = $I->grabService(EntityManagerInterface::class);

        $settings = empty($settings) ? $this->defaultReportSettings : $settings;

        $settings['propertyId'] ??= $this->createProperty([], $I);
        $settings['templateUuid'] ??= $this->createTemplate([], $I);
        $settings['documentUuid'] ??= $this->createDocument([], $I);

        $permissions = (new Permission())->setValue(['ROLE_GET_REPORT', 'ROLE_PATCH_REPORT', 'ROLE_POST_PDF']);

        $report = (new Report())->setData($settings['data'] ?? [])
            ->setInfo($settings['info'] ?? [])
            ->setType($settings['type'] ?? '')
            ->setToken(
                (new Token())->setValue('ReportToken')->setActive(true)->setReceiver([
                    'email' => 'name'
                ])->setPermissions(
                    $permissions
                )
            )->setProperty(
                $I->grabEntityFromRepository(Property::class, ['id' => $settings['propertyId']])
            )->setTemplate(
                $I->grabEntityFromRepository(Template::class, ['uuid' => $settings['templateUuid']])
            )->setDocument(
                $I->grabEntityFromRepository(Document::class, ['uuid' => $settings['documentUuid']])
            );

        $entityManager->persist($report);
        $entityManager->flush();

        $I->seeInRepository(Report::class, ['uuid' => $report->getUuid()]);
        return $report->getUuid();
    }
}