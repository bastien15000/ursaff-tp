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
        
        $error_validator = $apiService->parametersValidator($parameters);
        if($error_validator != "") {
            return $this->json(["message" => $error_validator], 400);
        } else {
            $parameters["adresse"] = $apiService->getAdresseString($parameters["adresse"]);
    
            $file = $apiService->getFileIfExists($parameters["siren"]);
            if (!$file) {
                $file_content = "Raison sociale : " . $parameters["raison_sociale"] . "\nSIREN : " . $parameters["siren"] . "\nSIRET : " . $parameters["siret"] . "\nAdresse : " . $parameters["adresse"];
                file_put_contents("./companies/" . $parameters["siren"] . ".txt", $file_content);
                return $this->json($parameters, 201);
            } else {
                return $this->json(["message" => "Il y a déjà une entreprise avec ce SIREN"], 409);
            }
        }
    }

    #[Route('/api-ouverte-ent/{siren}', name: 'api_ouverte_ent_update_one', methods: ['PATCH'])]
    public function updateOne(Request $request, ApiService $apiService): Response
    {
        if($apiService->hasApiAcess($request)) {
            $parameters = json_decode($request->getContent(), true);
            $error_validator = $apiService->parametersValidator($parameters);
            if($error_validator != "") {
                return $this->json(["message" => $error_validator], 400);
            } else {
                $siren = $request->get('siren');
                $file = $apiService->getFileIfExists($siren);
                if ($file) {
                    $sirenBody = isset($parameters["siren"]) ? $parameters["siren"] : $file->siren;
                    $siret = isset($parameters["siret"]) ? $parameters["siret"] : $file->siret;
                    $raison_sociale = isset($parameters["raison_sociale"]) ? $parameters["raison_sociale"] : $file->raison_sociale;
                    $parameters["adresse"] = $apiService->getAdresseString($parameters["adresse"]);
                    $path = "./companies/" . $siren . ".txt";
                    $file_content = "Raison sociale : " . $raison_sociale . "\nSIREN : " . $sirenBody . "\nSIRET : " . $siret . "\nAdresse : " . $parameters["adresse"];
                    file_put_contents($path, $file_content);
                    if(isset($parameters["siren"])) {
                        rename($path, "./companies/" . $sirenBody . ".txt");
                    }
                    return $this->json($parameters, 200);
                } else {
                    return $this->json(["message" => "Aucune entreprise avec ce SIREN"], 404);
                }
            }
        } else {
            return $this->json(["message" => "Non authentifié"], 401);
        }
    }

    #[Route('/api-ouverte-ent/{siren}', name: 'api_ouverte_ent_delete_one', methods: ['DELETE'])]
    public function deleteOne(Request $request, ApiService $apiService): Response
    {
        if($apiService->hasApiAcess($request)) {
            $siren = $request->get('siren');
            $file = $apiService->getFileIfExists($siren);
            if ($file) {
                unlink("./companies/" . $siren . ".txt");
                return $this->json($file, 200);
            } else {
                return $this->json(["message" => "Aucune entreprise avec ce SIREN"], 404);
            }
        } else {
            return $this->json(["message" => "Non authentifié"], 401);
        }

    }
}
