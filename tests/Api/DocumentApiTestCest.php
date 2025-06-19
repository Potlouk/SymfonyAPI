<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Entity\Document;
use App\Tests\Helper\TestSetupHelper;
use PHPUnit\Framework\Assert;
use App\Tests\Support\ApiTester;
use Codeception\Attribute\Depends;
use DateTimeImmutable;

final class DocumentApiTestCest
{
    use TestSetupHelper;
    private string  $token;
    private string  $endpoint = '/document';
    private array   $testerSettings;

    public function _before(ApiTester $I): void
    {
        $userSettings = array(
            'email' => '',
            'password' => 'password',
            'roleName' => 'tester',
            'tokenPermissions' => [],
            'rolePermissions' => [
                'ROLE_LABEL_DOCUMENT',
                'ROLE_ASSIGN_DOCUMENT',
                'ROLE_CREATE_DOCUMENT',
                'ROLE_GET_DOCUMENT',
                'ROLE_PATCH_DOCUMENT',
                'ROLE_DELETE_DOCUMENT',
                'ROLE_SHARE_DOCUMENT',
                'ROLE_PAGINATE_DOCUMENT',
                'ROLE_ALL_DOCUMENT',
                'ROLE_GET_USER',
            ],
        );
        $this->token = $this->createUser($userSettings, $I)['token'];
        $this->testerSettings = $userSettings;
    }

    public function tryCreateDocument(ApiTester $I): void {
        $endpoint = $this->endpoint;

        $propertyId = $this->createProperty([], $I);
        $templateUuid = $this->createTemplate([], $I);

        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->setCookie('AUTH_TOKEN', $this->token);

        $documentData = [
            'info'         => ['name' => 'test'],
            'propertyId'   => $propertyId,
            'templateUuid' => $templateUuid,
            'type'         => 'work-order',
            'expiryDate'   => (new DateTimeImmutable())->modify('+48 hours +1 minute')->format('F dS Y '),
        ];

        $I->sendPost($endpoint, $documentData);
        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);

