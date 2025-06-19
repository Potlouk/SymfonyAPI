<?php

namespace App\Entity;

use App\Repository\TemplateRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Ramsey\Uuid\UuidInterface;
use Ramsey\Uuid\Uuid;

#[ORM\Entity(repositoryClass: TemplateRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Template
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private ?string $name = null;

    #[ORM\Column]
    private array $data = [];

    #[ORM\Column]
    private array $info = [];

    #[Column(type: "uuid", unique: true)]
    private ?UuidInterface $uuid = null;

    /**
     * @var Collection<int, CULog>
     */
    #[ORM\OneToMany(targetEntity: CULog::class, mappedBy: 'template', cascade: ['persist','remove'], orphanRemoval: true)]
    private Collection $log;

    #[ORM\Column]
    private ?DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?DateTimeImmutable $updated_at = null;

    public function __construct()
    {
        $this->log = new ArrayCollection();
    }

    public function setId(int $id): static
    {
         $this->id = $id;
         return $this;
    }

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

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function getInfo(): array
    {
        return $this->info;
    }

    public function setInfo(array $info): static
    {
        $this->info = $info;
        return $this;
    }

    public function getUuid(): ?UuidInterface
    {
        return $this->uuid;
    }

    public function setUuid(UuidInterface $uuid): static
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    #[ORM\PrePersist]
    public function genUuid(): void {
        $this->uuid ??= Uuid::uuid7();
    }

    #[ORM\PrePersist]
    public function setTimestamps(): void
    {
        $this->created_at = new DateTimeImmutable();
        $this->updated_at = new DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updated_at = new DateTimeImmutable();
    }
}
