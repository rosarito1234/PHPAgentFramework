<?php

require_once('Agent.php');

/**
 * PMAgent – Product Manager Agent
 *
 * Responsible for gathering requirements, asking clarifying questions,
 * generating development plans, and coordinating with technical agents.
 */
class PMAgent extends Agent
{

    protected string $stage = 'waiting_description'; // initial state

    protected bool $pendingClarification = false;

    public function __construct(string $model, string $apiKey, string $llmAPIURL)
    {
        parent::__construct(
            name: "Product Manager",
            role: "PM",
            capabilities: [
                "analyze_product_description",
                "ask_for_clarifications",
                "generate_development_plan",
                "select_tech_stack",
                "delegate_task",
                "generate_user_stories"
            ],
            tools: ["exportUserStoriesToCSV"], // tools can be used by the agent to perform certain tasks
            model: $model,
            apiKey: $apiKey,
            llmApiUrl: $llmAPIURL
        );
    }

    /**
     * Generates clarifying questions from a product description.
     *
     * @param string $description
     * @return string
     */
    public function askForClarifications(string $description): string
    {
        $prompt = '
You are a Product Manager reviewing the following initial product idea:

'.$description.'

Ask up to 5 short and clear questions that would help define this product more precisely before planning its development.';

        return $this->callLLM($prompt);
    }

    /**
     * Generates a high-level development plan based on the description and memory.
     *
     * @param string $description
     * @return string
     */
    public function planDevelopment(string $description): string
    {
        $memoryContext = implode("\n", $this->memory);

        $prompt = 'You are a Product Manager preparing a development plan for the following product:

'.$description.'

Here is additional context and clarifications collected so far:

'.$memoryContext.'

Provide a high-level plan with up to 10 steps to the user, using clear language and separating frontend, backend, and data considerations where relevant.
';

        return $this->callLLM($prompt);
    }

    /**
     * Suggests the next agent to involve based on current goal.
     *
     * @param string $goal
     * @return string Suggested agent role
     */
    public function decideNextAgent(string $goal): string
    {
        $prompt = '
You are coordinating a multi-agent software project. Based on the following goal:

'.$goal.'

Decide which agent should be involved next: Backend Developer, Frontend Developer, DBA, Tester, or Tech Leader. Just return the role name and no explanation.';

        return $this->callLLM($prompt);
    }




    /**
     * Generates user stories (and epics if needed) based on the product description.
     * Format follows standard Jira-style stories: "As a [role], I want to [action] so that [value]"
     *
     * @param string $description
     * @return string
     */
    public function generateUserStories(string $description): string
    {
        $prompt = 'You are a Product Manager creating user stories from the following product idea:

            '.$description.'

            Write a list of Jira-style user stories. Use this format:  
            - As a [user role], I want to [do something] so that [benefit]

            If the product is large or has multiple components, group related stories under appropriate Epics using the format:

            Epic: [Epic Title]  
            - Story 1  
            - Story 2  
            - ...

            Keep the stories clear and actionable. If you need to write more than 15 user stories, suggest to the user to work on separate EPICs so that each Epic will have up to 10 user stories.';

        return $this->callLLM($prompt);
    }

    /**
     * Adds approved user stories to memory for further delegation.
     *
     * @param string $userStoryBlock The full set of user stories as string
     */
    public function storeUserStories(string $userStoryBlock): void
    {
        $this->addToMemory("User Stories:\n" . $userStoryBlock);
    }



    /**
     * Parses and exports user stories to a Jira-compatible CSV file.
     * Potentially an additional tool could be prepare to directly import into JIRA using APIs.
     *
     * @param string $userStoryBlock The full block of stories from LLM
     * @param string $filename Output CSV filename (e.g., "stories.csv")
     * @return string Success or error message
     */
    public function exportUserStoriesToCSV(string $userStoryBlock, string $filename): string
    {
        $rows = [];
        $currentEpic = null;

        $lines = explode("\n", $userStoryBlock);

        foreach ($lines as $line) {
            $line = trim($line);

            if (str_starts_with($line, "Epic:")) {
                // Start of a new Epic
                $currentEpic = trim(substr($line, strlen("Epic:")));
                $rows[] = [
                    "Summary" => $currentEpic,
                    "Issue Type" => "Epic",
                    "Description" => "Group of related user stories",
                    "Epic Name" => $currentEpic,
                    "Epic Link" => ""
                ];
            } elseif (str_starts_with($line, "- As")) {
                // Regular user story
                $rows[] = [
                    "Summary" => substr($line, 2), // remove dash and space
                    "Issue Type" => "Story",
                    "Description" => $line,
                    "Epic Name" => $currentEpic ?? "",
                    "Epic Link" => $currentEpic ?? ""
                ];
            }
        }

        if (empty($rows)) {
            return "⚠️ No stories found to export.";
        }

        $fp = fopen($filename, 'w');
        fputcsv($fp, ["Summary", "Issue Type", "Description", "Epic Name", "Epic Link"]);

        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }

