<?php

require_once __DIR__.'/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

$doctrine = $container->get('doctrine');
$em = $doctrine->getManager();

// Test de création de groupe pour sortie
$sortieRepo = $doctrine->getRepository('App\Entity\Sortie');
$groupeRepo = $doctrine->getRepository('App\Entity\GroupeMessage');

echo "=== Debug inscription sortie ===\n";

// Récupérer une sortie sans groupe
$sorties = $sortieRepo->findAll();
echo "Nombre total de sorties : " . count($sorties) . "\n";

foreach ($sorties as $sortie) {
    $groupe = $groupeRepo->findBySortie($sortie);
    if (!$groupe) {
        echo "Sortie ID " . $sortie->getId() . " (" . $sortie->getNom() . ") n'a pas de groupe\n";
        
        // Créer le groupe manuellement
        $groupe = new \App\Entity\GroupeMessage();
        $groupe->setNom('Sortie : ' . $sortie->getNom());
        $groupe->setDescription('Groupe de discussion pour la sortie "' . $sortie->getNom() . '"');
        $groupe->setType('sortie');
        $groupe->setSortie($sortie);
        $groupe->setCreateur($sortie->getOrganisateur());
        $groupe->setDateCreation(new \DateTime());
        
        // Ajouter l'organisateur comme administrateur
        $groupe->ajouterParticipant($sortie->getOrganisateur(), true);
        
        $em->persist($groupe);
        
        // Ajouter tous les participants inscrits au groupe
        foreach ($sortie->getInscriptions() as $inscription) {
            $participant = $inscription->getParticipant();
            if ($participant->getId() !== $sortie->getOrganisateur()->getId()) {
                $groupe->ajouterParticipant($participant, false);
                echo "  -> Participant " . $participant->getPseudo() . " ajouté\n";
            }
        }
        
        $em->flush();
        echo "  -> Groupe créé avec ID " . $groupe->getId() . "\n";
        
        // Créer message système
        $message = new \App\Entity\Message();
        $message->setGroupe($groupe);
        $message->setContenu("🎉 Groupe créé pour la sortie \"{$sortie->getNom()}\" !\n\nTous les participants inscrits ont été ajoutés automatiquement.");
        $message->setEstSysteme(true);
        $message->setTypeSysteme('groupe_cree');
        $message->setExpediteur($groupe->getCreateur());
        $message->setDateEnvoi(new \DateTime());
        
        $em->persist($message);
        $em->flush();
        echo "  -> Message système créé\n";
        
        break; // Ne traiter qu'une sortie pour le test
    }
}

echo "Debug terminé\n";