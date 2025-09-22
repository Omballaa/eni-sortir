<?php

namespace App\Repository;

use App\Entity\Site;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Site>
 */
class SiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Site::class);
    }

    /**
     * Trouve tous les sites ordonnés par nom
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.nomSite', 'ASC')
            ->getQuery()
            ->getResult();
    }
}