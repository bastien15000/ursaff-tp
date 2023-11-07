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

    #[Route('/detail-company', name: 'app_detail_company')]
    public function detailCompany(Request $request): Response
    {
        return $this->render('home/company.html.twig');
    }

    #[Route('/create-file', name: 'app_create_file')]
    public function createCompanyFile(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $company = $data['company'];
        $siege = $company["siege"];
        $file_content = "Raison sociale : " . $company["nom_raison_sociale"] . "\nSIREN : " . $company["siren"] . "\nSIRET : " . $siege["siret"] . "\nAdresse : " . $siege["adresse"];
        file_put_contents("./companies/" . $company["siren"] . ".txt", $file_content);
        return $this->json(["response" => $company]);
    }
}
