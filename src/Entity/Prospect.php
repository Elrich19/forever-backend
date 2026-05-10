<?php

namespace App\Entity;

use App\Repository\ProspectRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProspectRepository::class)]
#[ORM\Table(name: 'prospect')]
#[ORM\Index(columns: ['tunnel_id'], name: 'idx_prospect_tunnel')]
class Prospect
{
    public const STATUT_NOUVEAU = 'nouveau';
    public const STATUT_CONTACTE = 'contacte';
    public const STATUT_CONVERTI = 'converti';
    public const STATUT_PERDU = 'perdu';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Tunnel::class, inversedBy: 'prospects')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Tunnel $tunnel;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private string $prenom;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 20, options: ['default' => 'nouveau'])]
    private string $statut = self::STATUT_NOUVEAU;

    /** @var array<int,array{id:string,nom:string,prix?:string}> */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $produitsInteresse = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $soumisLe;

    public function __construct()
    {
        $this->id = Uuid::v4()->toRfc4122();
        $this->soumisLe = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getTunnel(): Tunnel { return $this->tunnel; }
    public function setTunnel(Tunnel $t): self { $this->tunnel = $t; return $this; }
    public function getPrenom(): string { return $this->prenom; }
    public function setPrenom(string $v): self { $this->prenom = $v; return $this; }
    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $v): self { $this->telephone = $v; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $v): self { $this->email = $v; return $this; }
    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $v): self { $this->statut = $v; return $this; }
    /** @return array<int,array{id:string,nom:string,prix?:string}> */
    public function getProduitsInteresse(): array { return $this->produitsInteresse ?? []; }
    /** @param array<int,array{id:string,nom:string,prix?:string}> $v */
    public function setProduitsInteresse(array $v): self { $this->produitsInteresse = array_values($v); return $this; }
    public function getSoumisLe(): \DateTimeImmutable { return $this->soumisLe; }
}
