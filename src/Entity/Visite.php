<?php

namespace App\Entity;

use App\Repository\VisiteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: VisiteRepository::class)]
#[ORM\Table(name: 'visite')]
#[ORM\Index(columns: ['tunnel_id'], name: 'idx_visite_tunnel')]
class Visite
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Tunnel::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Tunnel $tunnel;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $page = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $visiteLe;

    public function __construct()
    {
        $this->id = Uuid::v4()->toRfc4122();
        $this->visiteLe = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getTunnel(): Tunnel { return $this->tunnel; }
    public function setTunnel(Tunnel $t): self { $this->tunnel = $t; return $this; }
    public function getPage(): ?string { return $this->page; }
    public function setPage(?string $v): self { $this->page = $v; return $this; }
    public function getVisiteLe(): \DateTimeImmutable { return $this->visiteLe; }
}
