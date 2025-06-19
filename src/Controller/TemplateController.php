<?php

namespace App\Controller;

use App\Interface\CacheStorageInterface;
use App\Interface\LogInterface;
use App\Service\TemplateService;
use App\Trait\JsonResponseTrait;
use App\Transformer\TemplateTransformer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/template')]
final class TemplateController extends AbstractController {
 use JsonResponseTrait;
    private const UUID_REGEX_REQUIREMENT =  ['uuid' => '^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$'];

 public function __construct(
    private readonly TemplateService     $entityService,
    private readonly TemplateTransformer $transformer,
    private readonly LogInterface        $log,
    ) {}


    #[Route('/{page}/{limit}/{search}', name: 'template_paginate', requirements: ['page' => '\d+', 'limit' => '\d+' ], methods: ['GET'])]
    public function paginate(int $page, int $limit, string $search): Response{
        $template = $this->entityService->paginate($page, $limit, $search);
        $templates = $this->transformer->transformPaginate($template);
        return $this->responsePage($templates);
    }


    #[Route('/{uuid}', name: 'template_get', requirements: self::UUID_REGEX_REQUIREMENT, methods: ['GET'])]
    public function get(string $uuid, CacheStorageInterface $cache): Response{
        $template = $cache->get('AppEntityTemplate', $uuid);

        if (null === $template) {
            $template = $this->entityService->get($uuid);
            $templateData = $this->transformer->transform($template);
            $templateData["log"] = $this->log->getLogFrom($template);
            $cache->save($template, $templateData);

        }else $templateData = $template;

        return $this->responseOK($templateData);
    }

    #[Route('/{uuid}', name: 'template_patch', requirements: self::UUID_REGEX_REQUIREMENT, methods: ['PATCH'])]
    public function patch(string $uuid ,Request $request, CacheStorageInterface $cache): Response{
        $template = $this->entityService->patch($uuid, $request->toArray());
        $templateData = $this->transformer->transform($template);
        $templateData["log"] = $this->log->getLogFrom($template);

        $cache->save($template, $templateData);
        return $this->responseOK($templateData);
    }
 
    #[Route('/{uuid}', name: 'template_delete', requirements: self::UUID_REGEX_REQUIREMENT, methods: ['DELETE'])]
    public function delete(string $uuid ): Response{
        $this->entityService->delete($uuid);
        return $this->responseOK();
    }
    
    #[Route('', name: 'template_create', methods: ['POST'])]
    public function create(Request $request, CacheStorageInterface $cache): Response{
        $template = $this->entityService->create($request->toArray());
        $templateData = $this->transformer->transform($template);
        $templateData["log"] = $this->log->getLogFrom($template);

        $cache->save($template, $templateData);
        return $this->responseOK($templateData);
    }
    
}
