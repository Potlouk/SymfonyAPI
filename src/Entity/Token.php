<?php

namespace App\Entity;

use App\Repository\TokenRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TokenRepository::class)]
class Token
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $value = null;

    #[ORM\OneToOne(targetEntity: Permission::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Permission $permissions = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $expiry_date = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $valid_date = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $last_use_date = null;

    #[ORM\Column]
    private ?bool $active = null;

    #[ORM\Column]
    private ?array $receiver = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function getPermissions(): ?Permission
    {
        return $this->permissions;
    }

    public function setPermissions(?Permission $permissions): static
    {
        $this->permissions = $permissions;
        return $this;
    }

    public function getLastUsedDate(): ?DateTimeImmutable
    {
        return $this->last_use_date;
    }

    public function setLastUsedDate(?DateTimeImmutable $last_use_date): static
    {
        $this->last_use_date = $last_use_date;
        return $this;
    }

    public function getValidDate(): ?DateTimeImmutable
    {
        return $this->valid_date;
    }

    public function setValidDate(?DateTimeImmutable $valid_date): static
    {
        $this->valid_date = $valid_date;
        return $this;
    }

    public function getReceiver(): array
    {
        return $this->receiver;
    }

    public function getEmail(): ?string
    {
        if (array_key_exists('email',$this->receiver))
        return $this->receiver['email'];
         
        return null;
    }

    public function getFullName(): ?string
    {
        if (array_key_exists('fullName',$this->receiver))
        return $this->receiver['fullName'];
        
        return null;
    }

    public function getNotes(): ?string
    {
        if (array_key_exists('notes',$this->receiver))
        return $this->receiver['notes'];
        
        return null;
    }

    public function setReceiver(array $data): static
    {
        $this->receiver = $data;
        return $this;
    }

    public function getExpiryDate(): ?DateTimeImmutable
    {
        return $this->expiry_date;
    }

    public function setExpiryDate(?DateTimeImmutable $expiry_date): static
    {
        $this->expiry_date = $expiry_date;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }
}
