<?php

namespace App\Controller;

use App\Entity\Site;
use App\Repository\SortieRepository;
use App\Repository\SiteRepository;
use App\Repository\ParticipantRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        Request $request,
        SiteRepository $siteRepository,
        ParticipantRepository $participantRepository
    ): Response {
        // Si l'utilisateur n'est pas connecté, afficher la page d'accueil publique
        if (!$this->getUser()) {
            return $this->render('home/welcome.html.twig');
        }

        /** @var \App\Entity\Participant $user */
        $user = $this->getUser();

        // Récupération des valeurs des filtres pour les conserver dans le formulaire
        $site = $request->query->get('site');
        $nom = $request->query->get('nom');
        $dateDebut = $request->query->get('dateDebut');
        $dateFin = $request->query->get('dateFin');
        $organisateurSeulement = $request->query->get('organisateurSeulement');
        $inscriptionFilter = $request->query->get('inscriptionFilter');
        $sortiesPassees = $request->query->get('sortiesPassees');

        // Récupération des données pour les formulaires de filtres
        $sites = $siteRepository->findAllOrderedByName();
        $organisateurs = $participantRepository->findAll();

        return $this->render('home/dashboard.html.twig', [
            'user' => $user,
            'sorties' => [], // Pas de sorties au chargement initial
            'sites' => $sites,
            'organisateurs' => $organisateurs,
            'filtres' => [
                'site' => $site,
                'nom' => $nom,
                'dateDebut' => $dateDebut,
                'dateFin' => $dateFin,
                'organisateurSeulement' => $organisateurSeulement,
                'inscriptionFilter' => $inscriptionFilter,
                'sortiesPassees' => $sortiesPassees,
            ]
        ]);
    }

    #[Route('/dashboard/refresh', name: 'app_dashboard_refresh', methods: ['GET'])]
    public function refreshSorties(
        Request $request,
        SortieRepository $sortieRepository,
        SiteRepository $siteRepository,
        ParticipantRepository $participantRepository
    ): Response {
        // Récupération des filtres depuis la requête
        $criteria = [];
        $site = $request->query->get('site');
        $nom = $request->query->get('nom');
        $dateDebut = $request->query->get('dateDebut');
        $dateFin = $request->query->get('dateFin');
        $organisateurSeulement = $request->query->get('organisateurSeulement');
        //$inscritSeulement = $request->query->get('inscritSeulement');
        //$nonInscritSeulement = $request->query->get('nonInscritSeulement');
        $inscriptionFilter = $request->query->get('inscriptionFilter');
        $sortiesPassees = $request->query->get('sortiesPassees');


        // Construction des critères de recherche
        if ($site) {
            $criteria['site'] = $site;
        }
        if ($nom) {
            $criteria['nom'] = $nom;
        }
        if ($dateDebut) {
            $criteria['dateDebut'] = new \DateTime($dateDebut);
        }
        if ($dateFin) {
            $criteria['dateFin'] = new \DateTime($dateFin . ' 23:59:59');
        }

        // Récupération des sorties selon les filtres
        $sorties = $sortieRepository->search($criteria);
        /** @var \App\Entity\Participant $user */
        $user = $this->getUser();

        // Filtrage supplémentaire selon les options utilisateur
        if ($organisateurSeulement) {
            $sorties = array_filter($sorties, fn($sortie) => $sortie->getOrganisateur()->getId() === $user->getId());
        }

        if ($inscriptionFilter === 'inscritSeulement') {
            $sorties = array_filter($sorties, function($sortie) use ($user) {
                foreach ($sortie->getInscriptions() as $inscription) {
                    if ($inscription->getParticipant()->getId() === $user->getId()) {
                        return true;
                    }
                }
                return false;
            });
        }

        if ($inscriptionFilter === 'nonInscritSeulement') {
            $sorties = array_filter($sorties, function($sortie) use ($user) {
                foreach ($sortie->getInscriptions() as $inscription) {
                    if ($inscription->getParticipant()->getId() === $user->getId()) {
                        return false;
                    }
                }
                return true;
            });
        }

        if (!$sortiesPassees) {
            $sorties = array_filter($sorties, function($sortie) {
                return $sortie->getEtat()->getLibelle() !== 'Clôturée' && $sortie->getEtat()->getLibelle() !== 'Annulée';
            });
        } else {
            $sorties = array_filter($sorties, function($sortie){
                return  $sortie->getEtat()->getLibelle() == 'Clôtûrée' || $sortie->getEtat()->getLibelle() == 'Annulée';
            });
        }

        // Retourner seulement le template de la liste des sorties
        return $this->render('home/_sorties_list.html.twig', [
            'user' => $user,
            'sorties' => $sorties
        ]);
    }
}