        Assert::assertArrayHasKey('name', $documentData['info']);
        Assert::assertEquals($responseData['type'], $documentData['type']);
        Assert::assertEquals($responseData['property']['id'], $propertyId);
        Assert::assertEquals($responseData['templateUuid'], $templateUuid);
    }

    #[Depends('tryCreateDocument')]
    public function tryGetDocument(ApiTester $I): void
    {
        $documentSettings = [
            'propertyId'   => $this->createProperty([], $I),
            'templateUuid' => $this->createTemplate([], $I),
        ];

        $documentUuid = $this->createDocument($documentSettings, $I);

        $endpoint = "{$this->endpoint}/{$documentUuid}";
        $I = $this->setBaseHeaders($I, $this->token);
        $I->sendGet($endpoint);

        $I->seeResponseCodeIsSuccessful();
        $responseData = $this->tryFetchResponse($I);

        Assert::assertArrayHasKey('uuid', $responseData);
        Assert::assertEquals($documentUuid, $responseData['uuid']);
    }

    #[Depends('tryCreateDocument')]
    public function tryPatchDocument(ApiTester $I): void
    {
        $documentSettings = [
            'propertyId'   => $this->createProperty([], $I),
            'templateUuid' => $this->createTemplate([], $I),
        ];

        $documentUuid = $this->createDocument($documentSettings, $I);
        $endpoint = "{$this->endpoint}/{$documentUuid}";

        $documentSettings = [
            'data' => ['new']
        ];

        $I = $this->setBaseHeaders($I, $this->token);

        $I->sendPatch($endpoint, $documentSettings);
        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);

        Assert::assertEquals($documentSettings['data'], $responseData['data']);
    }

    #[Depends('tryCreateDocument')]
    public function tryDeleteDocument(ApiTester $I): void
    {
        $documentSettings = [
            'propertyId'   => $this->createProperty([], $I),
            'templateUuid' => $this->createTemplate([], $I),
        ];

        $documentUuid = $this->createDocument($documentSettings, $I);
        $endpoint = "{$this->endpoint}/{$documentUuid}";

        $I = $this->setBaseHeaders($I, $this->token);

        $I->sendDelete($endpoint);

        $I->seeResponseCodeIsSuccessful();
        $I->dontSeeInRepository(Document::class, ['uuid' => $documentUuid]);
    }

    #[Depends('tryCreateDocument')]
    public function tryAddLabelsToDocument(ApiTester $I): void
    {
        $documentSettings = [
            'propertyId'   => $this->createProperty([], $I),
            'templateUuid' => $this->createTemplate([], $I),
        ];

        $documentUuid = $this->createDocument($documentSettings, $I);
        $labelId = $this->createLabel([],$I);

        $endpoint = "{$this->endpoint}/label/{$documentUuid}";

        $I = $this->setBaseHeaders($I, $this->token);

        $labelData = [
            'labelIds' => [$labelId]
        ];

        $I->sendPut($endpoint, $labelData);
        $I->seeResponseCodeIsSuccessful();

        $document = $I->grabEntityFromRepository(Document::class, ['uuid'=> $documentUuid]);
        $labels = $document->getLabels()->toArray();

        Assert::assertCount(1, $labels);
        Assert::assertEquals($labelId, $labels[0]->getId());
    }

    #[Depends('tryCreateDocument')]
    public function tryAssignUsersToDocument(ApiTester $I): void
    {
        $userId = $this->createUser([], $I)['id'];

        $documentSettings = [
            'propertyId'   => $this->createProperty([], $I),
            'templateUuid' => $this->createTemplate([], $I),
        ];

        $documentUuid = $this->createDocument($documentSettings, $I);

        $endpoint = "{$this->endpoint}/assign/{$documentUuid}";

        $I = $this->setBaseHeaders($I, $this->token);

        $I->sendPut($endpoint,[
            'userIds' => [$userId]
        ]);

        $I->seeResponseCodeIsSuccessful();
        $I->sendGet("{$this->endpoint}/{$documentUuid}");

        $responseData = $this->tryFetchResponse($I);

        Assert::assertArrayHasKey('assignedUsers', $responseData);
        Assert::assertContains($userId, $responseData['assignedUsers']);

        $endpoint = "/user/{$userId}";

        $I->sendGet($endpoint);
        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);

        Assert::assertArrayHasKey('assignedDocuments', $responseData);
        Assert::assertContains($documentUuid, $responseData['assignedDocuments']);
    }

    #[Depends('tryCreateDocument')]
    public function tryGetAssignedDocumentsInPagination(ApiTester $I): void
    {
        $documentSettings = [
            'propertyId'   => $this->createProperty([], $I),
            'templateUuid' => $this->createTemplate([], $I),
            'type' => 'work-order'
        ];

        $userSettings = [
            'email' => 'test@test.test',
            'password' => 'password',
            'roleName' => 'test',
            'tokenPermissions' => [],
            'rolePermissions' => [
                'ROLE_PAGINATE_DOCUMENT'
            ],
        ];

        $documentUuid = $this->createDocument($documentSettings, $I);
        $this->createDocument($documentSettings, $I);

        $userToAssign = $this->createUser($userSettings, $I);
        $endpoint = "{$this->endpoint}/assign/{$documentUuid}";

        $I = $this->setBaseHeaders($I, $this->token);

        $I->sendPut($endpoint,[
            'userIds' => [$userToAssign['id']]
        ]);

        $I->seeResponseCodeIsSuccessful();
        $endpoint = "{$this->endpoint}/paginate/1/10";

        $I = $this->setBaseHeaders($I, $userToAssign['token']);

        $I->sendPost($endpoint,[ 'filters' => ['type' => 'work-order']]);
        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);
        Assert::assertCount(1, $responseData);
    }

    #[Depends('tryCreateDocument')]
    public function tryGetAllDocuments(ApiTester $I): void
    {
        $endpoint = "{$this->endpoint}/paginate/1/10";

        $documentSettings = [
            'propertyId'   => $this->createProperty([], $I),
            'templateUuid' => $this->createTemplate([], $I),
        ];

        $documentSettings['info']['name'] = 'w1';
        $documentSettings['type'] = 'work-order';
        $this->createDocument($documentSettings, $I);

        $documentSettings['info']['name'] = 'w2';
        $documentSettings['type'] = 'assessment';
        $this->createDocument($documentSettings, $I);

        $documentSettings['info']['name'] = 'w3';
        $this->createDocument($documentSettings, $I);

        $I = $this->setBaseHeaders($I, $this->token);

        $I->sendPost($endpoint, [
            'filters' => [
                'type'              => 'assessment',
                'propertyIds'       => [$documentSettings['propertyId']],
                'assessmentType'    => [],
                'name'              => "",
                'dates'             => [],
                'labelIds'          => [],
            ]
        ]);
        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);
        Assert::assertCount(2, $responseData);

        $I->sendPost($endpoint,[ 'filters' => ['type' => 'assessment']]);
        $responseData = $this->tryFetchResponse($I);
        Assert::assertCount(2, $responseData);

        $I->sendPost($endpoint,[ 'filters' => ['name' => 'w3' ]]);
        $responseData = $this->tryFetchResponse($I);
        Assert::assertCount(1, $responseData);

        $I->sendPost($endpoint,[ 'filters' => ['type' => 'work-order']]);
        $responseData = $this->tryFetchResponse($I);
        Assert::assertCount(1, $responseData);

        $I->sendPost($endpoint,[ 'filters' => [ 'name' => 'w1', 'type' => 'assessment']]);
        $responseData = $this->tryFetchResponse($I);
        Assert::assertCount(0, $responseData);

        $documentUuid = $this->createDocument($documentSettings, $I);
        $labelId = $this->createLabel([],$I);

        $endpoint = "{$this->endpoint}/label/{$documentUuid}";

        $I = $this->setBaseHeaders($I, $this->token);

        $I->sendPut($endpoint,[
            'labelIds' => [$labelId]
        ]);

        $I->seeResponseCodeIsSuccessful();

        $endpoint = "{$this->endpoint}/paginate/1/1";
        $I->sendPost($endpoint,[ 'filters' => ['labelIds' => [$labelId]]]);
        $responseData = $this->tryFetchResponse($I);
        Assert::assertCount(1, $responseData);
    }

    #[Depends('tryCreateDocument')]
    public function tryShareAndSubmitDocument(ApiTester $I): void
    {
        $documentSettings = [
            'createdBy' => $this->testerSettings['email'],
            'propertyId' => $this->createProperty([], $I),
            'templateUuid' => $this->createTemplate([], $I),
        ];

        $documentSettings['info']['name'] = 'w1';
        $documentSettings['type'] = 'work-order';
        $documentUuid = $this->createDocument($documentSettings, $I);

        $endpoint = "/document/share/{$documentUuid}";

        $shareSettings = [
            'permissions' => ['ROLE_GET_DOCUMENT', 'ROLE_PATCH_DOCUMENT', 'ROLE_POST_PDF', 'ROLE_SUBMIT_DOCUMENT'],
            'expiryDate' => '2025-03-19 00:00:00',
            'validDate' => '2025-03-19 00:00:00',
            'receiver' => [
                'id' => null,
                'email' => '',
                'fullName' => 'null',
                'notes' => 'tss',
            ],
        ];

        $I = $this->setBaseHeaders($I, $this->token);
        $I->sendPost($endpoint, $shareSettings);
        $I->seeResponseCodeIsSuccessful();

        $document = $I->grabEntityFromRepository(Document::class, ['uuid' => $documentUuid]);
        Assert::assertEquals('INPROGRESS', $document->getStatus());

        $endpoint = "/document/submit/{$documentUuid}";
        $documentToken = $document->getToken()?->getValue();

        if (null === $documentToken)
            Assert::fail('Token creation failed.');

        $I->haveHttpHeader('X-Public-Token', $documentToken);
        $data = json_decode('{"areas":[{"areaName":"Example Area","items":[{"itemName":"Building Exterior","files":[{"blob":[],"cost":{"total":0,"tenant":0,"landlord":0},"fileSrc":"storage/1/69971a58-3080-4477-8a2c-e389d647bbec/b4abbb8b-6ccd-4ede-a485-f727d2d15ac4","fileName":"b4abbb8b-6ccd-4ede-a485-f727d2d15ac4","fileUpload":{"time":1719500392207,"user":"Peso Pes"}}],"notes":"Here 123","jobCost":{"total":0,"tenant":0,"landlord":0},"comments":[],"hasValue":false},{"itemName":"Landscaping","files":[],"notes":null,"jobCost":{"total":0,"tenant":0,"landlord":0},"comments":[],"hasValue":false}]}]}', true);
        $I->sendPost($endpoint, [
            'data' => $data,
            'info' => ['test'],
            'type' => 'test',
        ]);

        $I->seeResponseCodeIsSuccessful();

        $document = $I->grabEntityFromRepository(Document::class, ['uuid' => $documentUuid]);
        Assert::assertEquals('SUBMITTED', $document->getStatus());

        $I = $this->setBaseHeaders($I, $this->token);
        $endpoint = "/document/reopen/{$documentUuid}";
        $I->sendPut($endpoint);
        $I->seeResponseCodeIsSuccessful();

        $document = $I->grabEntityFromRepository(Document::class, ['uuid' => $documentUuid]);
        Assert::assertEquals('INPROGRESS', $document->getStatus());
    }
}
