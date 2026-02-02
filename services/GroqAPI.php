<?php

class GroqAPI
{
    private $config;
    private $apiKey;
    private $baseUrl = 'https://api.groq.com/openai/v1';
    
    public function __construct($config)
    {
        $this->config = $config;
        $this->apiKey = $config['groq']['api_key'];
    }
    
    /**
     * Chat completion
     * Users should format messages as an array of ['role' => '...', 'content' => '...']
     */
    public function chat($messages)
    {
        $endpoint = "{$this->baseUrl}/chat/completions";
        
        $data = [
            'model' => $this->config['groq']['model'],
            'messages' => $messages,
            'max_tokens' => $this->config['groq']['max_tokens'],
            'temperature' => $this->config['groq']['temperature'],
        ];
        
        return $this->request($endpoint, $data);
    }
    
    /**
     * HTTP request to Groq API
     */
    private function request($url, $data)
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400) {
            throw new Exception("Groq API Error: " . ($result['error']['message'] ?? 'Unknown error'));
        }
        
        return $result['choices'][0]['message']['content'] ?? '';
    }
}
