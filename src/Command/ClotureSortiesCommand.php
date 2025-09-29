<?php

namespace App\Command;

use App\Entity\Etat;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:cloture-sorties',
    description: 'Clôture automatiquement les sorties dont la date est passée.',
)]
class ClotureSortiesCommand extends Command
{

    public function __construct(
        private SortieRepository $sortieRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Clôture des sorties');

        try {
            $sorties = $this->entityManager->getRepository(Sortie::class)->findAll();
            $etatCloturee = $this->entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Clôturée']);
            
            if (empty($sorties)) {
                $io->error('Aucune sortie trouvée.');
                return Command::FAILURE;
            }

            if (!$etatCloturee) {
                $io->error('L\'état "Clôturée" est introuvable. Veuillez vérifier les données de l\'application.');
                return Command::FAILURE;
            }

            $io->section('Vérification des sorties à clôturer');
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
            
        $this->entityManager->flush();
        $io->success("$count sorties clôturées.");

        return Command::SUCCESS;


        } catch (\Throwable $th) {
            $io->error('Une erreur est survenue lors de la clôture des sorties.');
            return Command::FAILURE;
        }
    }
}