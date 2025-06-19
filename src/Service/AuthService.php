<?php

namespace App\Service;

use App\Entity\User;
use App\Exception\LogicException;
use App\Exception\RequestBodyException;
use App\Repository\UserRepository;
use App\Trait\ServiceHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

final class AuthService {
    use ServiceHelper;
    public function __construct(
        private readonly UserRepository              $userRepository,
        private readonly Security                    $security,
        private readonly EntityManagerInterface      $entityManager,
        private readonly UserPasswordHasherInterface $hasher,
    ) {}

    /**
     * Authenticates a user based on email and password
     *
     * Validates the input credentials, finds the user by email,
     * and verifies the password.
     *
     * @param array<string, mixed> $data Login credentials (email, password)
     * @return User Authenticated user entity
     * @throws RequestBodyException If credentials are invalid
     */
    public function login(array $data): User {
        $validator = Validation::createValidator();

        $constraint = new Assert\Collection([
            'email' => new Assert\NotBlank(),
            'password'=> new Assert\NotBlank(),
        ]);

        $violations = $validator->validate($data,  $constraint);

        if (count($violations) > 0)
            throw new RequestBodyException($this->extractValidatorErrors($violations));

        $user = $this->userRepository->findByEmail($data['email']);

        if (!$this->hasher->isPasswordValid($user, $data['password']))
            throw new UnauthorizedHttpException('Basic realm="Access to the API"','Invalid credentials');

        return $user;
    }

    /**
     * Finds user based on passed auth cookie
     *
     *
     * @param  mixed $cookie auth cookie
     * @return User Authenticated user entity
     * @throws LogicException If auth token has no value or is missing
     */
    public function logout(mixed $cookie): User {
        if (false === $cookie)
            throw new LogicException('Authentication token not passed');

        $userEmail = $this->security->getUser()?->getUserIdentifier();
        return $this->userRepository->findByEmail($userEmail);
    }

}