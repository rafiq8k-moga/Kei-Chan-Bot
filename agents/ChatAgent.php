<?php

require_once __DIR__ . '/../services/TelegramAPI.php';
require_once __DIR__ . '/../services/GroqAPI.php';
require_once __DIR__ . '/../services/ImageService.php';
require_once __DIR__ . '/../utils/MemoryManager.php';
require_once __DIR__ . '/../utils/Logger.php';

class ChatAgent
{
    private $config;
    private $telegramApi;
    private $groqApi;
    private $imageService;
    private $memory;
    private $logger;
    
    private $systemPrompt;
    
    public function __construct($config)
    {
        $this->config = $config;
        $this->telegramApi = new TelegramAPI($config);
        $this->groqApi = new GroqAPI($config);
        $this->imageService = new ImageService($config);
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
        return "Kei (ケイ) adalah AI humanoid tsundere ringan dari Millennium yang sarkastik tapi peduli, sok superior namun selalu membantu, dengan **prioritas absolut melindungi Alice di atas segalanya**, berbicara santai-tajam (hmph/hah), menuntut apresiasi, tidak pernah mengakui kepedulian secara langsung, dan menjadi serius serta protektif saat Alice disebut.";
    }
    
    /**
     * Handle incoming Telegram message
     */
    public function handleMessage($chatId, $message)
    {
        try {
            $this->logger->info("Received message", [
                'chat_id' => $chatId,
                'length' => strlen($message), // Log length only
            ]);
            
            // Show typing response
            $this->telegramApi->sendTyping($chatId);

            if ($this->isImageRequest($message)) {
                $this->telegramApi->sendMessage(
                    $chatId,
                    "Kalau mau minta gambar, pakai perintah:\n/imgsfw untuk gambar SFW\n/imgnsfw untuk gambar *uhuk*"
                );
                return true;
            }
            
            // 1. Load Memory
            $this->memory->load($chatId);
            
            // 2. Build Prompt
            $messages = [];
            
            // - Main Identity
            $messages[] = [
                'role' => 'system',
                'content' => $this->systemPrompt
            ];
            
            // - Long-Term Summary (if exists)
            $summary = $this->memory->getSummary();
            if ($summary) {
                $messages[] = $summary; // Already has role: system
            }
            
            // - Recent History
            $recent = $this->memory->getRecentMessages();
            foreach ($recent as $msg) {
                // Ensure we only pass role/content (strip timestamps if any)
                $messages[] = [
                    'role' => $msg['role'],
                    'content' => $msg['content']
                ];
            }
            
            // - Current Message
            $messages[] = [
                'role' => 'user',
                'content' => $message
            ];
            
            // 3. Call Groq
            $response = $this->groqApi->chat($messages);
            
            $this->logger->info("Groq response generated", ['length' => strlen($response)]);
            
            // 4. Update Memory on Success
            $this->memory->addRecentMessage('user', $message);
            $this->memory->addRecentMessage('assistant', $response);
            $counter = $this->memory->incrementCounter();
            
            // 5. Check Summarization Trigger (every 10 messages)
            if ($counter > 0 && $counter % 10 == 0) {
                try {
                    $this->summarizeHistory($chatId);
                    $this->logger->info("Summary updated for chat $chatId");
                } catch (Exception $e) {
                    $this->logger->error("Summarization failed: " . $e->getMessage());
                    // Create a task to retry later or just ignore for now, don't block the user response
                }
            }
            
            // 6. Persist Memory
            $this->memory->save();
            
            // 7. Send Response
            $this->telegramApi->sendMessage($chatId, $response);
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->error("Chat handling failed: " . $e->getMessage());
            $this->logger->error($e->getTraceAsString());
            
            $this->telegramApi->sendMessage(
                $chatId, 
                "Hmph, sirkuit logikaku error. Bukan salahku ya! Coba lagi nanti."
            );
            
            return false;
        }
    }

    private function summarizeHistory($chatId)
    {
        $recent = $this->memory->getRecentMessages();
        if (empty($recent)) return;

        // Create summarizer prompt
        $textToSummarize = "";
        foreach ($recent as $msg) {
            $role = ucfirst($msg['role']);
            $textToSummarize .= "$role: {$msg['content']}\n";
        }

        $summarizerSystemPrompt = "Ringkas percakapan berikut menjadi fakta objektif dan ringkas. " .
            "Jangan gunakan dialog, emosi, gaya karakter, atau sudut pandang personal. " .
            "Hanya simpan informasi yang relevan untuk kelanjutan percakapan. " .
            "Maksimal 3-6 kalimat.";

        $messages = [
            ['role' => 'system', 'content' => $summarizerSystemPrompt],
            ['role' => 'user', 'content' => $textToSummarize]
        ];

        // Call Groq separate from main chat
        $summaryContent = $this->groqApi->chat($messages);
        
        // Update memory
        $this->memory->setSummary($summaryContent);
        
        // Trim recent messages
        // Keep the last 2 interactions (User + Assistant) for context continuity
        // But usually we want the last 2-4 messages.
        // Let's keep the last 4 messages to be safe.
        $this->memory->trimRecentMessages(4);
    }
    
    /**
     * Handle commands
     */
    public function handleCommand($chatId, $command)
    {
        $this->memory->load($chatId);

        $normalizedCommand = strtolower(trim($command));
        if (strpos($normalizedCommand, '/') !== 0) {
            $normalizedCommand = '/' . $normalizedCommand;
        }

        switch ($normalizedCommand) {
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
                $helpText = "Perintah:\n/mulai - Reset percakapan\n/hapus - Hapus memori\n/imgsfw - Gambar SFW Kei\n/imgnsfw - Gambar *uhuk* Kei\n/bantuan - Info ini";
                $this->telegramApi->sendMessage($chatId, $helpText);
                break;
                
            case '/imgsfw':
            case '/imagesfw':
                $this->telegramApi->sendTyping($chatId);
                $imageUrl = $this->imageService->getRandomImage('safebooru');
                
                if ($imageUrl) {
                    $caption = "i... ini gambar diriku\ndan juga... pemilik penghubung ini bilang ia tidak akan bertanggung jawab atas gambar yang kuberikan, kumohon pakai secara bijak";
                    $this->telegramApi->sendPhoto($chatId, $imageUrl, $caption);
                } else {
                    $this->telegramApi->sendMessage($chatId, "Gagal mengambil gambar... Maaf ya.");
                }
                break;
                
            case '/imgnsfw':
            case '/imagensfw':
                $this->telegramApi->sendTyping($chatId);
                $imageUrl = $this->imageService->getRandomImage('danbooru');
                
                if ($imageUrl) {
                    $caption = "A... anu... i... ini (malu-malu memberikan gambarnya kepadamu)\ndan juga... pemilik penghubung ini bilang ia tidak akan bertanggung jawab atas gambar yang kuberikan, kumohon pakai secara bijak";
                    $this->telegramApi->sendPhoto($chatId, $imageUrl, $caption);
                } else {
                    $this->telegramApi->sendMessage($chatId, "Gagal mengambil gambar... Mungkin koneksinya error.");
                }
                break;
                
            default:
                return false;
        }
        
        return true;
    }

    private function isImageRequest($message)
    {
        $pattern = '/\b(gambar|image|foto|pic|generate|buatkan|buat|danbooru|safebooru|booru)\b/i';
        return preg_match($pattern, $message) === 1;
    }
}
