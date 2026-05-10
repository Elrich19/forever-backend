<?php

namespace App\Repository;

use App\Entity\Distributeur;
use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    /** @return Produit[] */
    public function findByDistributeur(Distributeur $d, bool $actifsOnly = false): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.distributeur = :d')
            ->setParameter('d', $d)
            ->orderBy('p.createdAt', 'DESC');

        if ($actifsOnly) {
            $qb->andWhere('p.actif = true');
        }

        return $qb->getQuery()->getResult();
    }
}
