<?php

namespace App\Controller;

use App\Entity\Lieu;
use App\Form\LieuType;
use App\Repository\LieuRepository;
use App\Repository\VilleRepository;
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
    public function getFormModal(VilleRepository $villeRepository): Response
    {
        $lieu = new Lieu();
        $form = $this->createForm(LieuType::class, $lieu);
        $villes = $villeRepository->findAll();

        return $this->render('lieu/_form_modal.html.twig', [
            'form' => $form,
            'villes' => $villes
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
}