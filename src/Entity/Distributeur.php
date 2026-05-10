<?php

namespace App\Entity;

use App\Repository\DistributeurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DistributeurRepository::class)]
#[ORM\Table(name: 'distributeur')]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
class Distributeur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private string $prenom;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private string $nom;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email;

    #[ORM\Column(length: 255)]
    private string $motDePasseHash;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telephoneWhatsapp = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slogan = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'distributeur', targetEntity: Produit::class, orphanRemoval: true)]
    private Collection $produits;

    #[ORM\OneToMany(mappedBy: 'distributeur', targetEntity: Tunnel::class, orphanRemoval: true)]
    private Collection $tunnels;

    public function __construct()
    {
        $this->id = Uuid::v4()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
        $this->produits = new ArrayCollection();
        $this->tunnels = new ArrayCollection();
    }

    public function getId(): string { return $this->id; }
    public function getPrenom(): string { return $this->prenom; }
    public function setPrenom(string $v): self { $this->prenom = $v; return $this; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $v): self { $this->nom = $v; return $this; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $v): self { $this->email = $v; return $this; }
    public function getMotDePasseHash(): string { return $this->motDePasseHash; }
    public function setMotDePasseHash(string $v): self { $this->motDePasseHash = $v; return $this; }
    public function getTelephoneWhatsapp(): ?string { return $this->telephoneWhatsapp; }
    public function setTelephoneWhatsapp(?string $v): self { $this->telephoneWhatsapp = $v; return $this; }
    public function getSlogan(): ?string { return $this->slogan; }
    public function setSlogan(?string $v): self { $this->slogan = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getProduits(): Collection { return $this->produits; }
    public function getTunnels(): Collection { return $this->tunnels; }

    // UserInterface
    public function getRoles(): array { return ['ROLE_DISTRIBUTEUR']; }
    public function getPassword(): ?string { return $this->motDePasseHash; }
    public function getUserIdentifier(): string { return $this->email; }
    public function eraseCredentials(): void {}
}
