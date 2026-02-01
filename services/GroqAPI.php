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
     * Chat completion with conversation history
     */
    public function chat($messages, $systemPrompt = null)
    {
        $endpoint = "{$this->baseUrl}/chat/completions";
        
        // Prepare messages
        $formattedMessages = [];
        
        if ($systemPrompt) {
            $formattedMessages[] = [
                'role' => 'system',
                'content' => $systemPrompt,
            ];
        }
        
        foreach ($messages as $msg) {
            $formattedMessages[] = [
                'role' => $msg['role'],
                'content' => $msg['content'],
            ];
        }
        
        $data = [
            'model' => $this->config['groq']['model'],
            'messages' => $formattedMessages,
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
