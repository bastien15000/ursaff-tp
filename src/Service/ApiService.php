<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiService
{

    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    public function scanFilesCompanies(): array
    {
        $dir = "companies";
        $files = scandir($dir);
        $txtFiles = preg_grep('~\.txt$~', $files);

        $fileContents = array();
        foreach ($txtFiles as $file) {
            $content = file_get_contents($dir . '/' . $file);
            $lines = explode("\n", $content);
            $data = array();
            foreach ($lines as $line) {
                $parts = explode(" : ", $line);
                $key = str_replace(" ", "_", strtolower(trim($parts[0])));
                $value = trim($parts[1]);
                $data[$key] = $value;
            }
            $fileContents[str_replace(".txt", "", $file)] = (object) $data;
        }

        return $fileContents;
    }

    public function convertToCSV($fileContents)
    {
        ob_start();
        $output = fopen('php://output', 'w');
        foreach ($fileContents as $fileContent) {
            fputcsv($output, (array) $fileContent);
        }
        fclose($output);
        $csv = ob_get_clean();
        return $csv;
    }

    public function getFileIfExists($siren)
    {
        $fileContents = $this->scanFilesCompanies();
        $file_by_siren = null;
        foreach ($fileContents as $key => $file) {
            if ($key == $siren) {
                $file_by_siren = $file;
            }
        }
        return $file_by_siren;
    }

    public function getAdresseString($params_adresse)
    {
        $adresse = $params_adresse;
        $num = $adresse["num"];
        $voie = $adresse["voie"];
        $code_postal = $adresse["code_postal"];
        $ville = $adresse["ville"];
        return $num . " " . $voie . " " . $code_postal . " " . $ville;
    }

    public function hasApiAcess($request)
    {
        $authorization = base64_encode(str_replace("Basic ", "", $request->headers->all()["authorization"][0]));
        $api_key = $this->params->get('api.key');
        return $authorization == $api_key;
    }

    public function parametersValidator($parameters)
    {
        $message = "";
        if ($parameters == null) {
            return $message = "Mauavais format";
        }
        if (isset($parameters["siren"]) && (gettype($parameters["siren"]) != "integer" || strlen((string)$parameters["siren"]) != 9)) {
            $message = "Le SIREN doit avoir 9 chiffres !";
        }
        if (isset($parameters["siret"]) && (gettype($parameters["siret"]) != "integer" || strlen((string)$parameters["siret"]) != 14)) {
            $message = "Le SIRET doit avoir 14 chiffres !";
        }
        if (isset($parameters["raison_sociale"]) && $parameters["raison_sociale"] == "") {
            $message = "La raison sociale ne peut pas être vide !";
        }
        $adresse = $parameters["adresse"];
        if (!(isset($adresse["num"]) && isset($adresse["voie"]) && isset($adresse["code_postal"]) && isset($adresse["ville"]))) {
            $message = "Adresse invalide, paramètre manquant. Paramètres à renseigner: num, voie, code_postal, ville";
        } else {
            if (gettype($adresse["code_postal"]) != "integer" || strlen((string)$adresse["code_postal"]) != 5) {
                $message = "Le code postal doit avoir 5 chiffres !";
            }
            if (isset($adresse["ville"]) && $adresse["ville"] == "") {
                $message = "La ville ne peut pas être vide !";
            }
        }

        return $message;
    }
}
