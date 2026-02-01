<?php

class TelegramAPI
{
    private $config;
    private $baseUrl;
    
    public function __construct($config)
    {
        $this->config = $config;
        $this->baseUrl = "https://api.telegram.org/bot" . $config['telegram']['bot_token'];
    }
    
    /**
     * Send message to user
     */
    public function sendMessage($chatId, $text, $params = [])
    {
        $data = array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ], $params);
        
        return $this->request('sendMessage', $data);
    }
    
    /**
     * Send typing indicator (chat_action)
     */
    public function sendTyping($chatId)
    {
        return $this->request('sendChatAction', [
            'chat_id' => $chatId,
            'action' => 'typing'
        ]);
    }
    
    /**
     * Helper to make requests
     */
    private function request($method, $data = [])
    {
        $url = $this->baseUrl . '/' . $method;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400 || ($result['ok'] ?? false) === false) {
            // Log error or throw exception depending on your style
            // For now just return result with error info
            return $result;
        }
        
        return $result;
    }
}
