<?php

namespace App\Service;

use App\Entity\GroupeMessage;
use App\Entity\Message;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Repository\GroupeMessageRepository;
use App\Repository\MessageRepository;
use App\Repository\GroupeMembreRepository;
use Doctrine\ORM\EntityManagerInterface;

class MessagerieSortieService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GroupeMessageRepository $groupeRepository,
        private MessageRepository $messageRepository,
        private GroupeMembreRepository $membreRepository
    ) {}

    /**
     * Crée automatiquement un groupe pour une sortie
     */
    public function creerGroupePourSortie(Sortie $sortie): GroupeMessage
    {
        // Vérifier si un groupe existe déjà
        $groupe = $this->groupeRepository->findBySortie($sortie);
        if ($groupe) {
            return $groupe;
        }

        // Créer le nouveau groupe
        $groupe = new GroupeMessage();
        $groupe->setNom('Sortie : ' . $sortie->getNom());
        $groupe->setDescription('Groupe de discussion pour la sortie "' . $sortie->getNom() . '"');
        $groupe->setType('sortie');
        $groupe->setSortie($sortie);
        $groupe->setCreateur($sortie->getOrganisateur());
        
        // Ajouter l'organisateur comme administrateur
        $groupe->ajouterParticipant($sortie->getOrganisateur(), true);

        $this->entityManager->persist($groupe);
        $this->entityManager->flush();

        // Message système de création
        $this->ajouterMessageSysteme(
            $groupe, 
            "Groupe créé pour la sortie \"{$sortie->getNom()}\"", 
            'groupe_cree'
        );

        return $groupe;
    }

    /**
     * Ajoute un participant au groupe de la sortie (lors de l'inscription)
     */
    public function ajouterParticipantAuGroupe(Sortie $sortie, Participant $participant): void
    {
        $groupe = $this->groupeRepository->findBySortie($sortie);
        if (!$groupe) {
            $groupe = $this->creerGroupePourSortie($sortie);
        }

        // Ajouter le participant au groupe
        $groupe->ajouterParticipant($participant, false);
        $this->entityManager->flush();

        // Message système d'arrivée
        $this->ajouterMessageSysteme(
            $groupe, 
            "{$participant->getPrenom()} {$participant->getNom()} a rejoint la sortie", 
            'participant_rejoint'
        );
    }

    /**
     * Retire un participant du groupe de la sortie (lors de la désinscription)
     */
    public function retirerParticipantDuGroupe(Sortie $sortie, Participant $participant): void
    {
        $groupe = $this->groupeRepository->findBySortie($sortie);
        if (!$groupe) {
            return;
        }

        // Retirer le participant du groupe
        $groupe->retirerParticipant($participant);
        $this->entityManager->flush();

        // Message système de départ
        $this->ajouterMessageSysteme(
            $groupe, 
            "{$participant->getPrenom()} {$participant->getNom()} a quitté la sortie", 
            'participant_quitte'
        );
    }

    /**
     * Gère l'annulation d'une sortie
     */
    public function gererAnnulationSortie(Sortie $sortie, string $motifAnnulation = ''): void
    {
        $groupe = $this->groupeRepository->findBySortie($sortie);
        if (!$groupe) {
            return;
        }

        // Message système d'annulation
        $messageAnnulation = "⚠️ La sortie \"{$sortie->getNom()}\" a été annulée";
        if ($motifAnnulation) {
            $messageAnnulation .= "\nMotif : " . $motifAnnulation;
        }
        $messageAnnulation .= "\n\nVous pouvez continuer à utiliser ce groupe pour échanger sur une éventuelle reprogrammation.";

        $this->ajouterMessageSysteme($groupe, $messageAnnulation, 'sortie_annulee');

        // Ne pas désactiver le groupe - permettre aux participants de continuer à discuter
    }

    /**
     * Gère la publication d'une sortie
     */
    public function gererPublicationSortie(Sortie $sortie): void
    {
        $groupe = $this->groupeRepository->findBySortie($sortie);
        if (!$groupe) {
            $groupe = $this->creerGroupePourSortie($sortie);
        }

        // Message système de publication
        $this->ajouterMessageSysteme(
            $groupe, 
            "🎉 La sortie \"{$sortie->getNom()}\" est maintenant ouverte aux inscriptions !", 
            'sortie_publiee'
        );
    }

    /**
     * Gère la clôture des inscriptions
     */
    public function gererClotureSortie(Sortie $sortie): void
    {
        $groupe = $this->groupeRepository->findBySortie($sortie);
        if (!$groupe) {
            return;
        }

        // Message système de clôture
        $this->ajouterMessageSysteme(
            $groupe, 
            "🔒 Les inscriptions pour la sortie \"{$sortie->getNom()}\" sont maintenant fermées", 
            'inscriptions_fermees'
        );
    }

    /**
     * Ajoute un message système dans un groupe
     */
    private function ajouterMessageSysteme(GroupeMessage $groupe, string $contenu, string $type): Message
    {
        $message = new Message();
        $message->setGroupe($groupe);
        $message->setContenu($contenu);
        $message->setEstSysteme(true);
        $message->setTypeSysteme($type);
        $message->setExpediteur($groupe->getCreateur()); // L'organisateur comme expéditeur système

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // TODO: Notifier via WebSocket si le service est actif
        // $this->webSocketService?->sendSystemMessage($groupe->getId(), $contenu, $type);

        return $message;
    }

    /**
     * Récupère les groupes d'un participant avec les messages non lus
     */
    public function getGroupesAvecNonLusPourParticipant(Participant $participant): array
    {
        return $this->groupeRepository->findGroupesAvecNonLusPourParticipant($participant);
    }

    /**
     * Marque tous les messages d'un groupe comme lus pour un participant
     */
    public function marquerMessagesLus(Participant $participant, GroupeMessage $groupe): void
    {
        $this->messageRepository->marquerTousLusDansGroupe($participant, $groupe);
        $this->membreRepository->mettreAJourDerniereVisite($participant, $groupe);
    }

    /**
     * Envoie un message dans un groupe
     */
    public function envoyerMessageGroupe(Participant $expediteur, GroupeMessage $groupe, string $contenu): Message
    {
        // Vérifier que le participant est membre du groupe
        if (!$groupe->aCommeMembreParticipant($expediteur)) {
            throw new \Exception('Vous n\'êtes pas membre de ce groupe');
        }

        $message = new Message();
        $message->setExpediteur($expediteur);
        $message->setGroupe($groupe);
        $message->setContenu($contenu);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // TODO: Notifier via WebSocket
        // $this->webSocketService?->broadcastMessage($message);

        return $message;
    }

    /**
     * Envoie un message privé entre deux participants
     */
    public function envoyerMessagePrive(Participant $expediteur, Participant $destinataire, string $contenu): Message
    {
        $message = new Message();
        $message->setExpediteur($expediteur);
        $message->setDestinataire($destinataire);
        $message->setContenu($contenu);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        // TODO: Notifier via WebSocket
        // $this->webSocketService?->sendToUser($destinataire->getId(), $messageData);

        return $message;
    }

    /**
     * Récupère l'historique des messages d'un groupe
     */
    public function getHistoriqueGroupe(GroupeMessage $groupe, int $page = 1, int $limit = 50): array
    {
        return $this->messageRepository->findByGroupeWithPagination($groupe, $page, $limit);
    }

    /**
     * Récupère l'historique des messages privés entre deux participants
     */
    public function getHistoriqueMessagesPrive(Participant $participant1, Participant $participant2, int $limit = 50): array
    {
        return $this->messageRepository->findMessagesPrivesEntre($participant1, $participant2, $limit);
    }

    /**
     * Compte le nombre total de messages non lus pour un participant
     */
    public function countMessagesNonLus(Participant $participant): int
    {
        return $this->messageRepository->countTousMessagesNonLusPourParticipant($participant);
    }

    /**
     * Crée un groupe privé entre participants
     */
    public function creerGroupePrive(array $participants, string $nom, Participant $createur): GroupeMessage
    {
        $groupe = new GroupeMessage();
        $groupe->setNom($nom);
        $groupe->setType('prive');
        $groupe->setCreateur($createur);

        // Ajouter tous les participants
        foreach ($participants as $participant) {
            $estAdmin = ($participant === $createur);
            $groupe->ajouterParticipant($participant, $estAdmin);
        }

        $this->entityManager->persist($groupe);
        $this->entityManager->flush();

        // Message système de création
        $nomsParticipants = array_map(fn($p) => $p->getPrenom() . ' ' . $p->getNom(), $participants);
        $this->ajouterMessageSysteme(
            $groupe, 
            "Groupe privé créé avec : " . implode(', ', $nomsParticipants), 
            'groupe_prive_cree'
        );

        return $groupe;
    }
}