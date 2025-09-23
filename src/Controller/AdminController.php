<?php

namespace App\Controller;

use App\Entity\Site;
use App\Entity\Ville;
use App\Form\SiteType;
use App\Form\VilleType;
use App\Repository\SiteRepository;
use App\Repository\VilleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    public function dashboard(
        VilleRepository $villeRepository,
        SiteRepository $siteRepository
    ): Response {
        $stats = [
            'total_villes' => $villeRepository->count([]),
            'total_sites' => $siteRepository->count([]),
        ];

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
        ]);
    }

    // === GESTION DES VILLES ===

    #[Route('/villes/data', name: 'app_admin_villes_data', methods: ['GET'])]
    public function getVillesData(VilleRepository $villeRepository, Request $request): JsonResponse
    {
        $search = $request->query->get('search', '');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        
        $villes = $villeRepository->findBySearchPaginated($search, $page, $limit);
        $total = $villeRepository->countBySearch($search);
        
        $villesData = [];
        foreach ($villes as $ville) {
            $villesData[] = [
                'id' => $ville->getId(),
                'nomVille' => $ville->getNomVille(),
                'codePostal' => $ville->getCodePostal(),
                'nbLieux' => $ville->getLieux()->count(),
            ];
        }

        return new JsonResponse([
            'villes' => $villesData,
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $limit)
        ]);
    }

    #[Route('/ville/form/{id?}', name: 'app_admin_ville_form', methods: ['GET'])]
    public function getVilleForm(?Ville $ville = null): Response
    {
        if (!$ville) {
            $ville = new Ville();
        }

        $form = $this->createForm(VilleType::class, $ville);

        return $this->render('admin/modals/ville_form.html.twig', [
            'form' => $form->createView(),
            'ville' => $ville,
            'isEdit' => $ville->getId() !== null,
        ]);
    }

    #[Route('/ville/save/{id?}', name: 'app_admin_ville_save', methods: ['POST'])]
    public function saveVille(
        Request $request,
        EntityManagerInterface $em,
        ?Ville $ville = null
    ): JsonResponse {
        $isEdit = $ville !== null;
        
        if (!$ville) {
            $ville = new Ville();
        }

        $form = $this->createForm(VilleType::class, $ville);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($ville);
            $em->flush();

            return new JsonResponse([
                'success' => true,
                'message' => $isEdit ? 'Ville modifiée avec succès!' : 'Ville créée avec succès!',
                'ville' => [
                    'id' => $ville->getId(),
                    'nomVille' => $ville->getNomVille(),
                    'codePostal' => $ville->getCodePostal(),
                    'nbLieux' => $ville->getLieux()->count(),
                ]
            ]);
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return new JsonResponse([
            'success' => false,
            'errors' => $errors
        ], 400);
    }

    #[Route('/ville/delete/{id}', name: 'app_admin_ville_delete', methods: ['DELETE'])]
    public function deleteVille(Ville $ville, EntityManagerInterface $em): JsonResponse
    {
        try {
            // Vérifier s'il y a des lieux associés
            if ($ville->getLieux()->count() > 0) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Impossible de supprimer cette ville car elle contient des lieux.'
                ], 400);
            }

            $em->remove($ville);
            $em->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Ville supprimée avec succès!'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la ville.'
            ], 500);
        }
    }

    // === GESTION DES SITES ===

    #[Route('/sites/data', name: 'app_admin_sites_data', methods: ['GET'])]
    public function getSitesData(SiteRepository $siteRepository, Request $request): JsonResponse
    {
        $search = $request->query->get('search', '');
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        
        $sites = $siteRepository->findBySearchPaginated($search, $page, $limit);
        $total = $siteRepository->countBySearch($search);
        
        $sitesData = [];
        foreach ($sites as $site) {
            $sitesData[] = [
                'id' => $site->getId(),
                'nomSite' => $site->getNomSite(),
                'nbParticipants' => $site->getParticipants()->count(),
            ];
        }

        return new JsonResponse([
            'sites' => $sitesData,
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $limit)
        ]);
    }

    #[Route('/site/form/{id?}', name: 'app_admin_site_form', methods: ['GET'])]
    public function getSiteForm(?Site $site = null): Response
    {
        if (!$site) {
            $site = new Site();
        }

        $form = $this->createForm(SiteType::class, $site);

        return $this->render('admin/modals/site_form.html.twig', [
            'form' => $form->createView(),
            'site' => $site,
            'isEdit' => $site->getId() !== null,
        ]);
    }

    #[Route('/site/save/{id?}', name: 'app_admin_site_save', methods: ['POST'])]
    public function saveSite(
        Request $request,
        EntityManagerInterface $em,
        ?Site $site = null
    ): JsonResponse {
        $isEdit = $site !== null;
        
        if (!$site) {
            $site = new Site();
        }

        $form = $this->createForm(SiteType::class, $site);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($site);
            $em->flush();

            return new JsonResponse([
                'success' => true,
                'message' => $isEdit ? 'Site modifié avec succès!' : 'Site créé avec succès!',
                'site' => [
                    'id' => $site->getId(),
                    'nomSite' => $site->getNomSite(),
                    'nbParticipants' => $site->getParticipants()->count(),
                ]
            ]);
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return new JsonResponse([
            'success' => false,
            'errors' => $errors
        ], 400);
    }

    #[Route('/site/delete/{id}', name: 'app_admin_site_delete', methods: ['DELETE'])]
    public function deleteSite(Site $site, EntityManagerInterface $em): JsonResponse
    {
        try {
            // Vérifier s'il y a des participants associés
            if ($site->getParticipants()->count() > 0) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Impossible de supprimer ce site car il contient des participants.'
                ], 400);
            }

            $em->remove($site);
            $em->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Site supprimé avec succès!'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la suppression du site.'
            ], 500);
        }
    }
}