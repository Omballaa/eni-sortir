<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Sortie;
use App\Entity\Participant;
use App\Form\SortieType;
use App\Form\CancelSortieType;
use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
use App\Service\MessagerieSortieService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/sortie')]
#[IsGranted('ROLE_USER')]
class SortieController extends AbstractController
{
    #[Route('/nouvelle', name: 'app_sortie_create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, EtatRepository $etatRepository): Response
    {
        $sortie = new Sortie();
        
        /** @var Participant $user */
        $user = $this->getUser();
        $sortie->setOrganisateur($user);
        
        // État initial : "Créée"
        $etatCreee = $etatRepository->findOneBy(['libelle' => 'Créée']);
        if ($etatCreee) {
            $sortie->setEtat($etatCreee);
        }

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($sortie);
            $entityManager->flush();

            $this->addFlash('success', 'La sortie a été créée avec succès !');

            return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
        }

        return $this->render('sortie/create.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_sortie_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Sortie $sortie): Response
    {
        /** @var Participant $user */
        $user = $this->getUser();
        $isOrganisateur = $sortie->getOrganisateur()->getId() === $user->getId();
        
        // Vérifier si l'utilisateur est inscrit
        $isInscrit = false;
        foreach ($sortie->getInscriptions() as $inscription) {
            if ($inscription->getParticipant()->getId() === $user->getId()) {
                $isInscrit = true;
                break;
            }
        }

        return $this->render('sortie/show.html.twig', [
            'sortie' => $sortie,
            'isOrganisateur' => $isOrganisateur,
            'isInscrit' => $isInscrit,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_sortie_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        /** @var Participant $user */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est l'organisateur
        if ($sortie->getOrganisateur()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que vos propres sorties.');
        }

        // Vérifier que la sortie peut encore être modifiée (uniquement état "Créée")
        $etatsModifiables = ['Créée'];
        if (!in_array($sortie->getEtat()->getLibelle(), $etatsModifiables)) {
            $this->addFlash('error', 'Cette sortie ne peut plus être modifiée. Seules les sorties en état "Créée" peuvent être modifiées.');
            return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
        }

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La sortie a été modifiée avec succès !');

            return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
        }

        return $this->render('sortie/edit.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/annuler', name: 'app_sortie_cancel', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function cancel(Request $request, Sortie $sortie, EntityManagerInterface $entityManager, EtatRepository $etatRepository, MessagerieSortieService $messagerie): Response
    {
        /** @var Participant $user */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est l'organisateur
          $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        if ($sortie->getOrganisateur()->getId() !== $user->getId() && !$isAdmin) {
            throw $this->createAccessDeniedException('Vous ne pouvez annuler que vos propres sorties.');
        }

        // Vérifier que la sortie peut être annulée
        $etatsAnnulables = ['Ouverte'];
        if (!in_array($sortie->getEtat()->getLibelle(), $etatsAnnulables)) {
            $this->addFlash('error', 'Cette sortie ne peut pas être annulée.');
            return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
        }

        $form = $this->createForm(CancelSortieType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            
            // Changer l'état vers "Annulée"
            $etatAnnulee = $etatRepository->findOneBy(['libelle' => 'Annulée']);
            if ($etatAnnulee) {
                $sortie->setEtat($etatAnnulee);
                
                // Ajouter le motif d'annulation aux infos
                $infosActuelles = $sortie->getInfosSortie() ?: '';
                $motifAnnulation = "\n\n--- SORTIE ANNULÉE ---\nMotif : " . $data['motifAnnulation'];
                $sortie->setInfosSortie($infosActuelles . $motifAnnulation);
                
                $entityManager->flush();

                // Gérer l'annulation dans le système de messagerie
                $messagerie->gererAnnulationSortie($sortie, $data['motifAnnulation']);
                
                // Retourner JSON pour les appels AJAX
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => true,
                        'message' => 'La sortie a été annulée avec succès.',
                        'redirect' => $this->generateUrl('app_home')
                    ]);
                }
                
                $this->addFlash('success', 'La sortie a été annulée.');
                return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
            } else {
                // Erreur lors de l'annulation
                if ($request->isXmlHttpRequest()) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => 'Erreur lors de l\'annulation de la sortie.'
                    ]);
                }
                $this->addFlash('error', 'Erreur lors de l\'annulation de la sortie.');
            }
        }
        
        // Gestion des erreurs de validation pour AJAX
        if ($form->isSubmitted() && !$form->isValid() && $request->isXmlHttpRequest()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $errors
            ]);
        }

        return $this->render('sortie/cancel.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_sortie_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Sortie $sortie, EntityManagerInterface $entityManager): Response
    {
        /** @var Participant $user */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est l'organisateur
        if ($sortie->getOrganisateur()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez supprimer que vos propres sorties.');
        }

        // Vérifier que la sortie peut être supprimée (seulement si état "Créée" et aucune inscription)
        if ($sortie->getEtat()->getLibelle() !== 'Créée' || $sortie->getNbInscriptions() > 0) {
            $this->addFlash('error', 'Cette sortie ne peut pas être supprimée.');
            return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
        }

        $entityManager->remove($sortie);
        $entityManager->flush();

        $this->addFlash('success', 'La sortie a été supprimée.');

        return $this->redirectToRoute('app_home');
    }

    // ===== ROUTES POUR LES MODALES =====

    #[Route('/nouvelle/modal', name: 'app_sortie_create_modal', methods: ['GET', 'POST'])]
    public function createModal(Request $request, EtatRepository $etatRepository, EntityManagerInterface $entityManager, MessagerieSortieService $messagerie): Response
    {
        error_log("SortieController::createModal - Method: " . $request->getMethod());
        
        $sortie = new Sortie();
        
        /** @var Participant $user */
        $user = $this->getUser();
        $sortie->setOrganisateur($user);
        
        // État initial : "Créée"
        $etatCreee = $etatRepository->findOneBy(['libelle' => 'Créée']);
        if ($etatCreee) {
            $sortie->setEtat($etatCreee);
        }

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            error_log("SortieController::createModal - Formulaire soumis et valide");
            
            // Récupérer l'action demandée (save ou publish)
            $action = $request->request->get('action', 'save');
            error_log("SortieController::createModal - Action: " . $action);
            
            if ($action === 'publish') {
                // Publier la sortie (changer l'état à "Ouverte")
                $etatOuverte = $etatRepository->findOneBy(['libelle' => 'Ouverte']);
                if ($etatOuverte) {
                    $sortie->setEtat($etatOuverte);
                    error_log("SortieController::createModal - État changé à Ouverte");
                }
            }
            
            $entityManager->persist($sortie);
            $entityManager->flush();
            error_log("SortieController::createModal - Sortie créée avec ID: " . $sortie->getId());

            // Créer automatiquement le groupe de discussion
            $messagerie->creerGroupePourSortie($sortie);
            
            // Si la sortie est publiée, envoyer le message système
            if ($action === 'publish') {
                $messagerie->gererPublicationSortie($sortie);
            }

            $message = $action === 'publish' ? 'La sortie a été créée et publiée avec succès !' : 'La sortie a été créée avec succès !';

            return new JsonResponse([
                'success' => true,
                'message' => $message,
                'redirect' => $this->generateUrl('app_home')
            ]);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            error_log("SortieController::createModal - Formulaire soumis mais invalide");
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $errors
            ]);
        }

        // Récupérer les villes pour la sélection
        $villes = $entityManager->getRepository(\App\Entity\Ville::class)->findAll();
        
        error_log("SortieController::createModal - Rendu du template modal_create.html.twig");

        return $this->render('sortie/modal_create.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
            'villes' => $villes,
        ]);
    }

    #[Route('/{id}/modal', name: 'app_sortie_show_modal', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function showModal(Sortie $sortie): Response
    {
        error_log("SortieController::showModal - ID: " . $sortie->getId());
        
        /** @var Participant $user */
        $user = $this->getUser();
        $isOrganisateur = $sortie->getOrganisateur()->getId() === $user->getId();
        
        // Vérifier si l'utilisateur est inscrit
        $isInscrit = false;
        foreach ($sortie->getInscriptions() as $inscription) {
            if ($inscription->getParticipant()->getId() === $user->getId()) {
                $isInscrit = true;
                break;
            }
        }

        error_log("SortieController::showModal - Rendu du template modal_show.html.twig");

        return $this->render('sortie/modal_show.html.twig', [
            'sortie' => $sortie,
            'isOrganisateur' => $isOrganisateur,
            'isInscrit' => $isInscrit,
        ]);
    }

    #[Route('/{id}/modifier/modal', name: 'app_sortie_edit_modal', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function editModal(Sortie $sortie, Request $request, EntityManagerInterface $entityManager): Response
    {
        error_log("SortieController::editModal - ID: " . $sortie->getId() . " - Method: " . $request->getMethod());
        
        /** @var Participant $user */
        $user = $this->getUser();
        
        // Vérifier les permissions - organisateur seulement
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        if ($sortie->getOrganisateur()->getId() !== $user->getId() && !$isAdmin) {
            error_log("SortieController::editModal - Accès refusé pour l'utilisateur " . $user->getId());
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette sortie.');
        }
        
        // Vérifier l'état - interdire la modification si la sortie est ouverte ou annulée
        if (in_array($sortie->getEtat()->getLibelle(), ['Ouverte', 'Annulée'])) {
            error_log("SortieController::editModal - Tentative de modification d'une sortie " . $sortie->getEtat()->getLibelle() . " refusée");
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Une sortie ' . strtolower($sortie->getEtat()->getLibelle()) . ' ne peut plus être modifiée.'
                ]);
            }
            $this->addFlash('error', 'Une sortie ' . strtolower($sortie->getEtat()->getLibelle()) . ' ne peut plus être modifiée.');
            return $this->redirectToRoute('app_home');
        }

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            error_log("SortieController::editModal - Formulaire soumis et valide");
            
            // Récupérer l'action demandée (save ou publish)
            $action = $request->request->get('action', 'save');
            error_log("SortieController::editModal - Action: " . $action);
            
            if ($action === 'publish') {
                // Publier la sortie (changer l'état à "Ouverte")
                $etatOuverte = $entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Ouverte']);
                if ($etatOuverte) {
                    $sortie->setEtat($etatOuverte);
                    error_log("SortieController::editModal - État changé à Ouverte");
                }
            }
            
            $entityManager->flush();
            error_log("SortieController::editModal - Sortie modifiée");

            $message = $action === 'publish' ? 'La sortie a été modifiée et publiée avec succès !' : 'La sortie a été modifiée avec succès !';
            
            return new JsonResponse([
                'success' => true,
                'message' => $message,
                'redirect' => $this->generateUrl('app_home')
            ]);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            error_log("SortieController::editModal - Formulaire soumis mais invalide");
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $errors
            ]);
        }

        // Récupérer les villes pour la sélection
        $villes = $entityManager->getRepository(\App\Entity\Ville::class)->findAll();
        
        error_log("SortieController::editModal - Rendu du template modal_edit.html.twig");

        return $this->render('sortie/modal_edit.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
            'villes' => $villes,
        ]);
    }

    #[Route('/{id}/annuler/modal', name: 'app_sortie_cancel_modal', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function cancelModal(Sortie $sortie): Response
    {
        /** @var Participant $user */
        $user = $this->getUser();
        
        // Vérifier les permissions
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        if ($sortie->getOrganisateur()->getId() !== $user->getId() && !$isAdmin) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à annuler cette sortie.');
        }

        // Créer le formulaire sans données liées à l'entité
        $form = $this->createForm(CancelSortieType::class);

        return $this->render('sortie/modal_cancel.html.twig', [
            'sortie' => $sortie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/inscrire', name: 'app_sortie_inscrire', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function inscrire(Sortie $sortie, EntityManagerInterface $entityManager, MessagerieSortieService $messagerie): Response
    {
        /** @var Participant $user */
        $user = $this->getUser();
        
        // Vérifications
        if ($sortie->getEtat()->getLibelle() !== 'Ouverte') {
            return $this->json(['success' => false, 'message' => 'Cette sortie n\'est pas ouverte aux inscriptions.']);
        }
        
        // Vérifier si les inscriptions sont encore ouvertes (date limite)
        if (!$sortie->isInscriptionOuverte()) {
            return $this->json(['success' => false, 'message' => 'La date limite d\'inscription est dépassée.']);
        }
        
        if (count($sortie->getInscriptions()) >= $sortie->getNbInscriptionsMax()) {
            return $this->json(['success' => false, 'message' => 'Cette sortie est complète.']);
        }
        
        // Vérifier si déjà inscrit
        foreach ($sortie->getInscriptions() as $inscription) {
            if ($inscription->getParticipant()->getId() === $user->getId()) {
                return $this->json(['success' => false, 'message' => 'Vous êtes déjà inscrit à cette sortie.']);
            }
        }
        
        // Créer inscription
        $inscription = new \App\Entity\Inscription();
        $inscription->setParticipant($user);
        $inscription->setSortie($sortie);
        $inscription->setDateInscription(new \DateTime());
        
        $entityManager->persist($inscription);
        $entityManager->flush();

        // Ajouter le participant au groupe de discussion
        $messagerie->ajouterParticipantAuGroupe($sortie, $user);
        
        return $this->json(['success' => true, 'message' => 'Inscription réussie !']);
    }

    #[Route('/{id}/desinscrire', name: 'app_sortie_desinscrire', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function desinscrire(Sortie $sortie, EntityManagerInterface $entityManager, MessagerieSortieService $messagerie): Response
    {
        /** @var Participant $user */
        $user = $this->getUser();
        
        // Trouver l'inscription
        $inscriptionToRemove = null;
        foreach ($sortie->getInscriptions() as $inscription) {
            if ($inscription->getParticipant()->getId() === $user->getId()) {
                $inscriptionToRemove = $inscription;
                break;
            }
        }
        
        if (!$inscriptionToRemove) {
            return $this->json(['success' => false, 'message' => 'Vous n\'êtes pas inscrit à cette sortie.']);
        }
        
        $entityManager->remove($inscriptionToRemove);
        $entityManager->flush();

        // Retirer le participant du groupe de discussion
        $messagerie->retirerParticipantDuGroupe($sortie, $user);
        
        return $this->json(['success' => true, 'message' => 'Désinscription réussie !']);
    }

    #[Route('/{id}/publier', name: 'app_sortie_publier', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function publier(Sortie $sortie, EntityManagerInterface $entityManager, EtatRepository $etatRepository, MessagerieSortieService $messagerie): Response
    {
        /** @var Participant $user */
        $user = $this->getUser();
        
        // Vérifier les permissions
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        if ($sortie->getOrganisateur()->getId() !== $user->getId() && !$isAdmin) {
            return $this->json(['success' => false, 'message' => 'Vous n\'êtes pas autorisé à publier cette sortie.']);
        }
        
        if ($sortie->getEtat()->getLibelle() !== 'Créée') {
            return $this->json(['success' => false, 'message' => 'Cette sortie ne peut pas être publiée.']);
        }
        
        // Changer l'état à "Ouverte"
        $etatOuverte = $etatRepository->findOneBy(['libelle' => 'Ouverte']);
        if ($etatOuverte) {
            $sortie->setEtat($etatOuverte);
            $entityManager->flush();

            // Gérer la publication dans le système de messagerie
            $messagerie->gererPublicationSortie($sortie);
            
            return $this->json(['success' => true, 'message' => 'Sortie publiée avec succès !']);
        }
        
        return $this->json(['success' => false, 'message' => 'Erreur lors de la publication.']);
    }
}