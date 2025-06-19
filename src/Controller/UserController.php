<?php

namespace App\Controller;

use App\Interface\CacheStorageInterface;
use App\Security\CheckAuth;
use App\Service\UserService;
use App\Trait\JsonResponseTrait;
use App\Transformer\UserTransformer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user')]
final class UserController extends AbstractController {
 use JsonResponseTrait;

    public function __construct(
        private readonly UserService     $entityService,
        private readonly UserTransformer $transformer
    ) {}

    #[Route('/get/all', name: 'user_all', methods: ['GET'])]
    public function all(): Response{
        $users = $this->entityService->all();
        $users = $this->transformer->transformAll($users);
        return $this->responseOK($users);
    }

    #[Route('', name: 'user_get_by_token', methods: ['GET'])]
    public function getBT(CacheStorageInterface $cache, CheckAuth $authToken): Response{
        $userId = $authToken->getUserEmailFromToken();
        return $this->get($userId, $cache, $authToken);
    }

    #[Route('/{id}', name: 'user_get_by_id', requirements: ['id' => '\d+' ], methods: ['GET'])]
    public function get(int $id, CacheStorageInterface $cache, CheckAuth $authToken): Response{
        $authToken->validateUserEditOnlyItself($id,'ROLE_GET_USER');
        $user = $cache->get('AppEntityUser', $id);
        
        if (null === $user) {
            $user = $this->entityService->get($id);
            $userData = $this->transformer->transform($user);
            $cache->save($user, $userData);
        }else $userData = $user;

        return $this->responseOK($userData);
    }

    #[Route('/{id}', name: 'user_patch', requirements: ['id' => '\d+' ], methods: ['PATCH'])]
    public function patch(int $id, Request $request, CacheStorageInterface $cache, CheckAuth $authToken): Response{
        $authToken->validateUserEditOnlyItself($id,'ROLE_GET_USER');

       $user = $this->entityService->patch($id,$request->toArray());
       $userData = $this->transformer->transform($user);
       $cache->save($user, $userData);

        return $this->responseOK($userData);
    }

    #[Route('/{id}', name: 'user_delete', requirements: ['id' => '\d+' ], methods: ['DELETE'])]
    public function delete(int $id): Response{
        $this->entityService->delete($id);
        return $this->responseOK();
    }

    #[Route('', name: 'user_create', methods: ['POST'])]
    public function create(Request $request, CacheStorageInterface $cache): Response{
        $user = $this->entityService->create($request->toArray());
        $userData = $this->transformer->transform($user);
        $cache->save($user, $userData);

        return $this->responseOK($userData);
    }

}
