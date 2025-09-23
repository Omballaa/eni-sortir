<?php

namespace App\Repository;

use App\Entity\Ville;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ville>
 */
class VilleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ville::class);
    }

    /**
     * Trouve toutes les villes ordonnées par nom
     *
     * @return Ville[]
     */
    public function findAllOrderByName(): array
    {
        return $this->createQueryBuilder('v')
            ->orderBy('v.nomVille', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des villes par nom ou code postal
     */
    public function searchByNameOrPostalCode(string $search): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.nomVille LIKE :search OR v.codePostal LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('v.nomVille', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les villes avec pagination et recherche
     */
    public function findBySearchPaginated(string $search, int $page, int $limit): array
    {
        $queryBuilder = $this->createQueryBuilder('v');
        
        if (!empty($search)) {
            $queryBuilder
                ->where('v.nomVille LIKE :search OR v.codePostal LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        return $queryBuilder
            ->orderBy('v.nomVille', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les villes avec recherche
     */
    public function countBySearch(string $search): int
    {
        $queryBuilder = $this->createQueryBuilder('v')
            ->select('COUNT(v.id)');
        
        if (!empty($search)) {
            $queryBuilder
                ->where('v.nomVille LIKE :search OR v.codePostal LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }
        
        return $queryBuilder
            ->getQuery()
            ->getSingleScalarResult();
    }
}