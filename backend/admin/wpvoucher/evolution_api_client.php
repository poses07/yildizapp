<?php
function evoRequest($method, $endpoint, $queryParams = [], $bodyParams = [], $headers = []) {
    $config = require __DIR__ . '/config.php';
    $baseUrl = $config['evolution']['base_url'] ?? 'http://localhost:8080';
    $apiKey = $config['evolution']['api_key'] ?? '';
    
    $url = rtrim($baseUrl, '/') . '/' . ltrim($endpoint, '/');
    
    if (!empty($queryParams)) {
        $url .= '?' . http_build_query($queryParams);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    
    $httpHeaders = [
        'Content-Type: application/json',
        'apikey: ' . $apiKey
    ];
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($bodyParams));
    } else {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
    
    $response = curl_exec($ch);
    $err = curl_error($ch);
    // curl_close($ch); // Deprecated in PHP 8.0+ as CurlHandle is automatically closed
    
    if ($err) {
        throw new Exception("cURL Error: " . $err);
    }
    
    return json_decode($response, true);
}
?>