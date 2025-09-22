<?php

namespace App\Command;

use App\Entity\Site;
use App\Entity\Ville;
use App\Entity\Lieu;
use App\Entity\Etat;
use App\Entity\Participant;
use App\Entity\Sortie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:load-test-data',
    description: 'Load test data for the SORTIES application',
)]
class LoadTestDataCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Loading test data for SORTIES application');
        
        $io->progressStart(6);

        // Create Sites
        $sites = [
            'Campus Nantes',
            'Campus Rennes',
            'Campus Quimper',
            'Campus Niort',
        ];

        $siteEntities = [];
        foreach ($sites as $siteName) {
            $site = new Site();
            $site->setNomSite($siteName);
            $this->entityManager->persist($site);
            $siteEntities[] = $site;
        }
        $io->progressAdvance();

        // Create Villes
        $villes = [
            ['Nantes', '44000'],
            ['Rennes', '35000'],
            ['Quimper', '29000'],
            ['Niort', '79000'],
            ['Saint-Nazaire', '44600'],
            ['Vannes', '56000'],
        ];

        $villeEntities = [];
        foreach ($villes as [$nomVille, $codePostal]) {
            $ville = new Ville();
            $ville->setNomVille($nomVille);
            $ville->setCodePostal($codePostal);
            $this->entityManager->persist($ville);
            $villeEntities[] = $ville;
        }
        $io->progressAdvance();

        // Create Lieux
        $lieux = [
            ['Centre Beaulieu', 'Avenue des Thébaudières', $villeEntities[0]],
            ['Parc de Procé', 'Rue des Dervalières', $villeEntities[0]],
            ['Place de la Mairie', 'Place de la Mairie', $villeEntities[1]],
            ['Parc du Thabor', 'Place Saint-Mélaine', $villeEntities[1]],
            ['Centre-ville', 'Rue Kéréon', $villeEntities[2]],
            ['Escape Game', 'Rue de la Gare', $villeEntities[3]],
        ];

        $lieuEntities = [];
        foreach ($lieux as [$nomLieu, $rue, $ville]) {
            $lieu = new Lieu();
            $lieu->setNomLieu($nomLieu);
            $lieu->setRue($rue);
            $lieu->setVille($ville);
            $this->entityManager->persist($lieu);
            $lieuEntities[] = $lieu;
        }
        $io->progressAdvance();

        // Create Etats
        $etats = [
            'Créée',
            'Ouverte',
            'Clôturée',
            'Activité en cours',
            'Passée',
            'Annulée',
        ];

        $etatEntities = [];
        foreach ($etats as $libelle) {
            $etat = new Etat();
            $etat->setLibelle($libelle);
            $this->entityManager->persist($etat);
            $etatEntities[] = $etat;
        }
        $io->progressAdvance();

        // Create Participants
        $participants = [
            ['admin', 'Dupont', 'Jean', 'jean.dupont@eni-ecole.fr', true, $siteEntities[0]],
            ['jsmith', 'Smith', 'John', 'john.smith@eni-ecole.fr', false, $siteEntities[0]],
            ['mmartin', 'Martin', 'Marie', 'marie.martin@eni-ecole.fr', false, $siteEntities[1]],
            ['pdurand', 'Durand', 'Pierre', 'pierre.durand@eni-ecole.fr', false, $siteEntities[1]],
            ['sleblanc', 'Leblanc', 'Sophie', 'sophie.leblanc@eni-ecole.fr', false, $siteEntities[2]],
        ];

        $participantEntities = [];
        foreach ($participants as [$pseudo, $nom, $prenom, $mail, $isAdmin, $site]) {
            $participant = new Participant();
            $participant->setPseudo($pseudo);
            $participant->setNom($nom);
            $participant->setPrenom($prenom);
            $participant->setMail($mail);
            $participant->setTelephone('0123456789');
            $participant->setAdministrateur($isAdmin);
            $participant->setActif(true);
            $participant->setSite($site);
            
            // Set default password as "password123"
            $hashedPassword = $this->passwordHasher->hashPassword($participant, 'password123');
            $participant->setMotDePasse($hashedPassword);
            
            $this->entityManager->persist($participant);
            $participantEntities[] = $participant;
        }
        $io->progressAdvance();

        // Create Sorties
        $sorties = [
            [
                'Château des Ducs',
                new \DateTime('+7 days 14:00'),
                120, // 2 heures
                new \DateTime('+5 days'),
                20,
                'Visite guidée du château avec accès aux remparts',
                $etatEntities[1], // Ouverte
                $lieuEntities[0],
                $participantEntities[0],
            ],
            [
                'Laser Game',
                new \DateTime('+14 days 19:30'),
                90, // 1h30
                new \DateTime('+12 days'),
                12,
                'Partie de laser game entre collègues',
                $etatEntities[1], // Ouverte
                $lieuEntities[5],
                $participantEntities[1],
            ],
            [
                'Randonnée Brocéliande',
                new \DateTime('+21 days 09:00'),
                480, // 8 heures
                new \DateTime('+19 days'),
                15,
                'Randonnée guidée dans la mythique forêt de Brocéliande',
                $etatEntities[0], // Créée
                $lieuEntities[3],
                $participantEntities[2],
            ],
        ];

        foreach ($sorties as [$nom, $dateHeureDebut, $duree, $dateLimiteInscription, $nbMax, $infos, $etat, $lieu, $organisateur]) {
            $sortie = new Sortie();
            $sortie->setNom($nom);
            $sortie->setDateHeureDebut($dateHeureDebut);
            $sortie->setDuree($duree);
            $sortie->setDateLimiteInscription($dateLimiteInscription);
            $sortie->setNbInscriptionsMax($nbMax);
            $sortie->setInfosSortie($infos);
            $sortie->setEtat($etat);
            $sortie->setLieu($lieu);
            $sortie->setOrganisateur($organisateur);
            
            $this->entityManager->persist($sortie);
        }
        $io->progressAdvance();

        // Persist all entities
        $this->entityManager->flush();
        
        $io->progressFinish();

        $io->success('Test data loaded successfully!');
        $io->note('Default password for all users: password123');
        $io->note('Admin user: admin / password123');

        return Command::SUCCESS;
    }
}