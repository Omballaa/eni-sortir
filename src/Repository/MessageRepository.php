<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\Participant;
use App\Entity\GroupeMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 */
class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Récupère les messages d'un groupe avec pagination
     */
    public function findByGroupeWithPagination(GroupeMessage $groupe, int $page = 1, int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.groupe = :groupe')
            ->setParameter('groupe', $groupe)
            ->orderBy('m.dateEnvoi', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Alias pour findByGroupeWithPagination (compatibilité)
     */
    public function findMessagesForGroupe(GroupeMessage $groupe, int $limit = 50): array
    {
        return $this->findByGroupeWithPagination($groupe, 1, $limit);
    }

    /**
     * Récupère les messages privés entre deux participants
     */
    public function findMessagesPrivesEntre(Participant $participant1, Participant $participant2, int $limit = 50): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.expediteur = :p1 AND m.destinataire = :p2) OR (m.expediteur = :p2 AND m.destinataire = :p1)')
            ->andWhere('m.groupe IS NULL')
            ->setParameter('p1', $participant1)
            ->setParameter('p2', $participant2)
            ->orderBy('m.dateEnvoi', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les messages non lus pour un participant dans un groupe
     */
    public function countMessagesNonLusPourParticipantDansGroupe(Participant $participant, GroupeMessage $groupe): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->leftJoin('m.statuts', 's', 'WITH', 's.participant = :participant')
            ->where('m.groupe = :groupe')
            ->andWhere('m.expediteur != :participant')
            ->andWhere('s.id IS NULL OR s.lu = false')
            ->setParameter('participant', $participant)
            ->setParameter('groupe', $groupe)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte tous les messages non lus pour un participant
     */
    public function countTousMessagesNonLusPourParticipant(Participant $participant): int
    {
        // Messages de groupes non lus
        $groupeNonLus = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->leftJoin('m.statuts', 's', 'WITH', 's.participant = :participant')
            ->where('m.groupe IS NOT NULL')
            ->andWhere('m.expediteur != :participant')
            ->andWhere('s.id IS NULL OR s.lu = false')
            ->setParameter('participant', $participant)
            ->getQuery()
            ->getSingleScalarResult();

        // Messages privés non lus
        $privesNonLus = $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->leftJoin('m.statuts', 's', 'WITH', 's.participant = :participant')
            ->where('m.groupe IS NULL')
            ->andWhere('m.destinataire = :participant')
            ->andWhere('m.expediteur != :participant')
            ->andWhere('s.id IS NULL OR s.lu = false')
            ->setParameter('participant', $participant)
            ->getQuery()
            ->getSingleScalarResult();

        return $groupeNonLus + $privesNonLus;
    }

    /**
     * Alias pour countTousMessagesNonLusPourParticipant (compatibilité)
     */
    public function countTotalMessagesNonLus(Participant $participant): int
    {
        return $this->countTousMessagesNonLusPourParticipant($participant);
    }

    /**
     * Récupère les derniers messages par groupe pour un participant
     */
    public function findDerniersMessagesPourParticipant(Participant $participant): array
    {
        // Sous-requête pour récupérer le dernier message de chaque groupe
        $subQuery = $this->createQueryBuilder('m2')
            ->select('MAX(m2.dateEnvoi)')
            ->where('m2.groupe = m.groupe');

        return $this->createQueryBuilder('m')
            ->join('m.groupe', 'g')
            ->join('g.membres', 'gm', 'WITH', 'gm.participant = :participant AND gm.actif = true')
            ->where('m.dateEnvoi = (' . $subQuery->getDQL() . ')')
            ->setParameter('participant', $participant)
            ->orderBy('m.dateEnvoi', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Marque tous les messages d'un groupe comme lus pour un participant
     */
    public function marquerTousLusDansGroupe(Participant $participant, GroupeMessage $groupe): void
    {
        $messages = $this->createQueryBuilder('m')
            ->where('m.groupe = :groupe')
            ->andWhere('m.expediteur != :participant')
            ->setParameter('groupe', $groupe)
            ->setParameter('participant', $participant)
            ->getQuery()
            ->getResult();

        foreach ($messages as $message) {
            $message->marquerLuPar($participant);
        }

        $this->getEntityManager()->flush();
    }

    /**
     * Récupère les messages système d'un groupe (joins, leaves, etc.)
     */
    public function findMessagesSystemeGroupe(GroupeMessage $groupe): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.groupe = :groupe')
            ->andWhere('m.estSysteme = true')
            ->setParameter('groupe', $groupe)
            ->orderBy('m.dateEnvoi', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les nouveaux messages d'un groupe depuis un message donné
     */
    public function findNewMessagesForGroupe(GroupeMessage $groupe, int $lastMessageId = 0): array
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.groupe = :groupe')
            ->setParameter('groupe', $groupe);
            
        if ($lastMessageId > 0) {
            $qb->andWhere('m.id > :lastId')
               ->setParameter('lastId', $lastMessageId);
        }
        
        return $qb->orderBy('m.dateEnvoi', 'ASC')
            ->getQuery()
            ->getResult();
    }
}