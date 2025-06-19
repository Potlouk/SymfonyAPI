<?php

declare(strict_types=1);

namespace App\Tests\Api;

use App\Tests\Helper\TestSetupHelper;
use PHPUnit\Framework\Assert;
use App\Entity\Report;
use App\Entity\User;
use App\Tests\Support\ApiTester;
use Codeception\Attribute\Depends;

final class ReportApiTestCest
{
    use TestSetupHelper;
    private string $endpoint = '/report';
    private string $token;

    public function _before(ApiTester $I): void
    {
        $userSettings = [
            'email' => 'test@test.test',
            'password' => 'password',
            'roleName' => 'test',
            'tokenPermissions' => [],
            'rolePermissions' => [
                'ROLE_LABEL_REPORT',
                'ROLE_ASSIGN_REPORT',
                'ROLE_GET_REPORT',
                'ROLE_DELETE_REPORT',
                'ROLE_PATCH_REPORT',
                'ROLE_CREATE_REPORT',
                'ROLE_PAGINATE_REPORT',
                'ROLE_ALL_REPORT',
            ],
        ];

        $this->token = $this->createUser($userSettings, $I)["token"];
    }

    public function tryCreateReport(ApiTester $I): void
    {
        $reportSettings = [
            'data' => [],
            'info' => [
                'assessmentType' => 1
            ],
            'permissions' => ['test'],
            'expiryDate' => null,
            'type'=> 'assessment',
        ];

        $documentUuid = $this->createDocument([], $I);
        $endpoint = "{$this->endpoint}/{$documentUuid}";

        $I->sendPost($endpoint, $reportSettings);
        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);
        Assert::assertArrayHasKey('uuid', $responseData);
    }

    #[Depends('tryCreateReport')]
    public function tryGetReport(ApiTester $I): void
    {
        $reportUuid = $this->createReport([], $I);
        $report = $I->grabEntityFromRepository(Report::class, ['uuid' => $reportUuid]);

        $endpoint = "{$this->endpoint}/{$reportUuid}";
        $reportToken = $report->getToken()?->getValue();

        if (null === $reportToken)
            Assert::fail('Token creation failed.');

        $I->haveHttpHeader('X-Public-Token', $reportToken);

        $I->sendGet($endpoint);
        $I->seeResponseCodeIsSuccessful();
        $responseData = $this->tryFetchResponse($I);

        Assert::assertArrayHasKey('uuid', $responseData);
        Assert::assertEquals($reportUuid, $responseData['uuid']);
    }

    #[Depends('tryCreateReport')]
    public function tryPatchReport(ApiTester $I): void
    {
        $reportUuid = $this->createReport([], $I);
        $endpoint = "{$this->endpoint}/{$reportUuid}";

        $data = [ 'data' => ['new'] ];
        
        $I = $this->setBaseHeaders($I, $this->token);

        $I->sendPatch($endpoint, $data);
        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);

        Assert::assertEquals($data['data'], $responseData['data']);
    }

    #[Depends('tryCreateReport')]
    public function tryDeleteReport(ApiTester $I): void
    {
        $reportUuid = $this->createReport([], $I);

        $endpoint = "{$this->endpoint}/{$reportUuid}";

        $I = $this->setBaseHeaders($I, $this->token);
        $I->sendDelete($endpoint);

        $I->seeResponseCodeIsSuccessful();
        $I->dontSeeInRepository(Report::class, ['uuid' => $reportUuid]);
    }
    
    #[Depends('tryCreateReport')]
    public function tryAddLabelsToReport(ApiTester $I): void
    {
        $reportUuid = $this->createReport([], $I);
        $labelId = $this->createLabel([],$I);

        $endpoint = "{$this->endpoint}/label/{$reportUuid}";

        $I = $this->setBaseHeaders($I, $this->token);

        $I->sendPut($endpoint,[
            'labelIds' => [$labelId]
        ]);


        $I->seeResponseCodeIsSuccessful();

        $report = $I->grabEntityFromRepository(Report::class, ['uuid' =>  $reportUuid ]);
        $labels = $report->getLabels()->toArray();

        Assert::assertCount(1, $labels);
        Assert::assertEquals($labelId, $labels[0]->getId());
    }

    #[Depends('tryCreateReport')]
    public function tryAssignUserToReport(ApiTester $I): void
    {
        $reportUuid = $this->createReport([], $I);
        $userId = $this->createUser([],$I)['id'];

        $endpoint = "{$this->endpoint}/assign/{$reportUuid}";

        $I = $this->setBaseHeaders($I, $this->token);

        $I->sendPut($endpoint,[
            'userIds' => [$userId]
        ]);

        $I->seeResponseCodeIsSuccessful();

        $user = $I->grabEntityFromRepository(User::class, ['id' => $userId]);
        $report = $I->grabEntityFromRepository(Report::class, ['uuid' =>  $reportUuid ]);

        Assert::assertEquals($userId, $report->getAssignedUsers()->toArray()[0]->getId());
        Assert::assertEquals($reportUuid, $user->getAssignedReports()->toArray()[0]->getUuid());
    }
    
    #[Depends('tryCreateReport')]
    public function tryGetAssignedReportsInPagination(ApiTester $I): void
    {
        $reportUuid = $this->createReport([], $I);
        $user = $this->createUser([],$I);

        $endpoint = "{$this->endpoint}/assign/{$reportUuid}";

        $I = $this->setBaseHeaders($I, $this->token);

        $I->sendPut($endpoint,[
            'userIds' => [$user['id']]
        ]);

        $I->seeResponseCodeIsSuccessful();
        $endpoint = "{$this->endpoint}/paginate/1/1";

        $I = $this->setBaseHeaders($I, $user['token']);

        $I->sendPost($endpoint,[ 'filters' => ['type' => 'assessment']]);
        $I->seeResponseCodeIsSuccessful();
        $responseData = $this->tryFetchResponse($I);

        Assert::assertCount(1, $responseData);
    }

    #[Depends('tryCreateReport')]
    public function tryGetAllReports(ApiTester $I): void
    {
        $this->createReport([],$I);
        $this->createReport([],$I);

        $endpoint = "{$this->endpoint}/paginate/1/10";
       
        $I = $this->setBaseHeaders($I, $this->token);

        $I->sendPost($endpoint,[]);
        $I->seeResponseCodeIsSuccessful();

        $responseData = $this->tryFetchResponse($I);
        Assert::assertCount(2, $responseData);
  
        $I->sendPost($endpoint,[ 'filters' => ['type' => 'assessment']]);
        Assert::assertCount(2, $this->tryFetchResponse($I));

    }

}
