<?php

namespace App\Service;

use App\Constraint\RequestConstraints;
use App\Entity\Permission;
use App\Entity\Role;
use App\Exception\LogicException;
use App\Repository\RoleRepository;
use App\Trait\ServiceHelper;
use Doctrine\ORM\EntityManagerInterface;

final class RoleService {
use ServiceHelper;
    public function __construct(
        private readonly RoleRepository         $roleRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    /**
     * Retrieves a role by ID
     * 
     * @param int $id Role ID
     * @return Role The requested role entity
     */
    public function get(int $id): Role {
        return $this->roleRepository->findById($id);
    }

    /**
     * Creates a new role
     * 
     * Validates the input data and creates a new role with the specified
     * name and permissions.
     * 
     * @param array<string, mixed> $data Role creation parameters
     * @return Role The newly created role entity
     */
    public function create(array $data): Role {
        $this->validateRequestData($data, RequestConstraints::roleConstraintCreate());

        if ($this->roleRepository->isNameExisting($data['name']))
            throw new LogicException('Role with this name already exists');

        $role = (new Role())
        ->setName($data['name'])
        ->setTreeIds($data['treeIds'])
        ->setPermissions( 
            (new Permission())->setValue($data['permissions'])
        );

        $this->entityManager->persist($role);
        $this->entityManager->flush(); 
        return $role;
     }

    /**
     * Updates specific fields of a role
     * 
     * Validates the input data and updates the role's name or permissions
     * based on the provided parameters.
     * 
     * @param int $id Role ID
     * @param array<string, mixed> $data Fields to update
     * @return Role The updated role entity
     */
     public function patch(int $id, array $data): Role {
        $this->validateRequestData($data, RequestConstraints::roleConstraintPatch());
        $role = $this->get($id);

        if (array_key_exists('name', $data)){
            if ($data['name'] !== $role->getName() && $this->roleRepository->isNameExisting($data['name']))
                throw new LogicException('Role with this name already exists');

            $role->setName($data['name']);
        }

        if (array_key_exists('treeIds',$data))
        $role->setTreeIds($data['treeIds']);
        if (array_key_exists('permissions',$data))
        $role->getPermissions()?->setValue(
            $data['permissions']
        );
       
        $this->entityManager->flush();
        return $role;
      }
 
      /**
       * Permanently deletes a role
       * 
       * Removes the role from the database.
       * 
       * @param int $id Role ID
       * @return void
       */
      public function delete(int $id): void {
        $role = $this->get($id);
        $this->entityManager->remove($role);
        $this->entityManager->flush();
      }

     /**
      * Retrieves all roles
      * 
      * @return array<int, Role> List of all role entities
      */
      public function all(): array {
         return $this->roleRepository->findAll();
      }
}