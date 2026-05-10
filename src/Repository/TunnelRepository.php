<?php

namespace App\Repository;

use App\Entity\Distributeur;
use App\Entity\Tunnel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tunnel>
 */
class TunnelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tunnel::class);
    }

    /** @return Tunnel[] */
    public function findByDistributeur(Distributeur $d): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.distributeur = :d')
            ->setParameter('d', $d)
            ->orderBy('t.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneBySlug(string $slug): ?Tunnel
    {
        return $this->findOneBy(['slugUnique' => $slug]);
    }

    public function slugExists(string $slug, ?string $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->andWhere('t.slugUnique = :slug')
            ->setParameter('slug', $slug);

        if ($excludeId) {
            $qb->andWhere('t.id != :id')->setParameter('id', $excludeId);
        }

        return ((int) $qb->getQuery()->getSingleScalarResult()) > 0;
    }
}
