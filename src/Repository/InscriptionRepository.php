<?php

namespace App\Repository;

use App\Entity\Inscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Inscription>
 */
class InscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inscription::class);
    }

    /**
     * Trouve une inscription par participant et sortie
     */
    public function findByParticipantAndSortie(int $participantId, int $sortieId): ?Inscription
    {
        return $this->createQueryBuilder('i')
            ->where('i.participant = :participant')
            ->andWhere('i.sortie = :sortie')
            ->setParameter('participant', $participantId)
            ->setParameter('sortie', $sortieId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve toutes les inscriptions d'une sortie
     */
    public function findBySortie(int $sortieId): array
    {
        return $this->createQueryBuilder('i')
            ->join('i.participant', 'p')
            ->where('i.sortie = :sortie')
            ->setParameter('sortie', $sortieId)
            ->orderBy('i.dateInscription', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les inscriptions d'un participant
     */
    public function findByParticipant(int $participantId): array
    {
        return $this->createQueryBuilder('i')
            ->join('i.sortie', 's')
            ->where('i.participant = :participant')
            ->setParameter('participant', $participantId)
            ->orderBy('s.dateHeureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }
}