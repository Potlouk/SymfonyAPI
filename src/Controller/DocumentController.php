<?php

namespace App\Controller;

use App\Interface\CacheStorageInterface;
use App\Interface\LogInterface;
use App\Service\DocumentService;
use App\Service\SettingService;
use App\Trait\JsonResponseTrait;
use App\Transformer\DocumentTransformer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/document')]
final class DocumentController extends AbstractController {
 use JsonResponseTrait;
 private const UUID_REGEX_REQUIREMENT = ['uuid' => '^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$'];

 public function __construct(
    private readonly DocumentService     $entityService,
    private readonly DocumentTransformer $transformer,
    private readonly LogInterface        $log,
    ) {}

    #[Route('/paginate/{page}/{limit}', name: 'document_paginate', requirements: ['page' => '\d+', 'limit' => '\d+' ], methods: ['POST'])]
    public function paginate(int $page, int $limit, Request $request, SettingService $settings): Response {
        $documents = $this->entityService->paginate($page, $limit, $request->toArray());
        $documents = $this->transformer->transformPagination($documents, $settings->get());
        return $this->responsePage($documents);
    }

    #[Route('/label/{uuid}', name: 'document_put_label', requirements: self::UUID_REGEX_REQUIREMENT, methods: ['PUT'])]
    public function editLabels(string $uuid, Request $request): Response{
        $this->entityService->setLabels($uuid, $request->toArray());
        return $this->responseOK();
    }

    #[Route('/submit/{uuid}', name: 'document_submit', requirements: self::UUID_REGEX_REQUIREMENT, methods: ['POST'])]
    public function submit(string $uuid, Request $request): Response {
        $this->entityService->submit($uuid, $request->toArray());
        return $this->responseOK();
    }

    #[Route('/assign/{uuid}', name: 'document_assign_user', requirements: self::UUID_REGEX_REQUIREMENT, methods: ['PUT'])]
    public function assign(string $uuid, Request $request): Response {
        $this->entityService->assignUsers($uuid, $request->toArray());
        return $this->responseOK();
    }

    #[Route('/report/{uuid}', name: 'document_create_from_report', requirements: self::UUID_REGEX_REQUIREMENT, methods: ['POST'])]
    public function createFromReport(string $uuid, Request $request, SettingService $settings): Response {
        $document = $this->entityService->crateFromReport($uuid, $request->toArray());
        $documentData = $this->transformer->transform($document, $settings->get());
        return $this->responseOK($documentData);
    }

    #[Route('/images/{uuid}', name: 'document_upload_images', requirements: self::UUID_REGEX_REQUIREMENT, methods: ['POST'])]
    public function uploadImagesToDocument(string $uuid, Request $request): Response {
         $this->entityService->setImages($uuid, $request->toArray(), $request->files->all());
         return $this->responseOK();
    }

    #[Route('/move/{uuid}', name: 'document_move', requirements: self::UUID_REGEX_REQUIREMENT, methods: ['PATCH'])]
    public function changeProperty(string $uuid, Request $request): Response {
        $this->entityService->changeProperty($uuid, $request->toArray());
        return $this->responseOK();
    }

     #[Route('/share/{uuid}', name: 'document_share', requirements: self::UUID_REGEX_REQUIREMENT, methods: ['POST'])]
     public function share(string $uuid, Request $request): Response {
         $this->entityService->share($uuid, $request->toArray());
         return $this->responseOK();
    }

    #[Route('/unshare/{uuid}', name: 'document_unshare', requirements: self::UUID_REGEX_REQUIREMENT, methods: ['PUT'])]
    public function unshare(string $uuid): Response {
        $this->entityService->unshare($uuid);
        return $this->responseOK();
   }

     #[Route('/reopen/{uuid}', name: 'document_reopen', requirements: self::UUID_REGEX_REQUIREMENT, methods: ['PUT'])]
     public function reopen(string $uuid): Response {
         $this->entityService->reopen($uuid);
         return $this->responseOK();
    }

    #[Route('/{uuid}', name: 'document_get', requirements: self::UUID_REGEX_REQUIREMENT, methods: ['GET'])]
    public function get(string $uuid, CacheStorageInterface $cache, SettingService $settings): Response{
        $document = $cache->get('AppEntityDocument', $uuid);

        if (null === $document) {
            $document = $this->entityService->get($uuid);
            $documentData = $this->transformer->transform($document, $settings->get());
            $documentData["log"] = $this->log->getLogFrom($document);
            $cache->save($document, $documentData);
        }else $documentData = $document;
        
        return $this->responseOK($documentData);
    }

    #[Route('/{uuid}', name: 'document_patch', requirements: self::UUID_REGEX_REQUIREMENT, methods: ['PATCH'])]
    public function patch(string $uuid, Request $request, CacheStorageInterface $cache, SettingService $settings): Response{
        $document = $this->entityService->patch($uuid,$request->toArray());
        $documentData = $this->transformer->transform($document, $settings->get());
        $documentData["log"] = $this->log->getLogFrom($document);
        $cache->save($document, $documentData);

        return $this->responseOK($documentData);
    }

    #[Route('/{uuid}', name: 'document_delete', requirements: self::UUID_REGEX_REQUIREMENT, methods: ['DELETE'])]
    public function delete(string $uuid): Response{
        $this->entityService->delete($uuid);
        return $this->responseOK();
    }

    #[Route('', name: 'document_create', methods: ['POST'])]
    public function create(Request $request, SettingService $settings): Response{
        $document = $this->entityService->create($request->toArray());
        $documentData = $this->transformer->transform($document, $settings->get());
        $documentData["log"] = $this->log->getLogFrom($document);
        return $this->responseOK($documentData);
    }

}
