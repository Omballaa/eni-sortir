<?php

namespace App\Service;

use App\Entity\Message;
use App\Entity\GroupeMessage;
use App\Entity\Participant;
use App\Repository\MessageRepository;
use App\Repository\GroupeMessageRepository;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service WebSocket pour messaging temps réel
 * Architecture prête pour intégration WebSocket complète
 */
class WebSocketService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageRepository $messageRepository,
        private GroupeMessageRepository $groupeRepository,
        private ParticipantRepository $participantRepository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Diffuser un message système à un groupe
     * Version simplifiée - peut être étendue pour WebSocket temps réel
     */
    public function broadcastSystemMessage(int $groupeId, string $contenu): void
    {
        // Pour l'instant, on log le message
        // Future implémentation: diffuser via WebSocket en temps réel
        $this->logger->info("Message système pour groupe {$groupeId}: {$contenu}");
        
        // Optionnel: sauvegarder le message système en base
        $groupe = $this->groupeRepository->find($groupeId);
        if ($groupe) {
            $message = new Message();
            $message->setContenu($contenu);
            $message->setGroupe($groupe);
            $message->setDateEnvoi(new \DateTime());
            $message->setEstSysteme(true);
            
            $this->entityManager->persist($message);
            $this->entityManager->flush();
        }
    }

    /**
     * Notifier qu'un utilisateur rejoint un groupe
     */
    public function notifyUserJoinedGroup(int $groupeId, Participant $participant): void
    {
        $message = "{$participant->getPseudo()} a rejoint la discussion";
        $this->broadcastSystemMessage($groupeId, $message);
    }

    /**
     * Notifier qu'un utilisateur quitte un groupe
     */
    public function notifyUserLeftGroup(int $groupeId, Participant $participant): void
    {
        $message = "{$participant->getPseudo()} a quitté la discussion";
        $this->broadcastSystemMessage($groupeId, $message);
    }

    /**
     * Notifier d'une publication de sortie
     */
    public function notifySortiePublished(int $groupeId, string $sortieNom): void
    {
        $message = "🎉 La sortie \"{$sortieNom}\" est maintenant ouverte aux inscriptions !";
        $this->broadcastSystemMessage($groupeId, $message);
    }

    /**
     * Notifier d'une annulation de sortie
     */
    public function notifierSortieCancelled(int $groupeId, string $sortieNom, string $motif): void
    {
        $message = "❌ La sortie \"{$sortieNom}\" a été annulée.\nMotif: {$motif}";
        $this->broadcastSystemMessage($groupeId, $message);
    }

    /**
     * Préparer les données pour l'interface temps réel
     */
    public function getGroupesWithNotifications(Participant $participant): array
    {
        $groupes = $this->groupeRepository->findGroupesForParticipant($participant);
        $result = [];

        foreach ($groupes as $groupe) {
            $messagesNonLus = $this->groupeRepository->countMessagesNonLus($groupe, $participant);
            
            // Récupérer le dernier message du groupe
            $dernierMessage = $this->messageRepository->findOneBy(
                ['groupe' => $groupe],
                ['dateEnvoi' => 'DESC']
            );
            
            $result[] = [
                'id' => $groupe->getId(),
                'nom' => $groupe->getNom(),
                'messagesNonLus' => $messagesNonLus,
                'dernierMessage' => $dernierMessage ? [
                    'contenu' => mb_substr($dernierMessage->getContenu(), 0, 50) . (mb_strlen($dernierMessage->getContenu()) > 50 ? '...' : ''),
                    'dateEnvoi' => $dernierMessage->getDateEnvoi(),
                    'expediteur' => $dernierMessage->getExpediteur() ? $dernierMessage->getExpediteur()->getPseudo() : 'Système',
                    'estSysteme' => $dernierMessage->isEstSysteme()
                ] : null,
                'sortie' => $groupe->getSortie() ? [
                    'id' => $groupe->getSortie()->getId(),
                    'nom' => $groupe->getSortie()->getNom(),
                    'date' => $groupe->getSortie()->getDateHeureDebut(),
                    'etat' => $groupe->getSortie()->getEtat()->getLibelle()
                ] : null
            ];
        }

        // Trier par messages non lus (descendant) puis par date du dernier message (descendant)
        usort($result, function($a, $b) {
            if ($a['messagesNonLus'] !== $b['messagesNonLus']) {
                return $b['messagesNonLus'] - $a['messagesNonLus'];
            }
            
            $dateA = $a['dernierMessage'] ? $a['dernierMessage']['dateEnvoi'] : new \DateTime('2000-01-01');
            $dateB = $b['dernierMessage'] ? $b['dernierMessage']['dateEnvoi'] : new \DateTime('2000-01-01');
            
            return $dateB <=> $dateA;
        });

        return $result;
    }

    /**
     * Marquer tous les messages d'un groupe comme lus pour un participant
     */
    public function marquerMessagesLus(int $groupeId, Participant $participant): void
    {
        $groupe = $this->groupeRepository->find($groupeId);
        if (!$groupe) {
            return;
        }

        // Marquer tous les messages non lus comme lus
        $messages = $this->messageRepository->findBy(['groupe' => $groupe]);
        
        foreach ($messages as $message) {
            if (!$message->estLuPar($participant)) {
                $message->marquerLuPar($participant);
            }
        }
        
        $this->entityManager->flush();
        
        $this->logger->info("Messages du groupe {$groupeId} marqués comme lus pour {$participant->getPseudo()}");
    }

    /**
     * Obtenir le nombre total de messages non lus pour un participant
     */
    public function getTotalMessagesNonLus(Participant $participant): int
    {
        return $this->messageRepository->countTotalMessagesNonLus($participant);
    }

    /**
     * Vérifier si un participant peut envoyer des messages dans un groupe
     */
    public function peutEnvoyerMessage(int $groupeId, Participant $participant): bool
    {
        $groupe = $this->groupeRepository->find($groupeId);
        
        if (!$groupe) {
            return false;
        }
        
        // Vérifier l'appartenance au groupe
        if (!$groupe->aCommeMembreParticipant($participant)) {
            return false;
        }
        
        // Vérifier l'état de la sortie si applicable
        if ($groupe->getSortie()) {
            $etatSortie = $groupe->getSortie()->getEtat()->getLibelle();
            
            // Empêcher l'envoi de messages si la sortie est annulée (sauf organisateur)
            if ($etatSortie === 'Annulée') {
                return $groupe->getSortie()->getOrganisateur()->getId() === $participant->getId();
            }
        }
        
        return true;
    }

    /**
     * Créer un message dans un groupe
     * Utilisé par les contrôleurs pour envoyer des messages
     */
    public function creerMessage(int $groupeId, Participant $expediteur, string $contenu): ?Message
    {
        if (!$this->peutEnvoyerMessage($groupeId, $expediteur)) {
            return null;
        }
        
        $groupe = $this->groupeRepository->find($groupeId);
        if (!$groupe) {
            return null;
        }
        
        $message = new Message();
        $message->setContenu(trim($contenu));
        $message->setExpediteur($expediteur);
        $message->setGroupe($groupe);
        $message->setDateEnvoi(new \DateTime());
        $message->setEstSysteme(false);
        
        $this->entityManager->persist($message);
        $this->entityManager->flush();
        
        $this->logger->info("Message créé dans le groupe {$groupeId} par {$expediteur->getPseudo()}");
        
        return $message;
    }
}