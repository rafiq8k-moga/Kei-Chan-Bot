<?php

class ImageService
{
    private $config;
    private $providers = [
        'danbooru' => 'https://danbooru.donmai.us',
        'safebooru' => 'https://safebooru.donmai.us',
    ];
    
    public function __construct($config)
    {
        $this->config = $config;
    }
    
    /**
     * Get random image from Danbooru/Safebooru
     * Uses tag: kei_(new_body)_(blue_archive)
     * @param string $provider 'danbooru' or 'safebooru'
     */
    public function getRandomImage($provider = 'safebooru')
    {
        $baseUrl = $this->providers[$provider] ?? $this->providers['safebooru'];
        
        // Always use Kei tag regardless of keywords
        $tag = 'kei_(new_body)_(blue_archive)';
        
        // Build query tags
        // order:random is standard for Danbooru-based boorus
        $tags = "$tag order:random";
        
        if ($provider === 'safebooru') {
            $tags .= ' rating:general'; // Safebooru is mostly safe, but explicit rating tag helps
        } else {
            $tags .=  'rating:explicit';
            // For Danbooru (NSFW), we might want explicitly questionable or explicit, 
            // or just let it be random (which includes NSFW).
            // User requested /imgnsfw -> Danbooru. 
            // We won't restrict rating for Danbooru to allow NSFW.
        }
        
        $url = "{$baseUrl}/posts.json?" . http_build_query([
            'tags' => $tags,
            'limit' => 1,
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'KeiBot/1.0 (Telegram Bot)');
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
        
        return $post['large_file_url'] ?? $post['file_url'] ?? null;
    }
    
    /**
     * Get specific post by ID
     */
    public function getPostById($postId, $provider = 'safebooru')
    {
        $baseUrl = $this->providers[$provider] ?? $this->providers['safebooru'];
        $url = "{$baseUrl}/posts/{$postId}.json";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'KeiBot/1.0 (Telegram Bot)');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        return $data['large_file_url'] ?? $data['file_url'] ?? null;
    }
}
