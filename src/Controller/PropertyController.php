<?php

namespace App\Controller;

use App\Interface\CacheStorageInterface;
use App\Service\PropertyService;
use App\Trait\JsonResponseTrait;
use App\Transformer\PropertyTransformer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/property')]
final class PropertyController extends AbstractController {
 use JsonResponseTrait;

    public function __construct(
        private readonly PropertyService     $entityService,
        private readonly PropertyTransformer $transformer
    ) {}

    #[Route('/{page}/{limit}/{archived}/{search}', name: 'property_paginate', requirements: ['page' => '\d+', 'limit' => '\d+' ], methods: ['GET'])]
    public function paginate(int $page, int $limit, string $archived, string $search): Response{
        $properties = $this->entityService->paginate($page, $limit, $archived, $search);
        $properties = $this->transformer->transformPaginate($properties);
        return $this->responsePage($properties);
    }
    
    #[Route('/{id}', name: 'property_get', requirements: ['id' => '\d+' ], methods: ['GET'])]
    public function get(int $id, CacheStorageInterface $cache): Response{

        $property = $cache->get('AppEntityProperty', $id);

        if (null === $property) {
            $property = $this->entityService->get($id);
            $propertyData = $this->transformer->transform($property);
            $cache->save($property, $propertyData);

        }else $propertyData = $property;

        return $this->responseOK($propertyData);
    }

    #[Route('/{id}', name: 'property_patch', requirements: ['id' => '\d+' ], methods: ['PATCH'])]
    public function patch(int $id, Request $request, CacheStorageInterface $cache): Response{
        $property = $this->entityService->patch($id,$request->toArray());
        $propertyData = $this->transformer->transform($property);
        $cache->save($property, $propertyData);
        return $this->responseOK($propertyData);
    }

    #[Route('/{id}', name: 'property_delete', requirements: ['id' => '\d+' ], methods: ['DELETE'])]
    public function delete(int $id ): Response{
        $this->entityService->delete($id);
        return $this->responseOK();
    }
    
    #[Route('', name: 'property_create', methods: ['POST'])]
    public function create(Request $request, CacheStorageInterface $cache): Response{
        $property = $this->entityService->create($request->toArray());
        $propertyData = $this->transformer->transform($property);
        $cache->save($property, $propertyData);
        return $this->responseOK($propertyData);
    }
}
