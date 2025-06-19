<?php
namespace App\Security;

use DateTimeImmutable;
use App\Entity\Permission;
use App\Entity\Role;
use App\Entity\User;
use App\Repository\TokenRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\AccessMapInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly AccessTokenHandler $accessTokenHandler,
        private readonly TokenRepository    $repository,
        private readonly EntityManager      $entityManager,
        private readonly CheckAuth          $authToken,
        private readonly AccessMapInterface $accessMap,
    ){}

    public function supports(Request $request): ?bool
    {
        return !empty($this->accessMap->getPatterns($request));
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $hasCookieToken = $request->cookies->has('AUTH_TOKEN');
        $hasHeaderToken = $request->headers->has('X-Public-Token');

        if ($hasHeaderToken) {
            try {
                $authInfoFromHeader = $this->getPermissionsFromHeader($request);
            } catch (AuthenticationException $e) {
                if (!$hasCookieToken) {
                    throw $e;
                }
            }
        }

        // Public Token should not be able to delete Document/Report.
        if (!$hasCookieToken && $request->isMethod('DELETE'))
            throw new AuthenticationException('Invalid authentication attempt');

        if ($hasCookieToken) {
            $authInfoFromCookie = $this->getPermissionsFromCookie($request);
            $authInfoFromHeader['permissions'] = array_merge(
                $authInfoFromHeader['permissions'] ?? [],
                $authInfoFromCookie['permissions']
            );
        }

        if ($hasCookieToken && $hasHeaderToken)
            $authInfoFromHeader['email'] ??= $authInfoFromCookie['email'];

        return new SelfValidatingPassport(
            new UserBadge($authInfoFromHeader['email'] ?? $authInfoFromCookie['email'] ?? 'Not Specified',
                fn(string $identifier): User => $this->generateUser($identifier, $authInfoFromHeader['permissions'] ?? [])
            )
        );
    }

    private function getPermissionsFromCookie(Request $request): array
    {
        $cookie = $request->cookies->get('AUTH_TOKEN');
        $cookieUser = $this->accessTokenHandler->getUserBadgeFrom($cookie)->getUser();

        $token = $this->repository->findByValue($cookie);
        $token->setLastUsedDate(new DateTimeImmutable());
        $this->entityManager->flush();

        return [
            'email'       => $cookieUser->getUserIdentifier(),
            'permissions' => $cookieUser->getRoles() ?? []
        ];
    }

    private function getPermissionsFromHeader(Request $request): ?array
    {
        $path = $request->getPathInfo();

        if (preg_match('#^/report/([a-zA-Z0-9\-]+)$#', $path, $matches))
            return $this->authToken->validate($request, $matches[1], 'Report');

        if (preg_match('#^/document/([a-zA-Z0-9\-]+)$#', $path, $matches) ||
            preg_match('#^/document/submit/([a-zA-Z0-9\-]+)$#', $path, $matches)
        ) return $this->authToken->validate($request, $matches[1], 'Document');

        return null;
    }

    private function generateUser(string $identifier, array $permissions): User
    {
        $user = new User();
        $user->setId(crc32($identifier) & 0x7FFFFFFF);
        $user->setEmail($identifier);
        $user->setRole((new Role())->setPermissions((new Permission())->setValue($permissions)));
        return $user;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new Response($exception->getMessage(), Response::HTTP_UNAUTHORIZED);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }
}