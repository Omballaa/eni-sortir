<?php

namespace App\Controller;

use App\Repository\LieuRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ApiController extends AbstractController
{
    #[Route('/villes/{id}/lieux', name: 'api_ville_lieux', methods: ['GET'])]
    public function getLieuxByVille(int $id, LieuRepository $lieuRepository): JsonResponse
    {
        $lieux = $lieuRepository->findBy(['ville' => $id], ['nomLieu' => 'ASC']);
        
        $data = [];
        foreach ($lieux as $lieu) {
            $data[] = [
                'id' => $lieu->getId(),
                'nomLieu' => $lieu->getNomLieu(),
                'rue' => $lieu->getRue(),
                'latitude' => $lieu->getLatitude(),
                'longitude' => $lieu->getLongitude()
            ];
        }
        
        return $this->json($data);
    }
}