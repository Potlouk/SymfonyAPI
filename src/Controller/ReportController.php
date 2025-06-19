<?php

namespace App\Controller;

use App\Interface\CacheStorageInterface;
use App\Interface\LogInterface;
use App\Service\ReportService;
use App\Service\SettingService;
use App\Trait\JsonResponseTrait;
use App\Transformer\ReportTransformer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/report')]
final class ReportController extends AbstractController {
 use JsonResponseTrait;
 private const UUID_REGEX_REQUIREMENT = ['uuid' => '^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$'];
 
 public function __construct(
    private readonly ReportService     $entityService,
    private readonly ReportTransformer $transformer,
    private readonly LogInterface      $log,
    ) {}

    #[Route('/paginate/{page}/{limit}', name: 'report_paginate', requirements: ['page' => '\d+', 'limit' => '\d+' ], methods: ['POST'])]
    public function paginate(int $page, int $limit, Request $request, SettingService $settings): Response {
        $reports = $this->entityService->paginate($limit, $page, $request->toArray());
        $reports = $this->transformer->transformPagination($reports, $settings->get());
        return $this->responsePage($reports);
    }

    #[Route('/label/{uuid}', name: 'report_put_label', requirements: self::UUID_REGEX_REQUIREMENT, methods: ['PUT'])]
    public function editLabels(string $uuid, Request $request): Response{
        $this->entityService->setLabels($uuid, $request->toArray());
        return $this->responseOK();
    }

    #[Route('/assign/{uuid}', name: 'report_assign_user', requirements: self::UUID_REGEX_REQUIREMENT, methods: ['PUT'])]
    public function assign(string $uuid, Request $request): Response {
        $this->entityService->assignUsers($uuid, $request->toArray());
        return $this->responseOK();
    }

    #[Route('/{uuid}', name: 'report_delete', requirements: self::UUID_REGEX_REQUIREMENT, methods: ['DELETE'])]
    public function delete(string $uuid): Response{
        $this->entityService->delete($uuid);
        return $this->responseOK();
    }

    #[Route('/{uuid}', name: 'report_get', requirements: self::UUID_REGEX_REQUIREMENT, methods: ['GET'])]
    public function get(string $uuid, CacheStorageInterface $cache, SettingService $settings): Response{
        $report = $cache->get('AppEntityReport', $uuid);

        if (null === $report) {
            $report = $this->entityService->get($uuid);
            $reportData = $this->transformer->transform($report, $settings->get());
            $reportData["log"] = $this->log->getLogFrom($report);
            $cache->save($report, $reportData);
        }else $reportData = $report;

        return $this->responseOK($reportData);
    }
    
    #[Route('/{uuid}', name: 'report_patch', requirements: self::UUID_REGEX_REQUIREMENT, methods: ['PATCH'])]
    public function patch(string $uuid, Request $request, CacheStorageInterface $cache, SettingService $settings): Response{
        $report = $this->entityService->patch($uuid, $request->toArray());
        $reportData = $this->transformer->transform($report, $settings->get());
        $reportData["log"] = $this->log->getLogFrom($report);
        
        $cache->save($report, $reportData);
        
        return $this->responseOK($reportData);
    }
    
    #[Route('/{uuid}', name: 'report_create', requirements: self::UUID_REGEX_REQUIREMENT, methods: ['POST'])]
    public function create(string $uuid, Request $request, CacheStorageInterface $cache, SettingService $settings): Response{
        $report = $this->entityService->create($uuid,$request->toArray());
        $reportData = $this->transformer->transform($report, $settings->get());
        $reportData["log"] = $this->log->getLogFrom($report);
        $cache->save($report, $reportData);

        return $this->responseOK($reportData);
    }
}
