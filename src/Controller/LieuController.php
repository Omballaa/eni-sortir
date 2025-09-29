<?php

namespace App\Controller;

use App\Entity\Lieu;
use App\Form\LieuType;
use App\Repository\LieuRepository;
use App\Repository\VilleRepository;
use App\Service\GeocodingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/lieu')]
#[IsGranted('ROLE_USER')]
class LieuController extends AbstractController
{
    #[Route('/create-ajax', name: 'app_lieu_create_ajax', methods: ['POST'])]
    public function createAjax(Request $request, EntityManagerInterface $entityManager, VilleRepository $villeRepository): JsonResponse
    {
        $lieu = new Lieu();
        $form = $this->createForm(LieuType::class, $lieu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($lieu);
            $entityManager->flush();

            // Retourner les données du nouveau lieu créé
            return new JsonResponse([
                'success' => true,
                'lieu' => [
                    'id' => $lieu->getId(),
                    'nom' => $lieu->getNomLieu(),
                    'rue' => $lieu->getRue(),
                    'latitude' => $lieu->getLatitude(),
                    'longitude' => $lieu->getLongitude(),
                    'ville' => [
                        'id' => $lieu->getVille()->getId(),
                        'nom' => $lieu->getVille()->getNomVille(),
                        'codePostal' => $lieu->getVille()->getCodePostal()
                    ]
                ],
                'message' => 'Lieu créé avec succès !'
            ]);
        }

        // Récupérer les erreurs du formulaire
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return new JsonResponse([
            'success' => false,
            'errors' => $errors,
            'message' => 'Erreur lors de la création du lieu'
        ], 400);
    }

    #[Route('/form-modal', name: 'app_lieu_form_modal', methods: ['GET'])]
    public function getFormModal(Request $request, VilleRepository $villeRepository): Response
    {
        $lieu = new Lieu();
        
        // Pré-sélectionner la ville si fournie en paramètre
        $villeId = $request->query->get('ville_id');
        if ($villeId) {
            $ville = $villeRepository->find($villeId);
            if ($ville) {
                $lieu->setVille($ville);
            }
        }
        
        $form = $this->createForm(LieuType::class, $lieu);
        $villes = $villeRepository->findAll();

        return $this->render('lieu/modal_create.html.twig', [
            'form' => $form,
            'villes' => $villes,
            'selectedVilleId' => $villeId
        ]);
    }

    #[Route('/by-ville', name: 'app_lieu_by_ville', methods: ['GET'])]
    public function getLieuxByVille(Request $request, LieuRepository $lieuRepository): JsonResponse
    {
        $villeId = $request->query->get('ville');
        
        if (!$villeId) {
            return $this->json([]);
        }
        
        $lieux = $lieuRepository->findBy(['ville' => $villeId]);
        
        $lieuxData = [];
        foreach ($lieux as $lieu) {
            $lieuxData[] = [
                'id' => $lieu->getId(),
                'nomLieu' => $lieu->getNomLieu(),
                'rue' => $lieu->getRue(),
            ];
        }
        
        return $this->json($lieuxData);
    }

    #[Route('/geocode', name: 'app_lieu_geocode', methods: ['POST'])]
    public function geocode(Request $request, GeocodingService $geocodingService, VilleRepository $villeRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $rue = $data['rue'] ?? '';
        $villeId = $data['ville_id'] ?? null;
        
        if (empty($rue) || !$villeId) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Adresse et ville requises'
            ], 400);
        }
        
        // Récupérer les informations de la ville
        $ville = $villeRepository->find($villeId);
        if (!$ville) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ville non trouvée'
            ], 400);
        }
        
        // Géocoder l'adresse
        $coordinates = $geocodingService->geocodeSimpleAddress(
            $rue,
            $ville->getNomVille(),
            $ville->getCodePostal()
        );
        
        if ($coordinates) {
            return new JsonResponse([
                'success' => true,
                'latitude' => $coordinates['latitude'],
                'longitude' => $coordinates['longitude'],
                'confidence' => $coordinates['confidence'],
                'display_name' => $coordinates['display_name'],
                'message' => 'Coordonnées trouvées avec succès'
            ]);
        } else {
            return new JsonResponse([
                'success' => false,
                'message' => 'Impossible de trouver les coordonnées pour cette adresse'
            ], 404);
        }
    }
}