<?php

namespace App\Factory;

use App\Entity\Role;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFactory {

    /**
     * @param array<string, mixed> $data 
     */
    public static function build(array $data, Role $role , UserPasswordHasherInterface $hasher): User {
        $user = (new User())
        ->setRole($role)
        ->setEmail($data['email'])
        ->setData($data['data'] ?? []);

        $user->setPassword(
            $hasher->hashPassword(
                $user,
                $data['password']
            )
        );
        return $user;
    }
}