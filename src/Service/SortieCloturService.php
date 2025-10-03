<?php

namespace App\Service;

use App\Entity\Etat;
use App\Entity\Sortie;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SortieCloturService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    /**
     * Clôture automatiquement les sorties dont la date est passée
     * 
     * @return int Nombre de sorties clôturées
     */
    public function cloturerSortiesExpirees(): int
    {
        try {
            $now = new \DateTime();
            $sorties = $this->entityManager->getRepository(Sortie::class)->findAll();
            $etatCloturee = $this->entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Clôturée']);
            
            if (!$etatCloturee) {
                $this->logger->error('L\'état "Clôturée" est introuvable dans la base de données');
                throw new \RuntimeException('État "Clôturée" introuvable');
            }

            $count = 0;
            foreach ($sorties as $sortie) {
                if ($this->doitEtreCloturee($sortie, $now)) {
                    $sortie->setEtat($etatCloturee);
                    $count++;
                }
            }
            
            if ($count > 0) {
                $this->entityManager->flush();
                $this->logger->info("Clôture automatique: {$count} sorties clôturées");
            } else {
                $this->logger->debug("Clôture automatique: aucune sortie à clôturer");
            }
            
            return $count;
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'exécution automatique de la clôture des sorties', [
                'exception' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Détermine si une sortie doit être clôturée
     */
    private function doitEtreCloturee(Sortie $sortie, \DateTime $now): bool
    {
        return $sortie->getDateHeureDebut() < $now &&
               $sortie->getEtat()->getLibelle() !== 'Annulée' &&
               $sortie->getEtat()->getLibelle() !== 'Créée' &&
               $sortie->getEtat()->getLibelle() !== 'Clôturée';
    }

    /**
     * Obtient les statistiques des sorties par état
     */
    public function getStatistiquesEtats(): array
    {
        $sorties = $this->entityManager->getRepository(Sortie::class)->findAll();
        $stats = [];
        
        foreach ($sorties as $sortie) {
            $etat = $sortie->getEtat()->getLibelle();
            $stats[$etat] = ($stats[$etat] ?? 0) + 1;
        }
        
        return $stats;
    }
}