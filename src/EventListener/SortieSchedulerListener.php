<?php

namespace App\EventListener;

use App\Service\SortieCloturService;
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
        private SortieCloturService $sortieCloturService,
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
            $count = $this->sortieCloturService->cloturerSortiesExpirees();
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'exécution automatique de la clôture des sorties via EventListener', [
                'exception' => $e->getMessage()
            ]);
        }
    }
}