<?php

namespace App\Service;

use App\Constraint\RequestConstraints;
use App\Entity\User;
use App\Exception\LogicException;
use App\Exception\ResourceNotFoundException;
use App\Factory\TokenFactory;
use App\Factory\UserFactory;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use App\Trait\ServiceHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserService {
use ServiceHelper;
    public function __construct(
        private readonly UserRepository              $userRepository,
        private readonly RoleRepository              $roleRepository,
        private readonly EntityManagerInterface      $entityManager,
        private readonly UserPasswordHasherInterface $hasher,
    ) {}

    /**
     * Retrieves a user by ID
     * 
     * @param int $id User ID
     * @return User User entity
     * @throws ResourceNotFoundException When user cannot be found
     */
    public function get(int $id): User {
        return $this->userRepository->findById($id);
    }

    /**
     * Creates a new user
     *
     * Validates the input data, builds a new user entity with the specified role,
     * sets a token, and persists the entity to the database.
     *
     * @param array<string, mixed> $data User creation parameters
     * @return User The newly created user entity
     */
    public function create(array $data): User {
        $this->validateRequestData($data, RequestConstraints::userConstraintCreate());

        $user = UserFactory::build(
            $data,
            $this->roleRepository->findById($data['roleId']),
            $this->hasher,
        );
        $user->setToken(TokenFactory::build());
        $this->entityManager->persist($user);
        $this->entityManager->flush(); 
        return $user;
    }

    /**
     * Updates specific fields of a user
     * 
     * Validates the input data and updates the user's role, password or email.
     * 
     * 
     * @param int $id User ID
     * @param array<string, mixed> $data Fields to update
     * @return User The updated user entity
     */
    public function patch(int $id, array $data): User {
        $this->validateRequestData($data, RequestConstraints::userConstraintPatch());

        $user = $this->get($id);
        
        if (array_key_exists('roleId', $data)) {
            if ($data['roleId'] !== 1 &&
                $this->userRepository->countAdmins() === 1 &&
                $user->getRole()?->getId() === 1)
                throw new LogicException('At least one user with admin role is required');

            $user->setRole($this->roleRepository->findById($data['roleId']));
        }
        if (array_key_exists('password', $data))
            $user->setPassword($this->hasher->hashPassword($user, $data['password']));

        $user->setEmail($data['email'] ?? $user->getEmail());
        $user->setData($data['data'] ?? $user->getData());
    
        $this->entityManager->flush();
        return $user;
    }

    /**
     * Permanently deletes a user
     * 
     * Removes the user from the database.
     * 
     * @param int $id User ID
     * @return void
     */
    public function delete(int $id): void {
        $user = $this->get($id);
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }

    /**
     * Retrieves all users
     * 
     * @return array<int, User> List of all user entities
     */
    public function all(): array {
        return $this->userRepository->findAll();
    }

}