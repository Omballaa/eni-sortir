<?php

namespace App\Repository;

use App\Entity\Etat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Etat>
 */
class EtatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Etat::class);
    }

    /**
     * Trouve un état par son libellé
     */
    public function findByLibelle(string $libelle): ?Etat
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.libelle = :libelle')
            ->setParameter('libelle', $libelle)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve tous les états ordonnés par libellé
     */
    public function findAllOrderByLibelle(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.libelle', 'ASC')
            ->getQuery()
            ->getResult();
    }
}