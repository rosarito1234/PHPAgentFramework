<?php
require_once 'Agent.php';


class DBAgent extends Agent
{
    protected $pdo;

    public function __construct()
    {
        // Load config
        $settings = require_once __DIR__ . '/config.php';

        // Establish DB connection PDO
        $dsn = "mysql:host={$settings['db_host']};dbname={$settings['db_name']}";
        try {
            $this->pdo = new PDO(
                $dsn,
                $settings['db_user'],
                $settings['db_pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die("❌ Database connection failed: " . $e->getMessage());
        }

        // Create agent
        parent::__construct(
            name: "DBAgent",
            role: "DBA",
            capabilities: [
                "Inspect existing database structure",
                "Design database schema based on requirements",
                "Generate and execute SQL queries",
                "Evaluate if new user stories require DB changes"
            ],
            tools: [
                "inspectStructure",
                "executeSQLQuery"
            ],
            model: $settings['default_model'],
            apiKey: $settings['openai_api_key'],
            llmApiUrl: $settings['llm_api_url']
        );
    }


    // Tool: Inspect DB structure
    public function inspectStructure(): string
    {
        try {
            $tables = $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            $structure = "Tables in database:\n";

            foreach ($tables as $table) {
                $cols = $this->pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
                $structure .= "\n🔹 $table:\n";
                foreach ($cols as $col) {
                    $structure .= " - {$col['Field']} ({$col['Type']})\n";
                }
            }

            $this->addToMemory("Database structure:\n" . $structure);
            return $structure;
        } catch (PDOException $e) {
            return "Error reading structure: " . $e->getMessage();
        }
    }

    // Tool: Execute SQL from LLM or decision logic
    public function executeSQLQuery(string $sql): string
    {
        try {
            $this->pdo->exec($sql);
            $this->addToMemory("Executed SQL: $sql");
            return "✅ Query executed successfully:\n$sql";
        } catch (PDOException $e) {
            $this->addToMemory("❌ SQL Error: " . $e->getMessage());
            return "❌ SQL Error:\n" . $e->getMessage();
        }
    }

    // Capability: Generate SQL based on user story
    public function proposeSQLForUserStory(string $stories): string
    {
        $prompt = "Based on these user stories:\n\"$stories\"\n\n" .
                  "Suggest what SQL actions should be taken (CREATE TABLE, INSERT, etc) and provide the SQL code to generate the Database.";
        $response = $this->callLLM($prompt);
        $this->addToMemory("SQL proposal for story: $stories\n$response");
        return $response;
    }

    // Execute SQL only after approval
    public function confirmAndRunSQL(string $sql): string
    {
        return $this->executeSQLQuery($sql);
    }

    // Capability: Check if story requires DB changes
    public function assessImpactOfStory(string $story): string
    {
        $structure = $this->inspectStructure();
        $prompt = "Given this current database structure:\n$structure\n\n" .
                  "Does the following user story require any schema changes or new data?\n\"$story\"\n" .
                  "Reply Yes or No, and explain why.";
        return $this->callLLM($prompt);
    }
}
