<?php

namespace App\Controller;

use App\Entity\Inscription;
use App\Entity\Sortie;
use App\Repository\EtatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/inscription')]
class InscriptionController extends AbstractController
{
    #[Route('/sortie/{id}/inscrire', name: 'app_inscription_create', methods: ['POST'])]
    public function inscrire(
        Sortie $sortie,
        EntityManagerInterface $entityManager,
        EtatRepository $etatRepository
    ): Response {
        $user = $this->getUser();
        
        // Vérifications de sécurité et de logique métier
        if ($sortie->getEtat()->getLibelle() !== 'Ouverte') {
            $this->addFlash('error', 'Cette sortie n\'est plus ouverte aux inscriptions.');
            return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
        }
        
        // Vérifier si déjà inscrit
        foreach ($sortie->getInscriptions() as $inscription) {
            if ($inscription->getParticipant() === $user) {
                $this->addFlash('warning', 'Vous êtes déjà inscrit à cette sortie.');
                return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
            }
        }
        
        // Vérifier les places disponibles
        if ($sortie->getInscriptions()->count() >= $sortie->getNbInscriptionsMax()) {
            $this->addFlash('error', 'Cette sortie est complète.');
            return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
        }
        
        // Vérifier la date limite d'inscription
        $now = new \DateTime();
        if ($sortie->getDateLimiteInscription() < $now) {
            $this->addFlash('error', 'La date limite d\'inscription est dépassée.');
            return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
        }
        
        // Créer l'inscription
        $inscription = new Inscription();
        $inscription->setParticipant($user);
        $inscription->setSortie($sortie);
        $inscription->setDateInscription(new \DateTime());
        
        $entityManager->persist($inscription);
        
        // Vérifier si la sortie devient complète et la clôturer automatiquement
        if ($sortie->getInscriptions()->count() + 1 >= $sortie->getNbInscriptionsMax()) {
            $etatCloturee = $etatRepository->findOneBy(['libelle' => 'Clôturée']);
            if ($etatCloturee) {
                $sortie->setEtat($etatCloturee);
            }
        }
        
        $entityManager->flush();
        
        $this->addFlash('success', 'Vous êtes maintenant inscrit à cette sortie !');
        return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
    }
    
    #[Route('/sortie/{id}/desister', name: 'app_inscription_delete', methods: ['POST'])]
    public function desister(
        Sortie $sortie,
        EntityManagerInterface $entityManager,
        EtatRepository $etatRepository
    ): Response {
        $user = $this->getUser();
        
        // Vérifications de sécurité
        if ($sortie->getEtat()->getLibelle() !== 'Ouverte') {
            $this->addFlash('error', 'Vous ne pouvez plus vous désister de cette sortie.');
            return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
        }
        
        // Trouver l'inscription à supprimer
        $inscriptionASupprimer = null;
        foreach ($sortie->getInscriptions() as $inscription) {
            if ($inscription->getParticipant() === $user) {
                $inscriptionASupprimer = $inscription;
                break;
            }
        }
        
        if (!$inscriptionASupprimer) {
            $this->addFlash('error', 'Vous n\'êtes pas inscrit à cette sortie.');
            return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
        }
        
        // Vérifier qu'on peut encore se désister (24h avant la sortie)
        $dateLimiteDesistement = new \DateTime($sortie->getDateHeureDebut()->format('Y-m-d H:i:s'));
        $dateLimiteDesistement->modify('-1 day');
        $now = new \DateTime();
        
        if ($now > $dateLimiteDesistement) {
            $this->addFlash('error', 'Il est trop tard pour se désister de cette sortie (moins de 24h avant le début).');
            return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
        }
        
        // Supprimer l'inscription
        $entityManager->remove($inscriptionASupprimer);
        
        // Si la sortie était clôturée et qu'il y a maintenant des places, la rouvrir
        if ($sortie->getEtat()->getLibelle() === 'Clôturée' && 
            $sortie->getInscriptions()->count() - 1 < $sortie->getNbInscriptionsMax()) {
            $etatOuverte = $etatRepository->findOneBy(['libelle' => 'Ouverte']);
            if ($etatOuverte) {
                $sortie->setEtat($etatOuverte);
            }
        }
        
        $entityManager->flush();
        
        $this->addFlash('success', 'Vous vous êtes désisté de cette sortie.');
        return $this->redirectToRoute('app_sortie_show', ['id' => $sortie->getId()]);
    }
}