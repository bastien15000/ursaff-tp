<?php

namespace App\Controller;

use App\Service\ApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    #[Route('/api-ouverte-ent-liste', name: 'api_ouverte_ent_liste', methods: ['GET'])]
    public function index(Request $request, ApiService $apiService): Response
    {
        $fileContents = $apiService->scanFilesCompanies();
        if (count($fileContents) == 0) {
            return $this->json(["message" => "Aucune entreprise enregistrée"], 200);
        } else {
            $content_type = $request->headers->get("Content-Type");
            if ($content_type == "application/json") {
                return $this->json($fileContents, 200);
            } elseif ($content_type == "text/csv") {
                $csv = $apiService->convertToCSV($fileContents);
                return new Response($csv, 200, [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="file.csv"',
                ]);
            } else {
                return $this->json(["message" => "Format non pris en compte"], 406);
            }
        }
    }

    #[Route('/api-ouverte-ent/{siren}', name: 'api_ouverte_ent_get_one', methods: ['GET'])]
    public function getOne(Request $request, ApiService $apiService): Response
    {
        $siren = $request->get('siren');
        $file = $apiService->getFileIfExists($siren);
        if ($file) {
            return $this->json($file, 200);
        } else {
            return $this->json(["message" => "Aucune entreprise avec ce SIREN"], 404);
        }
    }

    #[Route('/api-ouverte-ent', name: 'api_ouverte_ent_post', methods: ['POST'])]
    public function postOne(Request $request, ApiService $apiService): Response
    {
        $parameters = json_decode($request->getContent(), true);
        if ($parameters == null) {
            return $this->json(["message" => "Mauavais format"], 400);
        }
        $siren = $parameters["siren"];
        $siret = $parameters["siret"];
        $adresse = $parameters["adresse"];
        $num = $adresse["num"];
        $voie = $adresse["voie"];
        $code_postale = $adresse["code_postale"];
        $ville = $adresse["ville"];

        $parameters["adresse"] = $num . " " . $voie . " " . $code_postale . " " . $ville;

        $file = $apiService->getFileIfExists($siren);
        if (!$file) {
            $file_content = "Raison sociale : " . $parameters["raison_sociale"] . "\nSIREN : " . $siren . "\nSIRET : " . $siret . "\nAdresse : " . $parameters["adresse"];
            file_put_contents("./companies/" . $siren . ".txt", $file_content);
            return $this->json($parameters, 201);
        } else {
            return $this->json(["message" => "Il y a déjà une entreprise avec ce SIREN"], 409);
        }
    }
}
