<?php

namespace App\EventListener;

use App\Entity\Etat;
use App\Entity\Sortie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Psr\Log\LoggerInterface;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 1000)]
class SortieSchedulerListener
{
    private static bool $initialized = false;
    private static ?int $lastExecution = null;
    
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        // S'exécuter seulement pour les requêtes principales
        if (!$event->isMainRequest()) {
            return;
        }

        $now = time();
        
        // Première exécution au démarrage
        if (!self::$initialized) {
            self::$initialized = true;
            self::$lastExecution = $now;
            $this->executeClotureSorties();
            return;
        }
        
        // Exécuter toutes les heures (3600 secondes)
        if (self::$lastExecution && ($now - self::$lastExecution) >= 3600) {
            self::$lastExecution = $now;
            $this->executeClotureSorties();
        }
    }
    
    private function executeClotureSorties(): void
    {
        try {
            $now = new \DateTime();
            $sorties = $this->entityManager->getRepository(Sortie::class)->findAll();
            $etatCloturee = $this->entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Clôturée']);
            
            if (!$etatCloturee) {
                $this->logger->error('L\'état "Clôturée" est introuvable dans la base de données');
                return;
            }

            $count = 0;
            foreach ($sorties as $sortie) {
                if (
                    $sortie->getDateHeureDebut() < $now &&
                    $sortie->getEtat()->getLibelle() !== 'Annulée' &&
                    $sortie->getEtat()->getLibelle() !== 'Clôturée'
                ) {
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
            
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'exécution automatique de la clôture des sorties', [
                'exception' => $e->getMessage()
            ]);
        }
    }
}