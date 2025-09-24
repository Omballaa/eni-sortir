<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\RegistrationFormType;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        ParticipantRepository $participantRepository
    ): Response {
        // Rediriger si l'utilisateur est déjà connecté
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $participant = new Participant();
        $form = $this->createForm(RegistrationFormType::class, $participant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier l'unicité du pseudo
            $existingUser = $participantRepository->findOneBy(['pseudo' => $participant->getPseudo()]);
            if ($existingUser) {
                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Ce nom d\'utilisateur est déjà pris. Veuillez en choisir un autre.',
                        'field' => 'pseudo'
                    ]);
                }
                $this->addFlash('error', 'Ce nom d\'utilisateur est déjà utilisé. Veuillez en choisir un autre.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }

            // Vérifier l'unicité de l'email
            $existingEmail = $participantRepository->findOneBy(['mail' => $participant->getMail()]);
            if ($existingEmail) {
                if ($request->isXmlHttpRequest()) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Cette adresse email est déjà utilisée. Veuillez en choisir une autre.',
                        'field' => 'mail'
                    ]);
                }
                $this->addFlash('error', 'Cette adresse email est déjà utilisée. Veuillez en choisir une autre.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }

            // Encoder le mot de passe
            $hashedPassword = $userPasswordHasher->hashPassword(
                $participant,
                $form->get('plainPassword')->getData()
            );
            $participant->setMotDePasse($hashedPassword);

            // Définir les valeurs par défaut
            $participant->setAdministrateur(false);
            $participant->setActif(true);

            // Sauvegarder l'utilisateur
            $entityManager->persist($participant);
            $entityManager->flush();

            // Réponse pour AJAX
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'message' => 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.',
                    'redirect' => $this->generateUrl('app_login')
                ]);
            }

            // Message de succès pour formulaire classique
            $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');

            // Rediriger vers la page de connexion
            return $this->redirectToRoute('app_login');
        }

        // Gestion des erreurs de validation pour AJAX
        if ($form->isSubmitted() && !$form->isValid() && $request->isXmlHttpRequest()) {
            $fieldErrors = [];
            $allErrors = [];

            // Fonction récursive pour collecter les erreurs sans doublons
            $this->collectFormErrors($form, $fieldErrors, '');

            // Construire un message global plus informatif
            $message = 'Veuillez corriger les erreurs suivantes :';

            // Construire la liste des erreurs avec labels
            foreach ($fieldErrors as $fieldPath => $errors) {
                $fieldLabel = $this->getFieldLabel($fieldPath);
                // Supprimer les doublons au niveau des messages
                $uniqueErrors = array_unique($errors);
                foreach ($uniqueErrors as $error) {
                    $allErrors[] = $fieldLabel . ' : ' . $error;
                }
            }

            return $this->json([
                'success' => false,
                'message' => $message,
                'errors' => $allErrors,
                'fieldErrors' => $fieldErrors
            ]);
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    /**
     * Retourne le label français pour un nom de champ
     */
    private function getFieldLabel(string $fieldName): string
    {
        $labels = [
            'prenom' => 'Prénom',
            'nom' => 'Nom',
            'pseudo' => 'Nom d\'utilisateur',
            'mail' => 'Email',
            'telephone' => 'Téléphone',
            'site' => 'Site',
            'plainPassword' => 'Mot de passe',
            'plainPassword.first' => 'Mot de passe',
            'plainPassword.second' => 'Confirmation du mot de passe'
        ];

        return $labels[$fieldName] ?? ucfirst($fieldName);
    }

    /**
     * Collecte récursivement les erreurs de formulaire sans doublons
     */
    private function collectFormErrors($form, &$fieldErrors, $prefix = '')
    {
        // Erreurs sur le formulaire lui-même
        foreach ($form->getErrors() as $error) {
            $key = $prefix ?: $form->getName();
            if (!isset($fieldErrors[$key])) {
                $fieldErrors[$key] = [];
            }
            $fieldErrors[$key][] = $error->getMessage();
        }

        // Erreurs sur les champs enfants
        foreach ($form->all() as $childName => $child) {
            $childKey = $prefix ? $prefix . '.' . $childName : $childName;
            
            if ($child->count() > 0) {
                // Le champ a des sous-champs, traiter récursivement
                $this->collectFormErrors($child, $fieldErrors, $childKey);
            } else {
                // Champ simple, collecter ses erreurs
                foreach ($child->getErrors() as $error) {
                    if (!isset($fieldErrors[$childKey])) {
                        $fieldErrors[$childKey] = [];
                    }
                    $fieldErrors[$childKey][] = $error->getMessage();
                }
            }
        }
    }

    #[Route('/check-username', name: 'app_check_username', methods: ['POST'])]
    #[IsGranted('PUBLIC_ACCESS')]
    public function checkUsername(
        Request $request,
        ParticipantRepository $participantRepository
    ): Response {
        $username = $request->request->get('username');
        
        if (!$username) {
            return $this->json(['available' => false, 'message' => 'Nom d\'utilisateur requis']);
        }

        $existingUser = $participantRepository->findOneBy(['pseudo' => $username]);
        
        return $this->json([
            'available' => $existingUser === null,
            'message' => $existingUser ? 'Ce nom d\'utilisateur est déjà pris' : 'Ce nom d\'utilisateur est disponible'
        ]);
    }

    #[Route('/check-email', name: 'app_check_email', methods: ['POST'])]
    #[IsGranted('PUBLIC_ACCESS')]
    public function checkEmail(
        Request $request,
        ParticipantRepository $participantRepository
    ): Response {
        $email = $request->request->get('email');
        
        if (!$email) {
            return $this->json(['available' => false, 'message' => 'Email requis']);
        }

        $existingUser = $participantRepository->findOneBy(['mail' => $email]);
        
        return $this->json([
            'available' => $existingUser === null,
            'message' => $existingUser ? 'Cette adresse email est déjà utilisée' : 'Cette adresse email est disponible'
        ]);
    }
}