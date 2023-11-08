<?php

namespace App\Service;

class ApiService {
    public function scanFilesCompanies(): array {
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

    public function convertToCSV($fileContents) {
        ob_start();
        $output = fopen('php://output', 'w');
        foreach ($fileContents as $fileContent) {
            fputcsv($output, (array) $fileContent);
        }
        fclose($output);
        $csv = ob_get_clean();
        return $csv;
    }

    public function getFileIfExists($siren) {
        $fileContents = $this->scanFilesCompanies();
        $file_by_siren = null;
        foreach ($fileContents as $key => $file) {
            if ($key == $siren) {
                $file_by_siren = $file;
            }
        }
        return $file_by_siren;
    }
}