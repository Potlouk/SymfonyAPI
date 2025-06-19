<?php

namespace App\Controller;

use App\Interface\CacheStorageInterface;
use App\Service\AuthService;
use App\Trait\JsonResponseTrait;
use App\Transformer\UserTransformer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PublicController extends AbstractController {
 use JsonResponseTrait;

 public function __construct(
    private readonly AuthService     $entityService,
    private readonly UserTransformer $transformer
    ) {}

    #[Route('/login', name: 'login_user', methods: ['POST'])]
    public function loginUser(Request $request, CacheStorageInterface $cache): Response {
        $user = $this->entityService->login($request->toArray());
        $tokenValue = $user->getToken()?->getValue();
        $cache->save("Token{$tokenValue}", [ "value" =>  $tokenValue , "email" => $user->getEmail(), 'userId' => $user->getId() ] );
        return $this->responseCookie('AUTH_TOKEN',  $tokenValue );
    }

    #[Route('/logout', name: 'logout_user', methods: ['POST'])]
    public function logoutUser(Request $request, CacheStorageInterface $cache): Response {
        $user = $this->entityService->logout($request->cookies->get('AUTH_TOKEN'));
        $tokenValue = $user->getToken()?->getValue();
        $cache->deleteKey("Token{$tokenValue}");
        return $this->removeCookie('AUTH_TOKEN');
    }
    
}
