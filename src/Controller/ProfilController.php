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

    #[Route('/{id}/edit/modal', name: 'app_profil_edit_modal', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function editModal(string $id, Request $request, ParticipantRepository $participantRepository, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        error_log("ProfilController::editModal - ID: " . $id . " - Method: " . $request->getMethod());
        
        /** @var Participant $currentUser */
        $currentUser = $this->getUser();
        
        // Sécurité : on ne peut modifier que son propre profil, sauf si admin
        $isAdmin = in_array('ROLE_ADMIN', $currentUser->getRoles());
        if ((int)$id !== $currentUser->getId() && !$isAdmin) {
            error_log("ProfilController::editModal - Accès refusé pour l'utilisateur " . $currentUser->getId() . " tentant de modifier le profil " . $id);
            throw $this->createAccessDeniedException('Vous ne pouvez modifier que votre propre profil');
        }
        
        $user = $participantRepository->find((int) $id);
        if (!$user) {
            error_log("ProfilController::editModal - Utilisateur non trouvé pour ID: " . $id);
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

    $form = $this->createForm(ProfilType::class, $user, ['is_admin' => $isAdmin]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            error_log("ProfilController::editModal - Formulaire soumis et valide");
            
            // Gestion du mot de passe avec validation manuelle
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                // Validation manuelle de la longueur du mot de passe
                if (strlen($plainPassword) < 6) {
                    if ($request->isXmlHttpRequest()) {
                        return $this->json([
                            'success' => false,
                            'message' => 'Le mot de passe doit contenir au moins 6 caractères'
                        ]);
                    }
                    $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
                    return $this->render('profil/modal_edit.html.twig', [
                        'user' => $user,
                        'form' => $form,
                    ]);
                }
                
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setMotDePasse($hashedPassword);
                error_log("ProfilController::editModal - Mot de passe mis à jour");
            }

            // Gestion de l'upload de photo
            $photoFile = $form->get('photo')->getData();
            if ($photoFile) {
                error_log("ProfilController::editModal - Fichier photo détecté: " . $photoFile->getClientOriginalName());
                error_log("ProfilController::editModal - Taille fichier: " . $photoFile->getSize() . " bytes");
                // getMimeType() supprimé car nécessite fileinfo
                
                try {
                    // Vérifier que le répertoire existe
                    try {
                        $photosDirectory = $this->getParameter('photos_directory');
                        error_log("ProfilController::editModal - Répertoire photos: " . $photosDirectory);
                    } catch (\Exception $e) {
                        error_log("ProfilController::editModal - Erreur récupération paramètre: " . $e->getMessage());
                        // Fallback vers un répertoire par défaut
                        $photosDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/photos';
                        error_log("ProfilController::editModal - Utilisation du répertoire par défaut: " . $photosDirectory);
                    }
                    
                    if (!is_dir($photosDirectory)) {
                        error_log("ProfilController::editModal - Répertoire n'existe pas, création...");
                        if (!mkdir($photosDirectory, 0755, true)) {
                            throw new \Exception("Impossible de créer le répertoire " . $photosDirectory);
                        }
                        error_log("ProfilController::editModal - Répertoire créé: " . $photosDirectory);
                    }
                    
                    // Vérifier les permissions d'écriture
                    if (!is_writable($photosDirectory)) {
                        throw new \Exception("Le répertoire " . $photosDirectory . " n'est pas accessible en écriture");
                    }
                    
                    $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $originalExtension = strtolower(pathinfo($photoFile->getClientOriginalName(), PATHINFO_EXTENSION));
                    error_log("ProfilController::editModal - Nom fichier original: " . $originalFilename);
                    error_log("ProfilController::editModal - Extension originale: " . $originalExtension);
                    
                    // Vérifier que l'extension est autorisée
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (!in_array($originalExtension, $allowedExtensions)) {
                        throw new \Exception("Type de fichier non autorisé. Extensions autorisées : " . implode(', ', $allowedExtensions));
                    }
                    
                    // Alternative sans intl : nettoyage manuel
                    $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '', $originalFilename);
                    if (empty($safeFilename)) {
                        $safeFilename = 'photo';
                    }
                    $safeFilename = strtolower($safeFilename);
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$originalExtension;
                    error_log("ProfilController::editModal - Nouveau nom fichier: " . $newFilename);
                    
                    $photoFile->move($photosDirectory, $newFilename);
                    error_log("ProfilController::editModal - Fichier déplacé avec succès");
                    
                    // Supprimer l'ancienne photo si elle existe
                    if ($user->getPhoto()) {
                        $oldPhotoPath = $photosDirectory . '/' . $user->getPhoto();
                        if (file_exists($oldPhotoPath) && is_file($oldPhotoPath)) {
                            unlink($oldPhotoPath);
                            error_log("ProfilController::editModal - Ancienne photo supprimée: " . $oldPhotoPath);
                        }
                    }
                    
                    $user->setPhoto($newFilename);
                    error_log("ProfilController::editModal - Photo assignée à l'utilisateur: " . $newFilename);
                    
                } catch (\Exception $e) {
                    error_log("ProfilController::editModal - Erreur upload photo: " . $e->getMessage());
                    error_log("ProfilController::editModal - Trace: " . $e->getTraceAsString());
                    
                    // Si c'est une requête AJAX, retourner JSON
                    if ($request->isXmlHttpRequest()) {
                        return $this->json([
                            'success' => false,
                            'message' => 'Erreur lors de l\'upload de la photo : ' . $e->getMessage()
                        ]);
                    }
                    
                    // Sinon, ajouter un flash message et continuer
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo.');
                }
            }

            try {
                $entityManager->flush();
                error_log("ProfilController::editModal - Données sauvegardées avec succès");
                
                // Si c'est une requête AJAX, retourner JSON
                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'success' => true,
                        'message' => 'Profil mis à jour avec succès!'
                    ]);
                }
                
                // Sinon, rediriger avec un flash message
                $this->addFlash('success', 'Profil mis à jour avec succès!');
                return $this->redirectToRoute('app_profil_show');
                
            } catch (\Exception $e) {
                error_log("ProfilController::editModal - Erreur sauvegarde: " . $e->getMessage());
                
                // Si c'est une requête AJAX, retourner JSON
                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Erreur lors de la sauvegarde : ' . $e->getMessage()
                    ]);
                }
                
                // Sinon, ajouter un flash message et continuer
                $this->addFlash('error', 'Erreur lors de la sauvegarde.');
            }
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            error_log("ProfilController::editModal - Formulaire soumis mais invalide");
            
            // Debug des erreurs de validation
            $allErrors = [];
            foreach ($form->getErrors(true) as $error) {
                $allErrors[] = $error->getMessage();
                error_log("ProfilController::editModal - Erreur: " . $error->getMessage());
            }
            
            // Si c'est une requête AJAX, retourner JSON
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $allErrors
                ]);
            }
        }

        error_log("ProfilController::editModal - Rendu du template modal_edit.html.twig");

        return $this->render('profil/modal_edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }
}