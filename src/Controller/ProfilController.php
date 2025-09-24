<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ProfilType;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profil')]
#[IsGranted('ROLE_USER')]
class ProfilController extends AbstractController
{
    #[Route('/{id?}', name: 'app_profil_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(?string $id, ParticipantRepository $participantRepository): Response
    {
        // Si aucun ID fourni, afficher le profil de l'utilisateur connecté
        if ($id === null) {
            /** @var Participant $user */
            $user = $this->getUser();
        } else {
            // Sinon, récupérer l'utilisateur demandé
            $user = $participantRepository->find((int) $id);
            if (!$user) {
                throw $this->createNotFoundException('Utilisateur non trouvé');
            }
        }
        
        /** @var Participant $currentUser */
        $currentUser = $this->getUser();
        $isOwnProfile = $currentUser->getId() === $user->getId();
        
        // Récupérer les sorties organisées et participations pour les statistiques
        $sortiesOrganisees = $user->getSortiesOrganisees();
        $participations = $user->getInscriptions();
        
        return $this->render('profil/show.html.twig', [
            'user' => $user,
            'isOwnProfile' => $isOwnProfile,
            'sortiesOrganisees' => $sortiesOrganisees,
            'participations' => $participations,
        ]);
    }

    #[Route('/{id}/modal', name: 'app_profil_show_modal', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function showModal(string $id, ParticipantRepository $participantRepository): Response
    {
        error_log("ProfilController::showModal - ID: " . $id);
        
        $user = $participantRepository->find((int) $id);
        if (!$user) {
            error_log("ProfilController::showModal - Utilisateur non trouvé pour ID: " . $id);
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }
        
        /** @var Participant $currentUser */
        $currentUser = $this->getUser();
        $isOwnProfile = $currentUser->getId() === $user->getId();
        
        // Récupérer les sorties organisées et participations pour les statistiques
        $sortiesOrganisees = $user->getSortiesOrganisees();
        $participations = $user->getInscriptions();
        
        error_log("ProfilController::showModal - Rendu du template modal_show.html.twig");
        
        return $this->render('profil/modal_show.html.twig', [
            'user' => $user,
            'isOwnProfile' => $isOwnProfile,
            'sortiesOrganisees' => $sortiesOrganisees,
            'participations' => $participations,
        ]);
    }

    #[Route('/modifier', name: 'app_profil_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        /** @var Participant $user */
        $user = $this->getUser();
        
        $form = $this->createForm(ProfilType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion du mot de passe
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setMotDePasse($hashedPassword);
            }

            // Gestion de l'upload de photo
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('photos_directory'),
                        $newFilename
                    );
                    $user->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo.');
                }
            }

            $entityManager->flush();

            // Réponse AJAX ou redirection
            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return $this->json([
                    'success' => true,
                    'message' => 'Profil mis à jour avec succès !'
                ]);
            }

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès !');
            return $this->redirectToRoute('app_profil_show');
        }

        // Erreurs de validation pour AJAX
        if ($form->isSubmitted() && !$form->isValid() && $request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            return $this->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $errors
            ]);
        }

        return $this->render('profil/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit/modal', name: 'app_profil_edit_modal', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function editModal(string $id, Request $request, ParticipantRepository $participantRepository, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        error_log("ProfilController::editModal - ID: " . $id . " - Method: " . $request->getMethod());
        
        /** @var Participant $currentUser */
        $currentUser = $this->getUser();
        
        // Sécurité : on ne peut modifier que son propre profil
        if ((int)$id !== $currentUser->getId()) {
            error_log("ProfilController::editModal - Accès refusé pour l'utilisateur " . $currentUser->getId() . " tentant de modifier le profil " . $id);
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que votre propre profil');
        }
        
        $user = $participantRepository->find((int) $id);
        if (!$user) {
            error_log("ProfilController::editModal - Utilisateur non trouvé pour ID: " . $id);
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        $form = $this->createForm(ProfilType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            error_log("ProfilController::editModal - Formulaire soumis et valide");
            
            // Gestion du mot de passe
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setMotDePasse($hashedPassword);
                error_log("ProfilController::editModal - Mot de passe mis à jour");
            }

            // Gestion de l'upload de photo
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()', $originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('photos_directory'),
                        $newFilename
                    );
                    $user->setPhoto($newFilename);
                    error_log("ProfilController::editModal - Photo uploadée: " . $newFilename);
                } catch (FileException $e) {
                    error_log("ProfilController::editModal - Erreur upload photo: " . $e->getMessage());
                }
            }

            $entityManager->flush();
            error_log("ProfilController::editModal - Données sauvegardées");
            
            return $this->json([
                'success' => true,
                'message' => 'Profil mis à jour avec succès!'
            ]);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            error_log("ProfilController::editModal - Formulaire soumis mais invalide");
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            return $this->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $errors
            ]);
        }

        error_log("ProfilController::editModal - Rendu du template modal_edit.html.twig");

        return $this->render('profil/modal_edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }
}