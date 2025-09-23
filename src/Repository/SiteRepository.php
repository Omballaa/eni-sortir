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

    /**
     * Trouve les sites avec pagination et recherche
     */
    public function findBySearchPaginated(string $search, int $page, int $limit): array
    {
        $queryBuilder = $this->createQueryBuilder('s');
        
        if (!empty($search)) {
            $queryBuilder
                ->where('s.nomSite LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        return $queryBuilder
            ->orderBy('s.nomSite', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les sites avec recherche
     */
    public function countBySearch(string $search): int
    {
        $queryBuilder = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)');
        
        if (!empty($search)) {
            $queryBuilder
                ->where('s.nomSite LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        return $queryBuilder
            ->getQuery()
            ->getSingleScalarResult();
    }
}