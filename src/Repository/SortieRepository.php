<?php

namespace App\Repository;

use App\Entity\Sortie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sortie>
 */
class SortieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }

    /**
     * Trouve les sorties à venir
     */
    public function findUpcoming(): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.etat', 'e')
            ->where('s.dateHeureDebut > :now')
            ->andWhere('e.libelle IN (:etats)')
            ->setParameter('now', new \DateTime())
            ->setParameter('etats', ['Ouverte', 'Clôturée'])
            ->orderBy('s.dateHeureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les sorties organisées par un participant
     */
    public function findByOrganisateur(int $organisateurId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.organisateur = :organisateur')
            ->setParameter('organisateur', $organisateurId)
            ->orderBy('s.dateHeureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les sorties auxquelles un participant est inscrit
     */
    public function findByParticipant(int $participantId): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.inscriptions', 'i')
            ->where('i.participant = :participant')
            ->setParameter('participant', $participantId)
            ->orderBy('s.dateHeureDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des sorties avec filtres
     */
    public function search(array $criteria = []): array
    {
        $qb = $this->createQueryBuilder('s')
            ->join('s.lieu', 'l')
            ->join('l.ville', 'v')
            ->join('s.etat', 'e')
            ->join('s.organisateur', 'o');

        if (!empty($criteria['nom'])) {
            $qb->andWhere('s.nom LIKE :nom')
               ->setParameter('nom', '%' . $criteria['nom'] . '%');
        }

        if (!empty($criteria['dateDebut'])) {
            $qb->andWhere('s.dateHeureDebut >= :dateDebut')
               ->setParameter('dateDebut', $criteria['dateDebut']);
        }

        if (!empty($criteria['dateFin'])) {
            $qb->andWhere('s.dateHeureDebut <= :dateFin')
               ->setParameter('dateFin', $criteria['dateFin']);
        }

        if (!empty($criteria['organisateur'])) {
            $qb->andWhere('o.id = :organisateur')
               ->setParameter('organisateur', $criteria['organisateur']);
        }

        if (!empty($criteria['site'])) {
            $qb->andWhere('o.site = :site')
               ->setParameter('site', $criteria['site']);
        }

        return $qb->orderBy('s.dateHeureDebut', 'ASC')
                  ->getQuery()
                  ->getResult();
    }
}