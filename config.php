<?php

return [
    // OpenAI or other LLM API key (do not commit your real key to Git)
    'openai_api_key' => 'your_api_key',

    // Environment: can be 'development', 'staging', or 'production'
    'environment' => 'development',

    // Default model to use for LLM-based agents
    'default_model' => 'gpt-4.1-nano', // or 'gpt-4'

    // Optional: default token limit per interaction
    'token_limit' => 4000,

    // LLM API URL to be used
    'llm_api_url' => 'https://api.openai.com/v1/chat/completions',

    // Database connection settings
    'db_host' => 'localhost',
    'db_name' => 'your_database_name',
    'db_user' => 'your_database_user',
    'db_pass' => 'your_database_password',
    'db_charset' => 'utf8mb4',
];
