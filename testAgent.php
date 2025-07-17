<?php

// This class is used to perform various tests with the Agents and  what we are doing
// Load config and base Agent class
$config = include('config.php');
require_once('Agent.php');

// Create a test agent
$agent = new Agent(
    name: "Backend Dev",
    role: "Backend Developer",
    capabilities: ["write_php", "integrate_db"],
    tools: ["generate_php_class", "create_sql_table"],
    model: $config['default_model'],
    apiKey: $config['openai_api_key']
);

// Simulate memory (task history or notes)
$agent->addToMemory("Defined table structure for reservations with fields: id, user_id, date, time.");
$agent->addToMemory("Coordinated with frontend about required API endpoints.");
$agent->addToMemory("Discussed validation logic for overlapping reservation slots.");
$agent->addToMemory("Waiting for DBA confirmation on foreign key constraints.");

// Ask agent to summarize memory for another agent (e.g., PM or another dev)
$summary = $agent->summarizeMemoryForLLM(
    recipientRole: "Product Manager",
    goal: "Validate that all backend work on reservation system is aligned with user requirements"
);

// Output to console
echo "=== Memory Summary for PM ===\n";
echo $summary . "\n";
