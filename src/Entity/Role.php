<?php

namespace App\Entity;

use App\Repository\RoleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoleRepository::class)]
class Role
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 16)]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?array $treeIds = null;

    #[ORM\OneToOne(targetEntity: Permission::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Permission $permissions = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getPermissions(): ?Permission
    {
        return $this->permissions;
    }

    public function setPermissions(Permission $permissions): static
    {
        $this->permissions = $permissions;
        return $this;
    }

    public function getTreeIds(): ?array
    {
        return $this->treeIds;
    }

    public function setTreeIds(?array $treeIds): static
    {
        $this->treeIds = $treeIds;
        return $this;
    }
}
