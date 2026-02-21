<?php

class WhatsAppAPI
{
    private $config;
    private $token;
    private $baseUrl = 'https://api.fonnte.com';
    
    public function __construct($config)
    {
        $this->config = $config;
        $this->token = $config['whatsapp']['fonnte_token'];
    }
    
    /**
     * Send text message to WhatsApp number
     */
    public function sendMessage($target, $text, $params = [])
    {
        $data = array_merge([
            'target' => $target,
            'message' => $text,
        ], $params);
        
        return $this->request('/send', $data);
    }

    /**
     * Send photo/file to WhatsApp number
     */
    public function sendPhoto($target, $url, $caption = null, $params = [])
    {
        $data = array_merge([
            'target' => $target,
            'url' => $url,
            'message' => $caption ?? '', // Fonnte uses message for caption
        ], $params);
        
        return $this->request('/send', $data);
    }
    
    /**
     * Helper to make requests to Fonnte API
     */
    private function request($endpoint, $data = [])
    {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array(
                'Authorization: ' . $this->token
            ),
            CURLOPT_SSL_VERIFYPEER => false,
        ));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400 || ($result['status'] ?? false) === false) {
            return $result;
        }
        
        return $result;
    }
}
