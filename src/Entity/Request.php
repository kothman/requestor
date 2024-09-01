<?php

namespace Kothman\Requestor;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RequestRepository::class)]
class Request
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $start_datetime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $end_datetime = null;

    #[ORM\ManyToOne(inversedBy: 'requests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $requestor = null;

    #[ORM\ManyToOne(inversedBy: 'reviewalRequests')]
    private ?User $requestee = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 255)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone_alt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartDateTime(): ?\DateTimeInterface
    {
        return $this->start_datetime;
    }

    public function setStartDateTime(\DateTimeInterface $start_datetime): static
    {
        $this->start_datetime = $start_datetime;

        return $this;
    }

    public function getEndDatetime(): ?\DateTimeInterface
    {
        return $this->end_datetime;
    }

    public function setEndDatetime(\DateTimeInterface $end_datetime): static
    {
        $this->end_datetime = $end_datetime;

        return $this;
    }

    public function getRequestor(): ?User
    {
        return $this->requestor;
    }

    public function setRequestor(?User $requestor): static
    {
        $this->requestor = $requestor;

        return $this;
    }

    public function getRequestee(): ?User
    {
        return $this->requestee;
    }

    public function setRequestee(?User $requestee): static
    {
        $this->requestee = $requestee;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getPhoneAlt(): ?string
    {
        return $this->phone_alt;
    }

    public function setPhoneAlt(string $phone_alt): static
    {
        $this->phone_alt = $phone_alt;

        return $this;
    }
}
