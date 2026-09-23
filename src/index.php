<?php
// src/index.php

function checkElasticsearchConnectivity() {
    $elasticsearchUrl = getenv('ELASTICSEARCH_URLS');

    if (empty($elasticsearchUrl)) {
        return [
            'configured' => false,
            'status' => 'NOT_CONFIGURED',
            'message' => 'Variable ELASTICSEARCH_URLS no configurada'
        ];
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $elasticsearchUrl);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return [
            'configured' => true,
            'status' => 'DOWN',
            'message' => "Error de conexión: $error",
            'url' => $elasticsearchUrl
        ];
    }

    if ($httpCode === 200 || $httpCode === 401) {
        return [
            'configured' => true,
            'status' => 'UP',
            'message' => "Conectado (HTTP $httpCode)",
            'url' => $elasticsearchUrl
        ];
    }

    return [
        'configured' => true,
        'status' => 'DOWN',
        'message' => "Respuesta HTTP $httpCode",
        'url' => $elasticsearchUrl
    ];
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Ruta de healthcheck: /actuator/health
if ($path === '/actuator/health') {
    header('Content-Type: application/json');
    $elasticsearch = checkElasticsearchConnectivity();
    http_response_code(200);
    echo json_encode([
        'status' => 'UP',
        'timestamp' => date(DATE_ATOM),
        'elasticsearch' => $elasticsearch
    ]);
    exit;
}

// Ruta raíz: /
if ($path === '/' || $path === '') {
    header('Content-Type: text/html; charset=utf-8');
    $elasticsearch = checkElasticsearchConnectivity();
    $esStatus = $elasticsearch['status'];
    $esColor = $esStatus === 'UP' ? 'green' : ($esStatus === 'NOT_CONFIGURED' ? 'orange' : 'red');

    echo '<h1>PHP Fargate OK</h1>';
    echo '<p>Aplicación mínima en puerto 8080.</p>';
    echo '<hr>';
    echo '<h2>Estado de OpenSearch/Elasticsearch</h2>';
    echo '<p><strong>Estado:</strong> <span style="color: ' . $esColor . '; font-weight: bold;">' . $esStatus . '</span></p>';
    echo '<p><strong>Mensaje:</strong> ' . htmlspecialchars($elasticsearch['message']) . '</p>';
    if (!empty($elasticsearch['url'])) {
        echo '<p><strong>URL:</strong> ' . htmlspecialchars($elasticsearch['url']) . '</p>';
    }
    exit;
}

// Cualquier otra ruta
http_response_code(404);
header('Content-Type: application/json');
echo json_encode([
    'status'  => 'NOT_FOUND',
    'path'    => $path,
]);