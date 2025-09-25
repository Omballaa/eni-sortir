<?php

namespace App\Command;

use App\Entity\Sortie;
use App\Entity\Participant;
use App\Service\MessagerieSortieService;
use App\Service\WebSocketService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-data-messaging',
    description: 'Crée des données de test pour le système de messagerie',
)]
class CreateTestDataMessagingCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessagerieSortieService $messagerieSortieService,
        private WebSocketService $webSocketService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Création des données de test pour la messagerie');

        try {
            // Récupérer des sorties et participants existants
            $sorties = $this->entityManager->getRepository(Sortie::class)->findAll();
            $participants = $this->entityManager->getRepository(Participant::class)->findAll();

            if (empty($sorties)) {
                $io->error('Aucune sortie trouvée. Créez d\'abord des sorties avant d\'exécuter cette commande.');
                return Command::FAILURE;
            }

            if (count($participants) < 2) {
                $io->error('Il faut au moins 2 participants pour tester la messagerie.');
                return Command::FAILURE;
            }

            $io->section('Création des groupes de discussion');

            // Créer des groupes pour les 3 premières sorties
            $groupesCreated = 0;
            foreach (array_slice($sorties, 0, 3) as $sortie) {
                try {
                    $groupe = $this->messagerieSortieService->creerGroupePourSortie($sortie);
                    $io->success("Groupe créé pour la sortie : {$sortie->getNom()}");
                    $groupesCreated++;

                    // Ajouter quelques participants au groupe
                    $participantsToAdd = array_slice($participants, 0, min(4, count($participants)));
                    foreach ($participantsToAdd as $participant) {
                        if ($participant->getId() !== $sortie->getOrganisateur()->getId()) {
                            $this->messagerieSortieService->ajouterParticipantAuGroupe($sortie, $participant);
                            $io->text("Participant ajouté : {$participant->getPseudo()}");
                        }
                    }

                    // Créer quelques messages de test
                    $this->creerMessagesTest($groupe, $participantsToAdd, $io);

                } catch (\Exception $e) {
                    $io->warning("Groupe probablement déjà existant pour la sortie : {$sortie->getNom()} - {$e->getMessage()}");
                }
            }

            if ($groupesCreated > 0) {
                $io->success("$groupesCreated groupes de discussion créés avec succès !");
            } else {
                $io->info("Aucun nouveau groupe créé (groupes déjà existants ou erreur)");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors de la création des données de test : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function creerMessagesTest($groupe, array $participants, SymfonyStyle $io): void
    {
        $messages = [
            'Salut tout le monde ! Hâte de participer à cette sortie !',
            'Quelqu\'un sait à quelle heure on se retrouve exactement ?',
            'J\'ai vérifié la météo, ça devrait être parfait !',
            'N\'oubliez pas d\'apporter de l\'eau !',
            'Super, j\'ai hâte d\'y être 😊'
        ];

        foreach ($messages as $index => $contenu) {
            if (isset($participants[$index % count($participants)])) {
                $participant = $participants[$index % count($participants)];
                
                $message = $this->webSocketService->creerMessage($groupe->getId(), $participant, $contenu);
                
                if ($message) {
                    $io->text("Message créé par {$participant->getPseudo()}");
                } else {
                    $io->warning("Erreur lors de la création du message par {$participant->getPseudo()}");
                }
            }
        }
    }
}