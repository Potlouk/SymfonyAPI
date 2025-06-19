<?php

namespace App\Entity;

use App\Repository\ReportRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Ramsey\Uuid\UuidInterface;
use Ramsey\Uuid\Uuid;


#[ORM\Entity(repositoryClass: ReportRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Report
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column]
    private array $data = [];

    #[ORM\Column]
    private array $info = [];

    #[Column(type: "uuid", unique: true)]
    private ?UuidInterface $uuid = null;

    #[ORM\Column]
    private ?DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?DateTimeImmutable $updated_at = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'assignedReports')]
    private Collection $assignedUsers;

    /**
     * @var Collection<int, CULog>
     */
    #[ORM\OneToMany(targetEntity: CULog::class, mappedBy: 'report', cascade: ['persist','remove'], orphanRemoval: true)]
    private Collection $log;

    #[ORM\OneToOne(cascade: ['persist','remove'], orphanRemoval: true)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Token $Token = null;

    #[ORM\ManyToOne(targetEntity: Property::class, cascade: ['persist'], inversedBy: 'reports')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Property $property = null;

    #[ORM\Column(length: 32)]
    private ?string $type = null;

    private ?Template $template = null;

    /**
     * @var Collection<int, Label>
     */
    #[ORM\ManyToMany(targetEntity: Label::class, cascade: ['persist'])]
    private Collection $labels;

    #[ORM\ManyToOne(targetEntity: Document::class, inversedBy: 'reports')]
    #[ORM\JoinColumn(name: "document_id", referencedColumnName: "id",  nullable: true, onDelete: "SET NULL")]
    private ?Document $document = null;

    public function __construct()
    {
        $this->assignedUsers = new ArrayCollection();
        $this->log = new ArrayCollection();
        $this->template = $this->getTemplate();
        $this->labels = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;
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

    public function getName(): ?string
    {
        return $this->info['name'] ?? null;
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

    public function removeProperty(Property $property): static
    {
        if (null === $this->property)
            $property->removeReport($this);

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getAssignedUsers(): Collection
    {
        return $this->assignedUsers;
    }

    public function addAssignedUser(User $assignedUser): static
    {
        if (!$this->assignedUsers->contains($assignedUser)) {
            $this->assignedUsers->add($assignedUser);
            $assignedUser->addAssignedReport($this);
        }
        return $this;
    }

    public function removeAssignedUser(User $assignedUser): static
    {
        if ($this->assignedUsers->removeElement($assignedUser))
            $assignedUser->removeAssignedReport($this);

        return $this;
    }

    public function clearAssignedUsers(): static
    {
        foreach ($this->assignedUsers as $user) 
        $user->removeAssignedReport($this); 
        
        $this->assignedUsers->clear();
        return $this;
    }

    /**
     * @return Collection<int, CULog>
     */
    public function getLog(): Collection
    {
        return $this->log;
    }

    public function addLog(CULog $log): static
    {
        if (!$this->log->contains($log)) {
            $this->log->add($log);
            $log->setReport($this);
        }
        return $this;
    }

    public function removeLog(CULog $log): static
    {
        if ($this->log->removeElement($log) && $log->getReport() === $this)
            $log->setReport(null);

        return $this;
    }

    public function getToken(): ?Token
    {
        return $this->Token;
    }

    public function setToken(?Token $Token): static
    {
        $this->Token = $Token;
        return $this;
    }

    public function getProperty(): ?Property
    {
        return $this->property;
    }

    public function setProperty(?Property $property): static
    {
        $this->property = $property;
        $property?->addReport($this);
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    #[ORM\PrePersist]
    public function genUuid(): void
    {
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

    /**
     * @return Collection<int, Label>
     */
    public function getLabels(): Collection
    {
        return $this->labels;
    }

    public function addLabel(Label $label): static
    {
        if (!$this->labels->contains($label)) {
            $this->labels->add($label);
        }
        return $this;
    }

    public function removeLabel(Label $label): static
    {
        $this->labels->removeElement($label);
        return $this;
    }

    public function clearLabels(): static
    {
        $this->labels->clear();
        return $this;
    }

    public function getTemplate(): ?Template
    {
        return $this->template;
    }

    public function setTemplate(?Template $template): static
    {
        $this->template = $template;
        return $this;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(?Document $document): static
    {
        $this->document = $document;
        return $this;
    }
}
