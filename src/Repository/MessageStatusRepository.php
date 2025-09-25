<?php

namespace App\Repository;

use App\Entity\MessageStatus;
use App\Entity\Participant;
use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MessageStatus>
 */
class MessageStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageStatus::class);
    }

    /**
     * Trouve le statut d'un message pour un participant
     */
    public function findStatusPourParticipant(Message $message, Participant $participant): ?MessageStatus
    {
        return $this->createQueryBuilder('ms')
            ->where('ms.message = :message')
            ->andWhere('ms.participant = :participant')
            ->setParameter('message', $message)
            ->setParameter('participant', $participant)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Crée ou met à jour le statut d'un message pour un participant
     */
    public function creerOuMettreAJourStatus(Message $message, Participant $participant, bool $lu = false): MessageStatus
    {
        $status = $this->findStatusPourParticipant($message, $participant);
        
        if (!$status) {
            $status = new MessageStatus();
            $status->setMessage($message);
            $status->setParticipant($participant);
            $this->getEntityManager()->persist($status);
        }
        
        $status->setLu($lu);
        $this->getEntityManager()->flush();
        
        return $status;
    }

    /**
     * Marque un message comme lu pour un participant
     */
    public function marquerCommeLu(Message $message, Participant $participant): void
    {
        $this->creerOuMettreAJourStatus($message, $participant, true);
    }

    /**
     * Marque un message comme non lu pour un participant
     */
    public function marquerCommeNonLu(Message $message, Participant $participant): void
    {
        $this->creerOuMettreAJourStatus($message, $participant, false);
    }

    /**
     * Compte les messages non lus pour un participant
     */
    public function countMessagesNonLusPourParticipant(Participant $participant): int
    {
        return $this->createQueryBuilder('ms')
            ->select('COUNT(ms.id)')
            ->join('ms.message', 'm')
            ->where('ms.participant = :participant')
            ->andWhere('ms.lu = false')
            ->andWhere('m.expediteur != :participant')
            ->setParameter('participant', $participant)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère tous les statuts non lus pour un participant
     */
    public function findMessagesNonLusPourParticipant(Participant $participant): array
    {
        return $this->createQueryBuilder('ms')
            ->join('ms.message', 'm')
            ->where('ms.participant = :participant')
            ->andWhere('ms.lu = false')
            ->andWhere('m.expediteur != :participant')
            ->orderBy('m.dateEnvoi', 'DESC')
            ->setParameter('participant', $participant)
            ->getQuery()
            ->getResult();
    }

    /**
     * Marque tous les messages comme lus pour un participant
     */
    public function marquerTousCommeLusPourParticipant(Participant $participant): void
    {
        $this->createQueryBuilder('ms')
            ->update()
            ->set('ms.lu', 'true')
            ->set('ms.dateLecture', ':now')
            ->where('ms.participant = :participant')
            ->andWhere('ms.lu = false')
            ->setParameter('participant', $participant)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }

    /**
     * Récupère les statistiques de lecture d'un message
     */
    public function getStatistiquesLecture(Message $message): array
    {
        $total = $this->createQueryBuilder('ms')
            ->select('COUNT(ms.id)')
            ->where('ms.message = :message')
            ->setParameter('message', $message)
            ->getQuery()
            ->getSingleScalarResult();

        $lus = $this->createQueryBuilder('ms')
            ->select('COUNT(ms.id)')
            ->where('ms.message = :message')
            ->andWhere('ms.lu = true')
            ->setParameter('message', $message)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'total' => $total,
            'lus' => $lus,
            'non_lus' => $total - $lus,
            'pourcentage_lu' => $total > 0 ? round(($lus / $total) * 100, 2) : 0
        ];
    }

    /**
     * Supprime les anciens statuts (nettoyage)
     */
    public function supprimerAnciensStatuts(\DateTimeInterface $avant): int
    {
        return $this->createQueryBuilder('ms')
            ->delete()
            ->where('ms.dateCreation < :avant')
            ->setParameter('avant', $avant)
            ->getQuery()
            ->execute();
    }
}