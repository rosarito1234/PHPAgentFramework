<?php
session_start();

require_once('config.php');
require_once('Agent.php');
require_once('PMAgent.php');

// Load agent from session or create new one
if (!isset($_SESSION['pm_agent'])) {
    $config = include('config.php');
    $pm = new PMAgent('gpt-4', $config['openai_api_key'], $config['llm_api_url']); // We use a better model for the orchestrator
    $_SESSION['pm_agent'] = serialize($pm);
} else {
    $pm = unserialize($_SESSION['pm_agent']);
}

// Get user input
$userInput = $_POST['message'] ?? '';

// Run interaction
$response = $pm->interact($userInput);

// Save agent state
$_SESSION['pm_agent'] = serialize($pm);

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'response' => $response
]);
