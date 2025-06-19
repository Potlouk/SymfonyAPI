<?php

namespace App\Controller;

use App\Interface\CacheStorageInterface;
use App\Service\RoleService;
use App\Trait\JsonResponseTrait;
use App\Transformer\RoleTransformer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/role')]
final class RoleController extends AbstractController {
 use JsonResponseTrait;

 public function __construct(
    private readonly RoleService     $entityService,
    private readonly RoleTransformer $transformer
    ) {}

    #[Route('/get/all', name: 'role_all', methods: ['GET'])]
    public function all(): Response{
       $roles = $this->entityService->all();
       $roles = $this->transformer->transformAll($roles);
       return $this->responseOK($roles);
    }

    #[Route('/{id}', name: 'role_get', requirements: ['id' => '\d+' ], methods: ['GET'])]
    public function get(int $id, CacheStorageInterface $cache): Response{
        $role = $cache->get('AppEntityRole', $id);

        if (null === $role) {
            $role = $this->entityService->get($id);
            $roleData = $this->transformer->transform($role);
            $cache->save($role, $roleData);
        }else $roleData = $role;
        
        return $this->responseOK($roleData);
    }

    #[Route('/{id}', name: 'role_patch', requirements: ['id' => '\d+' ], methods: ['PATCH'])]
    public function patch(int $id ,Request $request, CacheStorageInterface $cache): Response{
        $role = $this->entityService->patch($id, $request->toArray());
        $roleData = $this->transformer->transform($role);
        $cache->save($role, $roleData);
        return $this->responseOK($roleData);
    }

    #[Route('/{id}', name: 'role_delete', requirements: ['id' => '\d+' ], methods: ['DELETE'])]
    public function delete(int $id): Response{
        $this->entityService->delete($id);
        return $this->responseOK();
    }

    #[Route('', name: 'role_post', methods: ['POST'])]
    public function create(Request $request, CacheStorageInterface $cache): Response{
        $role = $this->entityService->create($request->toArray());
        $roleData = $this->transformer->transform($role);
        $cache->save($role, $roleData);
        return $this->responseOK($roleData);
    }
}
