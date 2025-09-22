<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Sortie;
use App\Entity\Participant;
use App\Form\SortieType;
use App\Form\CancelSortieType;
use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

        // Vérifier que la sortie peut encore être modifiée (état "Créée" ou "Ouverte")
        $etatsModifiables = ['Créée', 'Ouverte'];
        if (!in_array($sortie->getEtat()->getLibelle(), $etatsModifiables)) {
            $this->addFlash('error', 'Cette sortie ne peut plus être modifiée.');
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

    #[Route('/{id}/publier', name: 'app_sortie_publish', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function publish(Sortie $sortie, EntityManagerInterface $entityManager, EtatRepository $etatRepository): Response
    {
        /** @var Participant $user */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est l'organisateur
        if ($sortie->getOrganisateur()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez publier que vos propres sorties.');
        }

        // Vérifier que la sortie est en état "Créée"
        if ($sortie->getEtat()->getLibelle() !== 'Créée') {
            $this->addFlash('error', 'Cette sortie ne peut pas être publiée.');
            return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
        }

        // Changer l'état vers "Ouverte"
        $etatOuverte = $etatRepository->findOneBy(['libelle' => 'Ouverte']);
        if ($etatOuverte) {
            $sortie->setEtat($etatOuverte);
            $entityManager->flush();
            
            $this->addFlash('success', 'La sortie a été publiée et est maintenant ouverte aux inscriptions !');
        } else {
            $this->addFlash('error', 'Erreur lors de la publication de la sortie.');
        }

        return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
    }

    #[Route('/{id}/annuler', name: 'app_sortie_cancel', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function cancel(Request $request, Sortie $sortie, EntityManagerInterface $entityManager, EtatRepository $etatRepository): Response
    {
        /** @var Participant $user */
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est l'organisateur
        if ($sortie->getOrganisateur()->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous ne pouvez annuler que vos propres sorties.');
        }

        // Vérifier que la sortie peut être annulée
        $etatsAnnulables = ['Créée', 'Ouverte', 'Clôturée'];
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
                
                $this->addFlash('success', 'La sortie a été annulée.');
                return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
            } else {
                $this->addFlash('error', 'Erreur lors de l\'annulation de la sortie.');
            }
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
}