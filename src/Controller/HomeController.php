<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/create-file', name: 'app_create_file')]
    public function createCompanyFile(Request $request): Response
    {

        $data = json_decode($request->getContent(), true);
        $company = $data['company'];
        file_put_contents("./companies/company_" . $company["nom_raison_sociale"] . ".txt", $company["nom_raison_sociale"]);
        return $this->json(["response" => $company]);
    }
}
