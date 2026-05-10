<?php

namespace App\Repository;

use App\Entity\Prospect;
use App\Entity\Tunnel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Prospect>
 */
class ProspectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Prospect::class);
    }

    /** @return Prospect[] */
    public function findByTunnel(Tunnel $t): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.tunnel = :t')
            ->setParameter('t', $t)
            ->orderBy('p.soumisLe', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
