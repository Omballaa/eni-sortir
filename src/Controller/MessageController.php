<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\GroupeMessage;
use App\Entity\Message;
use App\Repository\GroupeMessageRepository;
use App\Repository\MessageRepository;
use App\Service\MessagerieSortieService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/messages')]
class MessageController extends AbstractController
{


    #[Route('/groupe/{id}/send', name: 'app_messages_send', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function sendMessage(
        GroupeMessage $groupe, 
        Request $request,
        EntityManagerInterface $entityManager,
        MessagerieSortieService $messagerie
    ): JsonResponse {
        /** @var Participant $user */
        $user = $this->getUser();

        // Vérifier que l'utilisateur fait partie du groupe
        if (!$groupe->aCommeMembreParticipant($user)) {
            return $this->json(['success' => false, 'message' => 'Accès refusé.']);
        }

        $content = trim($request->request->get('message', ''));
        if (empty($content)) {
            return $this->json(['success' => false, 'message' => 'Le message ne peut pas être vide.']);
        }

        if (strlen($content) > 500) {
            return $this->json(['success' => false, 'message' => 'Le message est trop long (maximum 500 caractères).']);
        }

        try {
            // Créer le message
            $message = new Message();
            $message->setContenu($content);
            $message->setExpediteur($user);
            $message->setGroupe($groupe);
            $message->setDateEnvoi(new \DateTime());
            $message->setEstSysteme(false);

            $entityManager->persist($message);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => [
                    'id' => $message->getId(),
                    'contenu' => $message->getContenu(),
                    'auteur' => $message->getExpediteur()->getPseudo(),
                    'dateEnvoi' => $this->formatDateForTimezone($message->getDateEnvoi()),
                    'estSysteme' => $message->isEstSysteme()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Erreur lors de l\'envoi du message.']);
        }
    }

    #[Route('/groupe/{id}/messages', name: 'app_messages_load', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function loadMessages(
        GroupeMessage $groupe, 
        MessageRepository $messageRepo,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var Participant $user */
        $user = $this->getUser();

        // Vérifier que l'utilisateur fait partie du groupe
        if (!$groupe->aCommeMembreParticipant($user)) {
            return $this->json(['success' => false, 'message' => 'Accès refusé.']);
        }

        $lastMessageId = $request->query->getInt('lastId', 0);
        $messages = $messageRepo->findNewMessagesForGroupe($groupe, $lastMessageId);

        $messagesData = [];
        $hasChanges = false;
        
        foreach ($messages as $message) {
            // Marquer comme lu si pas déjà lu
            if (!$message->estLuPar($user)) {
                $message->marquerLuPar($user);
                $hasChanges = true;
            }

            $messagesData[] = [
                'id' => $message->getId(),
                'contenu' => $message->getContenu(),
                'auteur' => $message->getExpediteur()->getPseudo(),
                'dateEnvoi' => $this->formatDateForTimezone($message->getDateEnvoi()),
                'estSysteme' => $message->isEstSysteme()
            ];
        }

        // Sauvegarder les changements de statut de lecture
        if ($hasChanges) {
            $entityManager->flush();
        }

        return $this->json([
            'success' => true,
            'messages' => $messagesData
        ]);
    }

    #[Route('/notifications', name: 'app_messages_notifications', methods: ['GET'])]
    public function getNotifications(GroupeMessageRepository $groupeRepo): JsonResponse
    {
        /** @var Participant $user */
        $user = $this->getUser();

        $groupes = $groupeRepo->findGroupesForParticipant($user);
        $totalNonLus = 0;

        $notifications = [];
        foreach ($groupes as $groupe) {
            $nonLus = $groupeRepo->countMessagesNonLus($groupe, $user);
            
            $notifications[] = [
                'groupeId' => $groupe->getId(),
                'groupeNom' => $groupe->getNom(),
                'messagesNonLus' => $nonLus,
                'sortie' => $groupe->getSortie() ? [
                    'id' => $groupe->getSortie()->getId(),
                    'nom' => $groupe->getSortie()->getNom(),
                    'date' => $groupe->getSortie()->getDateHeureDebut()->format('d/m/Y H:i')
                ] : null,
                'dateCreation' => $groupe->getDateCreation() ? $groupe->getDateCreation()->format('d/m/Y') : null
            ];
            
            $totalNonLus += $nonLus;
        }

        return $this->json([
            'totalNonLus' => $totalNonLus,
            'notifications' => $notifications
        ]);
    }

    /**
     * Formate une date avec le fuseau horaire Europe/Paris
     */
    private function formatDateForTimezone(\DateTime $date): string
    {
        // Cloner la date pour ne pas modifier l'original
        $localDate = clone $date;
        
        // Définir le fuseau horaire Europe/Paris
        $localDate->setTimezone(new \DateTimeZone($this->getParameter('app.timezone')));
        
        return $localDate->format('d/m/Y H:i');
    }
}