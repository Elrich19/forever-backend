<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[ORM\Table(name: 'produit')]
#[ORM\Index(columns: ['distributeur_id'], name: 'idx_produit_distributeur')]
class Produit
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Distributeur::class, inversedBy: 'produits')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Distributeur $distributeur;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank]
    private string $nom;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $categorie = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $imageUrl = null;

    /** @var string[] */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $imageUrls = [];

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\PositiveOrZero]
    private string $prix = '0.00';

    #[ORM\Column(options: ['default' => true])]
    private bool $actif = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->id = Uuid::v4()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getDistributeur(): Distributeur { return $this->distributeur; }
    public function setDistributeur(Distributeur $d): self { $this->distributeur = $d; return $this; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $v): self { $this->nom = $v; return $this; }
    public function getCategorie(): ?string { return $this->categorie; }
    public function setCategorie(?string $v): self { $this->categorie = $v; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $v): self { $this->description = $v; return $this; }
    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $v): self { $this->imageUrl = $v; return $this; }
    /** @return string[] */
    public function getImageUrls(): array { return $this->imageUrls ?? []; }
    /** @param string[] $v */
    public function setImageUrls(array $v): self { $this->imageUrls = array_values(array_filter($v, 'is_string')); return $this; }
    public function getPrix(): string { return $this->prix; }
    public function setPrix(string $v): self { $this->prix = $v; return $this; }
    public function isActif(): bool { return $this->actif; }
    public function setActif(bool $v): self { $this->actif = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
