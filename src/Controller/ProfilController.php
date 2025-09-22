<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ProfilType;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
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
        
        return $this->render('profil/show.html.twig', [
            'user' => $user,
            'isOwnProfile' => $isOwnProfile,
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
        
        // Sécurité : on ne peut modifier que son propre profil
        // (pas besoin de vérification supplémentaire car on récupère toujours l'utilisateur connecté)
        
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

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès !');

            return $this->redirectToRoute('app_profil_show');
        }

        return $this->render('profil/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }
}