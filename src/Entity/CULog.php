<?php

namespace App\Entity;

use App\Repository\CULogRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CULogRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CULog
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 16)]
    private ?string $action = null;

    #[ORM\Column(length: 32)]
    private ?string $madeBy = null;

    #[ORM\ManyToOne(inversedBy: 'log')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Document $document = null;

    #[ORM\ManyToOne(inversedBy: 'log')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Report $report = null;

    #[ORM\ManyToOne(inversedBy: 'log')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Template $template  = null;

    #[ORM\Column]
    private ?DateTimeImmutable $created_at = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function getMadeBy(): ?string
    {
        return $this->madeBy;
    }

    public function setMadeBy(?string $madeBy): static
    {
        $this->madeBy = $madeBy;
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

    public function getReport(): ?Report
    {
        return $this->report;
    }

    public function setReport(?Report $report): static
    {
        $this->report = $report;

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

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->created_at;
    }

    #[ORM\PrePersist]
    public function setTimestamps(): void
    {
        $this->created_at = new DateTimeImmutable();
    }
}
