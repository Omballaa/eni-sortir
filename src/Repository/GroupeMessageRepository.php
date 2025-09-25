<?php

namespace App\Repository;

use App\Entity\GroupeMessage;
use App\Entity\Participant;
use App\Entity\Sortie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GroupeMessage>
 */
class GroupeMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupeMessage::class);
    }

    /**
     * Trouve le groupe associé à une sortie
     */
    public function findBySortie(Sortie $sortie): ?GroupeMessage
    {
        return $this->createQueryBuilder('g')
            ->where('g.sortie = :sortie')
            ->andWhere('g.type = :type')
            ->setParameter('sortie', $sortie)
            ->setParameter('type', 'sortie')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère tous les groupes auxquels un participant appartient
     */
    public function findGroupesPourParticipant(Participant $participant): array
    {
        return $this->createQueryBuilder('g')
            ->join('g.membres', 'm')
            ->where('m.participant = :participant')
            ->andWhere('m.actif = true')
            ->andWhere('g.estActif = true')
            ->setParameter('participant', $participant)
            ->orderBy('g.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Alias pour findGroupesPourParticipant (compatibilité)
     */
    public function findGroupesForParticipant(Participant $participant): array
    {
        return $this->findGroupesPourParticipant($participant);
    }

    /**
     * Compte les messages non lus dans un groupe pour un participant
     */
    public function countMessagesNonLus(GroupeMessage $groupe, Participant $participant): int
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(m.id)')
            ->from('App\Entity\Message', 'm')
            ->leftJoin('m.statuts', 's', 'WITH', 's.participant = :participant')
            ->where('m.groupe = :groupe')
            ->andWhere('m.expediteur != :participant')
            ->andWhere('(s.id IS NULL OR s.lu = false)')
            ->setParameter('groupe', $groupe)
            ->setParameter('participant', $participant)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère tous les groupes de sorties auxquels un participant appartient
     */
    public function findGroupesSortiesPourParticipant(Participant $participant): array
    {
        return $this->createQueryBuilder('g')
            ->join('g.membres', 'm')
            ->where('m.participant = :participant')
            ->andWhere('m.actif = true')
            ->andWhere('g.estActif = true')
            ->andWhere('g.type = :type')
            ->setParameter('participant', $participant)
            ->setParameter('type', 'sortie')
            ->orderBy('g.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les groupes privés auxquels un participant appartient
     */
    public function findGroupesPrivesPourParticipant(Participant $participant): array
    {
        return $this->createQueryBuilder('g')
            ->join('g.membres', 'm')
            ->where('m.participant = :participant')
            ->andWhere('m.actif = true')
            ->andWhere('g.estActif = true')
            ->andWhere('g.type = :type')
            ->setParameter('participant', $participant)
            ->setParameter('type', 'prive')
            ->orderBy('g.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des groupes par nom
     */
    public function findByNomLike(string $nom): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.nom LIKE :nom')
            ->andWhere('g.estActif = true')
            ->setParameter('nom', '%' . $nom . '%')
            ->orderBy('g.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les groupes avec le nombre de messages non lus pour un participant
     */
    public function findGroupesAvecNonLusPourParticipant(Participant $participant): array
    {
        return $this->createQueryBuilder('g')
            ->select('g', 'COUNT(m.id) as messagesNonLus')
            ->join('g.membres', 'gm')
            ->leftJoin('g.messages', 'm', 'WITH', 'm.expediteur != :participant')
            ->leftJoin('m.statuts', 's', 'WITH', 's.participant = :participant AND s.lu = true')
            ->where('gm.participant = :participant')
            ->andWhere('gm.actif = true')
            ->andWhere('g.estActif = true')
            ->andWhere('s.id IS NULL')
            ->groupBy('g.id')
            ->setParameter('participant', $participant)
            ->orderBy('g.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Crée un groupe pour une sortie
     */
    public function creerGroupePourSortie(Sortie $sortie): GroupeMessage
    {
        $groupe = new GroupeMessage();
        $groupe->setNom('Sortie : ' . $sortie->getNom());
        $groupe->setDescription('Groupe de discussion pour la sortie ' . $sortie->getNom());
        $groupe->setType('sortie');
        $groupe->setSortie($sortie);
        $groupe->setCreateur($sortie->getOrganisateur());
        
        // Ajouter l'organisateur comme admin
        $groupe->ajouterParticipant($sortie->getOrganisateur(), true);

        $this->getEntityManager()->persist($groupe);
        $this->getEntityManager()->flush();

        return $groupe;
    }

    /**
     * Trouve les groupes inactifs (pour nettoyage)
     */
    public function findGroupesInactifs(): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.estActif = false')
            ->orderBy('g.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre total de groupes actifs
     */
    public function countGroupesActifs(): int
    {
        return $this->createQueryBuilder('g')
            ->select('COUNT(g.id)')
            ->where('g.estActif = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les statistiques d'activité des groupes
     */
    public function getStatistiquesActivite(): array
    {
        return $this->createQueryBuilder('g')
            ->select('g.type', 'COUNT(g.id) as nombre')
            ->where('g.estActif = true')
            ->groupBy('g.type')
            ->getQuery()
            ->getResult();
    }
}