        fclose($fp);

        return "✅ Exported " . count($rows) . " items to $filename";
    }


    /**
     * Auxiliary function for interaction. Determines if additional information is required to move forward.
     * This method enables a chat-like interface with progressive refinement.
     *
     * @param string $userInput
     * @return string
     */

    private function needsMoreInfo(string $stage, string $userInput): array
    {
        $contextSummary = $this->summarizeMemoryForLLM('PM', 'Create an application for the user.'); // or just get memory directly
        $prompt = "You are a product manager agent. The user has provided the following context:\n\n"
                . $contextSummary
                . "\n\nCurrent stage: $stage\n"
                . "User just said: \"$userInput\"\n\n"
                . "Is the information provided so far enough to proceed to the next step in the process? "
                . "Reply with 'yes' if it's enough, or 'I would like some additional information please. ' followed by what is missing in a way that is friendly and a general user will understand.";

        $response = $this->callLLM($prompt);

        if (stripos($response, 'yes') !== false) {
            return ['proceed' => true, 'message' => null];
        }

        return [
            'proceed' => false,
            'message' => $response
        ];
    }

    /**
     * Handles user input and responds on user intention to continue to next step. 
     * This method enables a chat-like interface with progressive refinement.
     *
     * @param string $userInput
     * @return string
     */
    protected function userWantsToProceed(string $context, string $userInput): bool
    {
        $prompt = "You are helping interpret a user's intent in a product development conversation.\n"
                . "Context: $context\n"
                . "User said: \"$userInput\"\n\n"
                . "Based on this, does the user want to proceed? Respond only with YES or NO.";
    
        $response = $this->callLLM($prompt);
    
        return stripos($response, 'YES') !== false;
    }




    /**
     * Handles user input and responds based on current interaction stage.
     * This method enables a chat-like interface with progressive refinement.
     *
     * @param string $userInput
     * @return string
     */
    public function interact(string $userInput): string
    {
        // If we are waiting for more info from the user, store this input and retry logic
        if ($this->pendingClarification) {
            $this->addToMemory("Additional Clarification: $userInput");
            $this->pendingClarification = false;

            // Re-evaluate this input after storing clarification
            return $this->interact($userInput);
        }

        // Check if more info is needed before proceeding with the current stage
        $validation = $this->needsMoreInfo($this->stage, $userInput, $this->memory);
        if (!$validation['proceed']) {
            $this->pendingClarification = true;
            return $validation['message'];  // Ask the user to provide more information
        }

        // === Stage 1: Initial product idea ===
        if ($this->stage === 'waiting_description') {
            $this->addToMemory("Product Idea: $userInput");
            $this->stage = 'asking_clarifications';
            return $this->askForClarifications($userInput);
        }

        // === Stage 2: Answering clarifications ===
        if ($this->stage === 'asking_clarifications') {
            $this->addToMemory("User Clarifications: $userInput");
            $this->stage = 'planning';
            return $this->planDevelopment($this->getProductIdeaFromMemory());
        }

        // === Stage 3: Planning done, ask if user wants stories ===
        if ($this->stage === 'planning') {
            if ($this->userWantsToProceed("Generate user stories", $userInput)) {
                $this->stage = 'stories_generated';
                $stories = $this->generateUserStories($this->getProductIdeaFromMemory());
                $this->storeUserStories($stories);
                return $stories . "\n\nWould you like me to export these stories to a Jira-compatible CSV file?";
            } else {
                return "Okay, let me know when you'd like to proceed with generating user stories.";
            }
        }

        // === Stage 4: Exporting stories ===
        if ($this->stage === 'stories_generated') {
            if ($this->userWantsToProceed("Export user stories to CSV", $userInput)) {
                $filename = "stories_" . time() . ".csv";
                $result = $this->exportUserStoriesToCSV($this->getLastUserStoriesFromMemory(), $filename);
                $this->stage = 'completed';
                return $result;
            } else {
                return "Understood. You can export the stories later at any time.";
            }
        }

        return "I'm ready to continue when you are!";
    }


    /**
     * Gets the first product idea from memory.
     */
    private function getProductIdeaFromMemory(): string
    {
        foreach ($this->memory as $entry) {
            if (str_starts_with($entry, "Product Idea:")) {
                return trim(substr($entry, strlen("Product Idea:")));
            }
        }
        return "";
    }

    /**
     * Gets the most recent user story block stored in memory.
     */
    private function getLastUserStoriesFromMemory(): string
    {
        foreach (array_reverse($this->memory) as $entry) {
            if (str_starts_with($entry, "User Stories:")) {
                return trim(substr($entry, strlen("User Stories:")));
            }
        }
        return "";
    }





    
}
