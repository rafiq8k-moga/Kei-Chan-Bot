<?php

require_once __DIR__ . '/../services/TelegramAPI.php';
require_once __DIR__ . '/../services/GroqAPI.php';
require_once __DIR__ . '/../utils/MemoryManager.php';
require_once __DIR__ . '/../utils/Logger.php';

class ChatAgent
{
    private $config;
    private $telegramApi; // Changed from fbApi
    private $groqApi;
    private $memory;
    private $logger;
    
    private $systemPrompt;
    
    public function __construct($config)
    {
        $this->config = $config;
        $this->telegramApi = new TelegramAPI($config); // Init TelegramAPI
        $this->groqApi = new GroqAPI($config);
        $this->memory = new MemoryManager($config);
        $this->logger = new Logger($config['paths']['logs']);
        
        $this->systemPrompt = $this->loadSystemPrompt();
    }
    
    private function loadSystemPrompt()
    {
        $sysFile = __DIR__ . '/../sys.txt';
        if (file_exists($sysFile)) {
            return file_get_contents($sysFile);
        }
        return "You are Kei.";
    }
    
    /**
     * Handle incoming Telegram message
     */
    public function handleMessage($chatId, $message)
    {
        try {
            $this->logger->info("Received message", [
                'chat_id' => $chatId,
                'message' => $message,
            ]);
            
            // Show typing indicator
            $this->telegramApi->sendTyping($chatId);
            
            // Load history
            $history = $this->memory->load($chatId);
            
            // Add user message to memory
            $this->memory->add($chatId, 'user', $message);
            
            // Prepare messages for Groq
            $messages = [];
            foreach ($history as $msg) {
                $messages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content'],
                ];
            }
            $messages[] = [
                'role' => 'user',
                'content' => $message,
            ];
            
            // Get AI response
            $response = $this->groqApi->chat($messages, $this->systemPrompt);
            
            $this->logger->info("Groq response", ['response' => $response]);
            
            // Add assistant message to memory
            $this->memory->add($chatId, 'assistant', $response);
            
            // Send response to Telegram
            $this->telegramApi->sendMessage($chatId, $response);
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error("Chat handling failed: " . $e->getMessage());
            
            $this->telegramApi->sendMessage(
                $chatId, 
                "Hmph, sirkuit logikaku error. Bukan salahku ya! Coba lagi nanti."
            );
            
            return false;
        }
    }
    
    /**
     * Handle commands
     */
    public function handleCommand($chatId, $command)
    {
        switch (strtolower(trim($command))) {
            case '/start':
            case '/mulai':
                $this->memory->clear($chatId);
                $this->telegramApi->sendMessage(
                    $chatId,
                    "Hah? Mulai dari awal? ...Baiklah. Aku Kei, Guardian of the Nameless Priest. Ada yang bisa kubantu? (Bot Otomatis)"
                );
                break;
                
            case '/reset':
            case '/hapus':
                $this->memory->clear($chatId);
                $this->telegramApi->sendMessage(
                    $chatId,
                    "Hmph, memori dihapus. Jangan lupa berterima kasih!"
                );
                break;
                
            case '/help':
            case '/bantuan':
                $helpText = "Perintah:\n/mulai - Reset percakapan\n/hapus - Hapus memori\n/bantuan - Info ini";
                $this->telegramApi->sendMessage($chatId, $helpText);
                break;
                
            default:
                // Treat unknown command as text message? Or ignore?
                // For now, let's treat it as unknown command
                return false;
        }
        
        return true;
    }
}
