<?php

namespace App\Security;

use App\Entity\User;
use App\Interface\CacheStorageInterface;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final readonly class AccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private  UserRepository        $repository,
        private  CacheStorageInterface $cache,
    ) {}

    /**
     * Retrieves the user badge based on the access token.
     *
     * @param string $accessToken
     * @return UserBadge
     * @throws BadCredentialsException if no valid user is found.
     */
    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        $data = $this->cache->get('Token', $accessToken);
        
        if (null === $data) {
            /** @var User|null $user */
            $user = $this->repository
                ->createQueryBuilder('u')
                ->innerJoin('u.token', 't')
                ->andWhere('t.value = :token')
                ->setParameter('token', $accessToken)
                ->getQuery()
                ->getOneOrNullResult();
            
            if (null === $user)
            throw new BadCredentialsException('Invalid Token');

            return new UserBadge($user->getEmail(), function(string $identifier) use ($user) {
                return $user;
            });
        } 

        $this->cache->deleteKey("AppEntityUser{$data['userId']}");
        return new UserBadge($data['email'], function(string $identifier) {
            return $this->repository->findOneBy(['email' => $identifier]);
        });
        
    }
}