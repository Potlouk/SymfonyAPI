<?php

namespace App\Entity;

use App\Repository\DocumentRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[Column(type: "uuid", unique: true)]
    private ?UuidInterface $uuid = null;

    #[ORM\Column]
    private array $data = [];

    #[ORM\Column]
    private array $info = [];

    #[ORM\Column(length: 16)]
    private ?string $type = null;

    #[ORM\ManyToOne(targetEntity: Property::class, cascade: ['persist'], inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Property $property = null;

    #[ORM\Column]
    private ?DateTimeImmutable $created_at = null;

    #[ORM\Column]
    private ?DateTimeImmutable $updated_at = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'assignedDocuments')]
    private Collection $assignedUsers;

    /**
     * @var Collection<int, CULog>
     */
    #[ORM\OneToMany(targetEntity: CULog::class, mappedBy: 'document', cascade: ['persist','remove'], orphanRemoval: true)]
    private Collection $log;

    #[ORM\OneToOne(cascade: ['persist','remove'] , orphanRemoval: true)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Token $token = null;

    #[ORM\Column(length: 16)]
    private ?string $status = null;

    #[ORM\ManyToOne(targetEntity: Template::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    private ?Template $template = null;

    /**
     * @var Collection<int, Label>
     */
    #[ORM\ManyToMany(targetEntity: Label::class, cascade: ['persist'])]
    private Collection $labels;

    /**
     * @var Collection<int, Report>
     */
    #[ORM\OneToMany(targetEntity: Report::class, mappedBy: 'document', cascade: ['persist','remove'])]
    private Collection $reports;

    #[ORM\ManyToOne(targetEntity: Report::class)]
    #[ORM\JoinColumn(name: "made_from_report_id", referencedColumnName: "id", onDelete: "SET NULL")]
    private ?Report $madeFromReport = null;

    public function __construct()
    {
        $this->labels = new ArrayCollection();
        $this->assignedUsers = new ArrayCollection();
        $this->log = new ArrayCollection();
        $this->reports = new ArrayCollection();
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

    public function getProperty(): ?Property
    {
        return $this->property;
    }

    public function setProperty(?Property $property): static
    {
        $this->property = $property;
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
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
            $log->setDocument($this);
        }
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }
    
    public function clearLabels(): static
    {
        $this->labels->clear();
        return $this;
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
        if (!$this->labels->contains($label))
            $this->labels->add($label);

        return $this;
    }

    public function removeLabel(Label $label): static
    {
        $this->labels->removeElement($label);
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
    public function updateTimestamp(): void {
        $this->updated_at = new DateTimeImmutable();
    }
    
    public function clearAssignedUsers(): static
    {
        foreach ($this->assignedUsers as $user) 
        $user->removeAssignedDocument($this); 
        
        $this->assignedUsers->clear();
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
            $assignedUser->addAssignedDocument($this); 
        }
        return $this;
    }

    public function removeAssignedUser(User $assignedUser): static
    {
        if ($this->assignedUsers->removeElement($assignedUser))
            $assignedUser->removeAssignedDocument($this);

        return $this;
    }

    public function removeLog(CULog $log): static
    {
        if ($this->log->removeElement($log) && $log->getDocument() === $this)
            $log->setDocument(null);

        return $this;
    }

    public function getMadeFromReport(): ?Report
    {
        return $this->madeFromReport;
    }

    public function setMadeFromReport(?Report $report): static
    {
        $this->madeFromReport = $report;
        return $this;
    }

    public function getToken(): ?Token
    {
        return $this->token;
    }

    public function setToken(?Token $token): static
    {
        $this->token = $token;
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

    public function removeReport(?Report $report): static
    {
        if (null !== $report && $this->reports->contains($report)) {
            $this->reports->removeElement($report);
            if ($report->getDocument() === $this){
                $report->setDocument(null);
            }
        }

        return $this;
    }

    public function getTypeName(): ?string
    {
        return $this->info['assessmentType']['name'] ?? $this->info['name'] ?? null;
    }

    /**
     * @return Collection<int, Report>
     */
    public function getReports(): Collection
    {
        return $this->reports;
    }

    public function addReport(Report $report): static
    {
        if (!$this->reports->contains($report)) {
            $this->reports->add($report);
            $report->setDocument($this);
        }
        return $this;
    }

}
