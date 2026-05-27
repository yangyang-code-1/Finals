<?php 

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;  // New import
use Symfony\Component\Validator\Mapping\ClassMetadata;  // New import
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 50)]
    private ?string $role = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'artist', targetEntity: Commission::class)]
    private Collection $commissionsCreated;

    #[ORM\OneToMany(mappedBy: 'client', targetEntity: Commission::class)]
    private Collection $commissionsRequested;

    #[ORM\Column]
    private ?bool $isVerified = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(name: 'fcm_token', length: 512, nullable: true)]
    private ?string $fcmToken = null;

    #[ORM\Column(name: 'fcm_platform', length: 20, nullable: true)]
    private ?string $fcmPlatform = null;

    #[ORM\Column(name: 'fcm_token_updated_at', nullable: true)]
    private ?\DateTimeImmutable $fcmTokenUpdatedAt = null;

    public function __construct()
    {
        $this->commissionsCreated = new ArrayCollection();
        $this->commissionsRequested = new ArrayCollection();
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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

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

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    // New validation method
    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('email', new Assert\Email([
            'message' => 'The email "{{ value }}" is not a valid email.',
        ]));
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
     */
    public function getRoles(): array
    {
        // Ensure we always return at least ROLE_USER
        $roles = ['ROLE_USER'];
        
        // Add the user's role if it exists and is not already ROLE_USER
        if ($this->role && $this->role !== 'ROLE_USER') {
            $roles[] = $this->role;
        }
        
        return array_unique($roles);
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function isVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getVerificationToken(): ?string
    {
        return $this->verificationToken;
    }

    public function setVerificationToken(?string $verificationToken): static
    {
        $this->verificationToken = $verificationToken;

        return $this;
    }

    public function getFcmToken(): ?string
    {
        return $this->fcmToken;
    }

    public function setFcmToken(?string $fcmToken): static
    {
        $this->fcmToken = $fcmToken;

        return $this;
    }

    public function getFcmPlatform(): ?string
    {
        return $this->fcmPlatform;
    }

    public function setFcmPlatform(?string $fcmPlatform): static
    {
        $this->fcmPlatform = $fcmPlatform;

        return $this;
    }

    public function getFcmTokenUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->fcmTokenUpdatedAt;
    }

    public function setFcmTokenUpdatedAt(?\DateTimeImmutable $fcmTokenUpdatedAt): static
    {
        $this->fcmTokenUpdatedAt = $fcmTokenUpdatedAt;

        return $this;
    }
}
