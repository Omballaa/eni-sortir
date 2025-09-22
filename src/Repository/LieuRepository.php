<?php

namespace App\Repository;

use App\Entity\Lieu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Lieu>
 */
class LieuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lieu::class);
    }

    /**
     * Trouve tous les lieux d'une ville donnée
     */
    public function findByVille(int $villeId): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.ville = :ville')
            ->setParameter('ville', $villeId)
            ->orderBy('l.nomLieu', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des lieux par nom
     */
    public function searchByName(string $search): array
    {
        return $this->createQueryBuilder('l')
            ->join('l.ville', 'v')
            ->where('l.nomLieu LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('l.nomLieu', 'ASC')
            ->getQuery()
            ->getResult();
    }
}