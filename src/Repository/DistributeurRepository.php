<?php

namespace App\Repository;

use App\Entity\Distributeur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Distributeur>
 */
class DistributeurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Distributeur::class);
    }

    public function findOneByEmail(string $email): ?Distributeur
    {
        return $this->findOneBy(['email' => $email]);
    }
}
