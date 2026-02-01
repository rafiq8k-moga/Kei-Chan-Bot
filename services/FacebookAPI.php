<?php

class FacebookAPI
{
    private $config;
    private $baseUrl;
    
    public function __construct($config)
    {
        $this->config = $config;
        $this->baseUrl = "https://graph.facebook.com/{$config['facebook']['api_version']}";
    }
    
    /**
     * Post photo to Facebook Page
     */
    public function postPhoto($message, $imageUrl)
    {
        $endpoint = "{$this->baseUrl}/{$this->config['facebook']['page_id']}/photos";
        
        $data = [
            'url' => $imageUrl,
            'caption' => $message,
            'access_token' => $this->config['facebook']['access_token'],
        ];
        
        return $this->request($endpoint, 'POST', $data);
    }
    
    /**
     * Send message to user
     * messaging_type: RESPONSE (default), UPDATE, or MESSAGE_TAG
     */
    public function sendMessage($recipientId, $message, $messagingType = 'RESPONSE')
    {
        $endpoint = "{$this->baseUrl}/me/messages";
        
        $data = [
            'recipient' => ['id' => $recipientId],
            'messaging_type' => $messagingType,
            'message' => ['text' => $message],
            'access_token' => $this->config['facebook']['access_token'],
        ];
        
        return $this->request($endpoint, 'POST', $data);
    }
    
    /**
     * Send typing indicator
     */
    public function sendTypingOn($recipientId)
    {
        $endpoint = "{$this->baseUrl}/me/messages";
        
        $data = [
            'recipient' => ['id' => $recipientId],
            'sender_action' => 'typing_on',
            'access_token' => $this->config['facebook']['access_token'],
        ];
        
        return $this->request($endpoint, 'POST', $data);
    }
    
    /**
     * HTTP request helper
     */
    private function request($url, $method = 'GET', $data = [])
    {
        $ch = curl_init();
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } elseif ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400) {
            throw new Exception("Facebook API Error: " . ($result['error']['message'] ?? 'Unknown error'));
        }
        
        return $result;
    }
}
