<?php

namespace App\Repository;

use App\Entity\GroupeMembre;
use App\Entity\Participant;
use App\Entity\GroupeMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GroupeMembre>
 */
class GroupeMembreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupeMembre::class);
    }

    /**
     * Trouve un membre spécifique dans un groupe
     */
    public function findMembreParticipantDansGroupe(Participant $participant, GroupeMessage $groupe): ?GroupeMembre
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.participant = :participant')
            ->andWhere('gm.groupe = :groupe')
            ->setParameter('participant', $participant)
            ->setParameter('groupe', $groupe)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère tous les membres actifs d'un groupe
     */
    public function findMembresActifs(GroupeMessage $groupe): array
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.groupe = :groupe')
            ->andWhere('gm.actif = true')
            ->setParameter('groupe', $groupe)
            ->orderBy('gm.dateAjout', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les administrateurs d'un groupe
     */
    public function findAdmins(GroupeMessage $groupe): array
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.groupe = :groupe')
            ->andWhere('gm.actif = true')
            ->andWhere('gm.estAdmin = true')
            ->setParameter('groupe', $groupe)
            ->orderBy('gm.dateAjout', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte le nombre de membres actifs dans un groupe
     */
    public function countMembresActifs(GroupeMessage $groupe): int
    {
        return $this->createQueryBuilder('gm')
            ->select('COUNT(gm.id)')
            ->where('gm.groupe = :groupe')
            ->andWhere('gm.actif = true')
            ->setParameter('groupe', $groupe)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupère les groupes d'un participant avec leur statut de membre
     */
    public function findGroupesAvecStatutPourParticipant(Participant $participant): array
    {
        return $this->createQueryBuilder('gm')
            ->join('gm.groupe', 'g')
            ->where('gm.participant = :participant')
            ->andWhere('gm.actif = true')
            ->andWhere('g.estActif = true')
            ->setParameter('participant', $participant)
            ->orderBy('gm.derniereVisite', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Met à jour la dernière visite pour un membre dans un groupe
     */
    public function mettreAJourDerniereVisite(Participant $participant, GroupeMessage $groupe): void
    {
        $membre = $this->findMembreParticipantDansGroupe($participant, $groupe);
        if ($membre) {
            $membre->mettreAJourDerniereVisite();
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Active ou désactive les notifications pour un membre
     */
    public function toggleNotifications(Participant $participant, GroupeMessage $groupe): bool
    {
        $membre = $this->findMembreParticipantDansGroupe($participant, $groupe);
        if ($membre) {
            $membre->setNotifications(!$membre->isNotifications());
            $this->getEntityManager()->flush();
            return $membre->isNotifications();
        }
        return false;
    }

    /**
     * Récupère les membres avec notifications activées
     */
    public function findMembresAvecNotifications(GroupeMessage $groupe): array
    {
        return $this->createQueryBuilder('gm')
            ->where('gm.groupe = :groupe')
            ->andWhere('gm.actif = true')
            ->andWhere('gm.notifications = true')
            ->setParameter('groupe', $groupe)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les statistiques d'un groupe
     */
    public function getStatistiquesGroupe(GroupeMessage $groupe): array
    {
        $membresActifs = $this->countMembresActifs($groupe);
        $admins = count($this->findAdmins($groupe));
        
        return [
            'membres_actifs' => $membresActifs,
            'administrateurs' => $admins,
            'membres_avec_notifications' => count($this->findMembresAvecNotifications($groupe))
        ];
    }
}