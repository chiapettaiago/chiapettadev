<?php 
    header('Content-Type: application/json');

     $latitude = -22.4165;
     $longitude = -42.9752;

     // Solicita current_weather e hourly para obter relativa umidade
     $url = "https://api.open-meteo.com/v1/forecast?" .
         "latitude=$latitude&longitude=$longitude" .
         "&current_weather=true" .
         "&hourly=relativehumidity_2m,windspeed_10m" .
         "&timezone=America/Sao_Paulo";

     $response = @file_get_contents($url);
     if ($response === false) {
          http_response_code(502);
          echo json_encode(["error" => "Não foi possível obter dados da API de clima"]);
          exit;
     }

     echo $response;


?>