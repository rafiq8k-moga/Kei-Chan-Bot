<?php

require_once __DIR__ . '/../services/FacebookAPI.php';
require_once __DIR__ . '/../services/ImageService.php';
require_once __DIR__ . '/../utils/Logger.php';

class AutoPostAgent
{
    private $config;
    private $fbApi;
    private $imageService;
    private $logger;
    
    public function __construct($config)
    {
        $this->config = $config;
        $this->fbApi = new FacebookAPI($config);
        $this->imageService = new ImageService($config);
        $this->logger = new Logger($config['paths']['logs']);
    }
    
    /**
     * Execute auto-post based on current time
     */
    public function execute()
    {
        try {
            $this->logger->info("Starting auto-post agent");
            
            // Get appropriate greeting
            $greeting = $this->getCurrentGreeting();
            
            if (!$greeting) {
                $this->logger->error("No greeting found for current time");
                return false;
            }
            
            $this->logger->info("Selected greeting", [
                'text' => $greeting['text'],
            ]);
            
            // Get random image of Kei from Danbooru
            $imageUrl = $this->imageService->getRandomImage();
            
            if (!$imageUrl) {
                $this->logger->error("Failed to fetch image");
                return false;
            }
            
            $this->logger->info("Fetched image", ['url' => $imageUrl]);
            
            // Post to Facebook
            $result = $this->fbApi->postPhoto($greeting['text'], $imageUrl);
            
            $this->logger->info("Posted to Facebook", ['result' => $result]);
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error("Auto-post failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get greeting for current time
     */
    private function getCurrentGreeting()
    {
        $hour = (int) date('H');
        
        foreach ($this->config['greetings'] as $greeting) {
            list($start, $end) = $greeting['time_range'];
            
            // Handle overnight range (18-5)
            if ($start > $end) {
                if ($hour >= $start || $hour < $end) {
                    return $greeting;
                }
            } else {
                if ($hour >= $start && $hour < $end) {
                    return $greeting;
                }
            }
        }
        
        return null;
    }
}
