<?php

class MemoryManager
{
    private $memoryPath;
    private $maxMessages;
    private $ttl;
    
    public function __construct($config)
    {
        $this->memoryPath = $config['paths']['memory'];
        $this->maxMessages = $config['memory']['max_messages'];
        $this->ttl = $config['memory']['ttl'];
        
        if (!is_dir($this->memoryPath)) {
            mkdir($this->memoryPath, 0755, true);
        }
    }
    
    /**
     * Load conversation history for user
     */
    public function load($userId)
    {
        $file = $this->getFilePath($userId);
        
        if (!file_exists($file)) {
            return [];
        }
        
        $data = json_decode(file_get_contents($file), true);
        
        // Check TTL
        if (isset($data['last_updated'])) {
            $age = time() - $data['last_updated'];
            if ($age > $this->ttl) {
                // Memory expired, clear it
                $this->clear($userId);
                return [];
            }
        }
        
        return $data['messages'] ?? [];
    }
    
    /**
     * Save conversation history
     */
    public function save($userId, $messages)
    {
        $file = $this->getFilePath($userId);
        
        // Keep only last N messages
        if (count($messages) > $this->maxMessages) {
            $messages = array_slice($messages, -$this->maxMessages);
        }
        
        $data = [
            'user_id' => $userId,
            'messages' => $messages,
            'last_updated' => time(),
        ];
        
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    /**
     * Add message to history
     */
    public function add($userId, $role, $content)
    {
        $messages = $this->load($userId);
        
        $messages[] = [
            'role' => $role,
            'content' => $content,
            'timestamp' => time(),
        ];
        
        $this->save($userId, $messages);
    }
    
    /**
     * Clear user memory
     */
    public function clear($userId)
    {
        $file = $this->getFilePath($userId);
        
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    /**
     * Get file path for user
     */
    private function getFilePath($userId)
    {
        return $this->memoryPath . 'user_' . $userId . '.json';
    }
    
    /**
     * Cleanup old memories (run periodically)
     */
    public function cleanup()
    {
        $files = glob($this->memoryPath . 'user_*.json');
        $deleted = 0;
        
        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            
            if (isset($data['last_updated'])) {
                $age = time() - $data['last_updated'];
                
                if ($age > $this->ttl) {
                    unlink($file);
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
}
