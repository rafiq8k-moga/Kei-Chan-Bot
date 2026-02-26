<?php

class WhatsAppAPI
{
    private $config;
    private $token;
    private $baseUrl = 'https://api.fonnte.com';
    private $logger;
    
    public function __construct($config)
    {
        $this->config = $config;
        $this->token = $config['whatsapp']['fonnte_token'];
        
        // Initialize logger
        require_once __DIR__ . '/../utils/Logger.php';
        $this->logger = new Logger($config['paths']['logs']);
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
        
        $this->logger->info("WhatsApp API Send Message", [
            'target' => $target,
            'message_length' => strlen($text),
            'data' => $data
        ]);
        
        $result = $this->request('/send', $data);
        
        $this->logger->info("WhatsApp API Response", [
            'target' => $target,
            'result' => $result,
            'success' => ($result['status'] ?? false) === true
        ]);
        
        return $result;
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
        
        $this->logger->info("Fonnte API Request", [
            'url' => $url,
            'endpoint' => $endpoint,
            'data' => $data,
            'token_length' => strlen($this->token)
        ]);
        
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
        $curlError = curl_error($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        $this->logger->info("Fonnte API Raw Response", [
            'http_code' => $httpCode,
            'curl_error' => $curlError,
            'response' => $response,
            'decoded_result' => $result
        ]);
        
        if ($httpCode >= 400 || ($result['status'] ?? false) === false) {
            $this->logger->error("Fonnte API Error", [
                'http_code' => $httpCode,
                'result' => $result,
                'curl_error' => $curlError
            ]);
            return $result;
        }
        
        return $result;
    }
}
