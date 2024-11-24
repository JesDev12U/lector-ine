<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$response = [];

try {
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $tempFile = $_FILES['image']['tmp_name'];
    if (is_uploaded_file($tempFile)) {
      // Ruta temporal de salida
      $outputFile = tempnam(sys_get_temp_dir(), 'tesseract_output');
      exec("tesseract $tempFile $outputFile -l spa", $output, $returnCode);

      // Depuración: Verifica si Tesseract se ejecutó
      $response['exec_output'] = $output;
      $response['exec_return_code'] = $returnCode;

      if ($returnCode === 0) {
        $response['status'] = 'success';
        $response['fulltext'] = file_get_contents($outputFile . '.txt');
        $patternName = '/(?<=NOMBRE\s)[A-Z\s]+(?=\sDOMICILIO)/i'; //regex para el nombre
        $patternAddress = '/(?<=DOMICILIO\s)[A-Z\s0-9\.,]+(?=\sCLAVE)/i'; //regex para el domicilio
        $patternCURP = '/\b([A-Z][AEIOUX][A-Z]{2}\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01])[HM](?:AS|B[CS]|C[CLMSH]|D[FG]|G[TR]|HG|JC|M[CNS]|N[ETL]|OC|PL|Q[TR]|S[PLR]|T[CSL]|VZ|YN|ZS)[B-DF-HJ-NP-TV-Z]{3}[A-Z\d])(\d)\b/';

        //Validamos el nombre
        if (preg_match($patternName, $response['fulltext'], $matches)) {
          $splitName = [];
          $name = trim($matches[0], "\n");
          $arrayName = explode("\n", $name);
          $splitName['paternal_surname'] = $arrayName[0];
          $splitName['maternal_surname'] = $arrayName[1];
          $splitName['names'] = $arrayName[2];
          $response['name'] = $splitName;
        } else {
          $response['status'] = 'error';
          $response['error'] = 'NOMBRE no encontrado';
        }

        //Validamos el domicilio
        if (preg_match($patternAddress, $response['fulltext'], $matches)) {
          $response['address'] = str_replace("\n", " ", trim($matches[0], "\n"));
        } else {
          $response['status'] = 'error';
          $response['error'] = 'DOMICILIO no encontrado';
        }

        //Validamos el CURP
        if (preg_match($patternCURP, $response['fulltext'], $matches)) {
          $response['curp'] = $matches[0];
        } else {
          $response['status'] = 'error';
          $response['error'] = "CURP no encontrada";
        }
        // Limpia el archivo temporal
        unlink($outputFile . '.txt');
      } else {
        $response['status'] = 'error';
        $response['error'] = 'Error al procesar la imagen con Tesseract.';
      }
    } else {
      $response['status'] = 'error';
      $response['error'] = 'Error al subir el archivo.';
    }
  } else {
    $response['status'] = 'error';
    $response['error'] = 'No se recibió ninguna imagen.';
  }
} catch (Exception $e) {
  $response['status'] = 'error';
  $response['error'] = 'Excepción: ' . $e->getMessage();
}

// Devuelve la respuesta JSON
echo json_encode($response);
