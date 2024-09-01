<?php

namespace Kothman\Requestor;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    
    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var Collection<int, Request>
     */
    #[ORM\OneToMany(targetEntity: Request::class, mappedBy: 'requestor')]
    private Collection $requests;

    /**
     * @var Collection<int, Request>
     */
    #[ORM\OneToMany(targetEntity: Request::class, mappedBy: 'requestee')]
    private Collection $reviewalRequests;

    public function __construct()
    {
        $this->requests = new ArrayCollection();
        $this->reviewalRequests = new ArrayCollection();
    }

    public function getId(): ?int {
        return $this->id;
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @return Collection<int, Request>
     */
    public function getRequests(): Collection
    {
        return $this->requests;
    }

    public function addRequest(Request $request): static
    {
        if (!$this->requests->contains($request)) {
            $this->requests->add($request);
            $request->setRequestor($this);
        }

        return $this;
    }

    public function removeRequest(Request $request): static
    {
        if ($this->requests->removeElement($request)) {
            // set the owning side to null (unless already changed)
            if ($request->getRequestor() === $this) {
                $request->setRequestor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Request>
     */
    public function getReviewalRequests(): Collection
    {
        return $this->reviewalRequests;
    }

    public function addReviewalRequest(Request $reviewalRequest): static
    {
        if (!$this->reviewalRequests->contains($reviewalRequest)) {
            $this->reviewalRequests->add($reviewalRequest);
            $reviewalRequest->setRequestee($this);
        }

        return $this;
    }

    public function removeReviewalRequest(Request $reviewalRequest): static
    {
        if ($this->reviewalRequests->removeElement($reviewalRequest)) {
            // set the owning side to null (unless already changed)
            if ($reviewalRequest->getRequestee() === $this) {
                $reviewalRequest->setRequestee(null);
            }
        }

        return $this;
    }
}
