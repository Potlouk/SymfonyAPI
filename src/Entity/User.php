<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 64)]
    private ?string $email = null;

    #[ORM\Column]
    private array $data = [];
    
    /**
     * @var Collection<int, Document>
     */
    #[ORM\ManyToMany(targetEntity: Document::class, mappedBy: 'assignedUsers')]
    private Collection $assignedDocuments;

    /**
     * @var Collection<int, Report>
     */
    #[ORM\ManyToMany(targetEntity: Report::class, inversedBy: 'assignedUsers')]
    #[ORM\JoinTable(name: 'user_assigned_reports')]
    private Collection $assignedReports;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Token $token = null;

    #[ORM\ManyToOne(targetEntity: Role::class, cascade: ["persist"])]
    #[ORM\JoinColumn(name: "role_id", referencedColumnName: "id",  nullable: true, onDelete: "SET NULL")]
    private ?Role $role = null;

    public function __construct()
    {
        $this->assignedDocuments = new ArrayCollection();
        $this->assignedReports = new ArrayCollection();
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

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getRoles(): array
    {
       return $this->role->getPermissions()?->getValue() ?? [];
    }

    public function getBccEmail(): array
    {
        if (isset($this->data['bccEmails']) && !empty($this->data['bccEmails']))
            return $this->data['bccEmails'];

        return $this->data['bccEmails'] ?? [];
    } 

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
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

    public function getTreeIds(): ?array
    {
        return $this->role?->getTreeIds() ?? [];
    }

    /**
     * @return Collection<int, Document>
     */
    public function getAssignedDocuments(): Collection
    {
        return $this->assignedDocuments;
    }

    public function addAssignedDocument(Document $document): static
    {
        if (!$this->assignedDocuments->contains($document)) {
            $this->assignedDocuments->add($document);
            $document->addAssignedUser($this);
        }
        return $this;
    }

    public function removeAssignedDocument(Document $document): static
    {
        if ($this->assignedDocuments->removeElement($document))
            $document->removeAssignedUser($this);

        return $this;
    }

    /**
     * @return Collection<int, Report>
     */
    public function getAssignedReports(): Collection
    {
        return $this->assignedReports;
    }

    public function addAssignedReport(Report $assignedReport): static
    {
        if (!$this->assignedReports->contains($assignedReport))
            $this->assignedReports->add($assignedReport);

        return $this;
    }

    public function removeAssignedReport(Report $assignedReport): static
    {
        $this->assignedReports->removeElement($assignedReport);
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

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function eraseCredentials(): void {}
}
