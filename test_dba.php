<?php
require_once 'DBAgent.php';

header('Content-Type: application/json');

// Simple mensaje de entrada (por POST)
$userInput = $_POST['message'] ?? '';

if (empty($userInput)) {
    echo json_encode(['response' => '👋 Hi! Please enter a user story or ask me to inspect the database.']);
    exit;
}

// Crear instancia
$dba = new DBAgent();

// Analiza input
$response = "";

// Comandos básicos para testear
if (stripos($userInput, 'inspect') !== false) {
    $response = $dba->inspectStructure();
} elseif (stripos($userInput, 'assess') !== false) {
    // Assess impact of a story
    $response = $dba->assessImpactOfStory($userInput);
} elseif (stripos($userInput, 'propose') !== false) {
    // Propose SQL
    $response = $dba->proposeSQLForUserStory($userInput);
} elseif (stripos($userInput, 'execute') !== false) {
    // Ejecuta directamente el SQL enviado
    $sqlStart = strpos($userInput, 'execute') + 7;
    $sqlToRun = trim(substr($userInput, $sqlStart));
    $response = $dba->confirmAndRunSQL($sqlToRun);
} else {
    // Default: Asume que es una historia de usuario
    $response = $dba->proposeSQLForUserStory($userInput);
}

echo json_encode(['response' => $response]);
