<?php

namespace App\Controller;

use App\Interface\CacheStorageInterface;
use App\Service\LabelService;
use App\Trait\JsonResponseTrait;
use App\Transformer\LabelTransformer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/label')]
final class LabelController extends AbstractController {
 use JsonResponseTrait;

 public function __construct(
    private readonly LabelService     $entityService,
    private readonly LabelTransformer $transformer,
    ) {}

    #[Route('/get/all', name: 'label_all', methods: ['GET'])]
    public function all(): Response{
        $label = $this->entityService->all();
        $labelData = $this->transformer->transformAll($label);
        return $this->responseOK($labelData);
    }

    #[Route('/{id}', name: 'label_put', requirements: ['id' => '\d+' ], methods: ['PUT'])]
    public function put(int $id, Request $request, CacheStorageInterface $cache): Response{
        $label = $this->entityService->put($id, $request->toArray());
        $labelData = $this->transformer->transform($label);
        $cache->save($label, $labelData);
        return $this->responseOK($labelData);
    }
 
    #[Route('/{id}', name: 'label_delete', requirements: ['id' => '\d+' ], methods: ['DELETE'])]
    public function delete(int $id): Response{
        $this->entityService->delete($id);
        return $this->responseOK();
    }

    #[Route('', name: 'label_post', methods: ['POST'])]
    public function create(Request $request, CacheStorageInterface $cache): Response{
        $label = $this->entityService->create($request->toArray());
        $labelData = $this->transformer->transform($label);
        $cache->save($label, $labelData);
        return $this->responseOK($labelData);
    }
  
}
