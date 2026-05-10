<?php

namespace App\Entity;

use App\Repository\TunnelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TunnelRepository::class)]
#[ORM\Table(name: 'tunnel')]
#[ORM\Index(columns: ['distributeur_id'], name: 'idx_tunnel_distributeur')]
#[UniqueEntity(fields: ['slugUnique'], message: 'Ce slug est déjà utilisé.')]
class Tunnel
{
    public const STATUT_BROUILLON = 'brouillon';
    public const STATUT_PUBLIE   = 'publie';
    public const STATUT_ARCHIVE  = 'archive';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Distributeur::class, inversedBy: 'tunnels')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Distributeur $distributeur;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    private string $nomTunnel;

    #[ORM\Column(length: 120, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', message: 'Slug invalide (lettres minuscules, chiffres et tirets uniquement)')]
    private string $slugUnique;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titrePage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sousTitre = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $texteCta = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $messageMerci = null;

    #[ORM\Column(length: 20, options: ['default' => 'brouillon'])]
    private string $statut = self::STATUT_BROUILLON;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'tunnel', targetEntity: TunnelProduit::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['ordreAffichage' => 'ASC'])]
    private Collection $tunnelProduits;

    #[ORM\OneToMany(mappedBy: 'tunnel', targetEntity: Prospect::class, orphanRemoval: true)]
    private Collection $prospects;

    public function __construct()
    {
        $this->id = Uuid::v4()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->tunnelProduits = new ArrayCollection();
        $this->prospects = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getDistributeur(): Distributeur { return $this->distributeur; }
    public function setDistributeur(Distributeur $d): self { $this->distributeur = $d; return $this; }
    public function getNomTunnel(): string { return $this->nomTunnel; }
    public function setNomTunnel(string $v): self { $this->nomTunnel = $v; return $this; }
    public function getSlugUnique(): string { return $this->slugUnique; }
    public function setSlugUnique(string $v): self { $this->slugUnique = $v; return $this; }
    public function getTitrePage(): ?string { return $this->titrePage; }
    public function setTitrePage(?string $v): self { $this->titrePage = $v; return $this; }
    public function getSousTitre(): ?string { return $this->sousTitre; }
    public function setSousTitre(?string $v): self { $this->sousTitre = $v; return $this; }
    public function getTexteCta(): ?string { return $this->texteCta; }
    public function setTexteCta(?string $v): self { $this->texteCta = $v; return $this; }
    public function getMessageMerci(): ?string { return $this->messageMerci; }
    public function setMessageMerci(?string $v): self { $this->messageMerci = $v; return $this; }
    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $v): self { $this->statut = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $v): self { $this->updatedAt = $v; return $this; }
    public function getTunnelProduits(): Collection { return $this->tunnelProduits; }
    public function getProspects(): Collection { return $this->prospects; }
}
