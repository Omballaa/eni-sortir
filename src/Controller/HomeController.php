<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // Si l'utilisateur est connecté, afficher le tableau de bord
        if ($this->getUser()) {
            return $this->render('home/dashboard.html.twig', [
                'user' => $this->getUser(),
            ]);
        }
        
        // Sinon, afficher la page d'accueil publique
        return $this->render('home/welcome.html.twig');
    }
}