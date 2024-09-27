<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Exception;

class GitHubActivityCommand extends Command
{
        // Command signature: the username argument is required
        protected $signature = 'github-activity {username}';

        // Command description
        protected $description = 'Fetch recent GitHub activity for a user';
    
        // Execute the console command
        public function handle()
        {
            // Get the username from the command arguments
            $username = $this->argument('username');
    
            // GitHub API endpoint
            $url = "https://api.github.com/users/{$username}/events";
    
            // Fetch data using a simple HTTP request (no external libraries)
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        "User-Agent: PHP"
                    ]
                ]
            ]);
    
            try {
                // Make the API request
                $response = file_get_contents($url, false, $context);
    
                if ($response === FALSE) {
                    $this->error("Error fetching data for username: {$username}");
                    return 1;
                }
    
                // Decode the JSON response
                $events = json_decode($response, true);
    
                // Check if we received any events
                if (empty($events)) {
                    $this->info("No recent activity found for user: {$username}");
                    return 0;
                }
    
                // Display the recent events
                foreach ($events as $event) {
                    $action = $this->parseEvent($event);
                    if ($action) {
                        $this->info($action);
                    }
                }
    
                return 0;
    
            } catch (Exception $e) {
                // Handle exceptions gracefully
                $this->error("An error occurred: " . $e->getMessage());
                return 1;
            }
        }
    
        // Helper function to parse the event and return a readable string
        private function parseEvent($event)
        {
            $type = $event['type'];
            $repo = $event['repo']['name'];
    
            switch ($type) {
                case 'PushEvent':
                    $commitCount = count($event['payload']['commits']);
                    return "Pushed {$commitCount} commits to {$repo}";
                case 'IssuesEvent':
                    return "Opened a new issue in {$repo}";
                case 'WatchEvent':
                    return "Starred {$repo}";
                case 'ForkEvent':
                    return "Forked {$repo}";
                default:
                    return null; // Skip other event types
            }
        }
}
