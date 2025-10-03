<?php

namespace App\Command;

use App\Service\SortieCloturService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:cloture-sorties',
    description: 'Clôture automatiquement les sorties dont la date est passée.',
)]
class ClotureSortiesCommand extends Command
{
    public function __construct(
        private SortieCloturService $sortieCloturService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Clôture des sorties');

        try {
            // Afficher les statistiques avant
            $statsAvant = $this->sortieCloturService->getStatistiquesEtats();
            $io->section('État des sorties avant clôture');
            $io->table(['État', 'Nombre'], array_map(fn($etat, $count) => [$etat, $count], array_keys($statsAvant), $statsAvant));
            
            // Exécuter la clôture
            $io->section('Exécution de la clôture automatique');
            $count = $this->sortieCloturService->cloturerSortiesExpirees();
            
            // Afficher les résultats
            if ($count > 0) {
                $io->success("$count sorties clôturées avec succès.");
                
                // Afficher les statistiques après
                $statsApres = $this->sortieCloturService->getStatistiquesEtats();
                $io->section('État des sorties après clôture');
                $io->table(['État', 'Nombre'], array_map(fn($etat, $count) => [$etat, $count], array_keys($statsApres), $statsApres));
            } else {
                $io->info('Aucune sortie à clôturer.');
            }

            return Command::SUCCESS;

        } catch (\Throwable $th) {
            $io->error('Une erreur est survenue lors de la clôture des sorties : ' . $th->getMessage());
            return Command::FAILURE;
        }
    }
}