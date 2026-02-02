<?php

class ImageService
{
    private $config;
    private $baseUrl = 'https://safebooru.donmai.us';
    
    public function __construct($config)
    {
        $this->config = $config;
    }
    
    /**
     * Get random image from Danbooru
     * Uses tag: kei_(new_body)_(blue_archive)
     */
    public function getRandomImage($keywords = null)
    {
        // Always use Kei tag regardless of keywords
        $tag = 'kei_(new_body)_(blue_archive)';
        
        // Get random post with Kei tag
        // Note: Danbooru uses order:random, not random=true
        $url = "{$this->baseUrl}/posts.json?" . http_build_query([
            'tags' => $tag . ' rating:safe order:random',
            'limit' => 1,
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'KeiBot/1.0 (Facebook Page Bot)');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (empty($data) || !isset($data[0])) {
            return null;
        }
        
        $post = $data[0];
        
        // Return the image URL (prefer large_file_url, fallback to file_url)
        return $post['large_file_url'] ?? $post['file_url'] ?? null;
    }
    
    /**
     * Get specific post by ID from Danbooru
     */
    public function getPostById($postId)
    {
        $url = "{$this->baseUrl}/posts/{$postId}.json";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'KeiBot/1.0 (Facebook Page Bot)');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        return $data['large_file_url'] ?? $data['file_url'] ?? null;
    }
}
