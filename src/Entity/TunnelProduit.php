<?php

namespace App\Entity;

use App\Repository\TunnelProduitRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TunnelProduitRepository::class)]
#[ORM\Table(name: 'tunnel_produit')]
#[ORM\UniqueConstraint(name: 'uniq_tunnel_produit', columns: ['tunnel_id', 'produit_id'])]
class TunnelProduit
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Tunnel::class, inversedBy: 'tunnelProduits')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Tunnel $tunnel;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Produit::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Produit $produit;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $ordreAffichage = 0;

    public function getTunnel(): Tunnel { return $this->tunnel; }
    public function setTunnel(Tunnel $t): self { $this->tunnel = $t; return $this; }
    public function getProduit(): Produit { return $this->produit; }
    public function setProduit(Produit $p): self { $this->produit = $p; return $this; }
    public function getOrdreAffichage(): int { return $this->ordreAffichage; }
    public function setOrdreAffichage(int $v): self { $this->ordreAffichage = $v; return $this; }
}
