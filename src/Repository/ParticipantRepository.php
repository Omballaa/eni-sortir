<?php

namespace App\Repository;

use App\Entity\Participant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<Participant>
 */
class ParticipantRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participant::class);
    }

    /**
     * Trouve un participant par son pseudo (utilisé pour l'authentification)
     */
    public function findByPseudo(string $pseudo): ?Participant
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.pseudo = :pseudo')
            ->setParameter('pseudo', $pseudo)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve tous les participants actifs d'un site donné
     */
    public function findActiveBySite(int $siteId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.site = :site')
            ->andWhere('p.actif = :actif')
            ->setParameter('site', $siteId)
            ->setParameter('actif', true)
            ->orderBy('p.nom', 'ASC')
            ->addOrderBy('p.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des participants par nom, prénom ou pseudo
     */
    public function search(string $search): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.nom LIKE :search OR p.prenom LIKE :search OR p.pseudo LIKE :search')
            ->andWhere('p.actif = :actif')
            ->setParameter('search', '%' . $search . '%')
            ->setParameter('actif', true)
            ->orderBy('p.nom', 'ASC')
            ->addOrderBy('p.prenom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Participant) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setMotDePasse($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
    
    /**
     * Trouve les participants avec pagination et recherche
     */
    public function findBySearchPaginated(string $search, int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('p');
        if (!empty($search)) {
            $qb->where('p.nom LIKE :search OR p.prenom LIKE :search OR p.pseudo LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        return $qb
            ->orderBy('p.nom', 'ASC')
            ->addOrderBy('p.prenom', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les participants avec recherche
     */
    public function countBySearch(string $search): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)');
        if (!empty($search)) {
            $qb->where('p.nom LIKE :search OR p.prenom LIKE :search OR p.pseudo LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        return $qb->getQuery()->getSingleScalarResult();
    }
}