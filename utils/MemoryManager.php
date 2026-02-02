<?php

class MemoryManager
{
    private $memoryPath;
    private $maxRecentMessages; // Configurable limit for recent messages
    private $ttl;
    
    // Internal state
    private $data = [];
    private $currentUserId = null;

    public function __construct($config)
    {
        $this->memoryPath = $config['paths']['memory'];
        // Use a default if not set, though ideally it should be in config
        $this->maxRecentMessages = 10; 
        $this->ttl = $config['memory']['ttl'];
        
        if (!is_dir($this->memoryPath)) {
            mkdir($this->memoryPath, 0755, true);
        }
    }
    
    /**
     * Load memory for a user.
     * Initializes a default structure if none exists or if expired.
     */
    public function load($userId)
    {
        $this->currentUserId = $userId;
        $file = $this->getFilePath($userId);
        
        $this->data = [
            'user_id' => $userId,
            'summary' => null, // { role: system, content: ... }
            'recent_messages' => [],
            'counter' => 0,
            'last_updated' => time(),
        ];
        
        if (file_exists($file)) {
            $loadedEntry = json_decode(file_get_contents($file), true);
            
            // Check TTL
            if (isset($loadedEntry['last_updated'])) {
                $age = time() - $loadedEntry['last_updated'];
                if ($age <= $this->ttl) {
                    // Merge loaded data with defaults to ensure structure exists
                    $this->data = array_merge($this->data, $loadedEntry);
                } else {
                    // Expired - start fresh, maybe keep user_id
                }
            }
        }
        
        return $this->data;
    }
    
    /**
     * Save current state to disk
     */
    public function save()
    {
        if (!$this->currentUserId) {
            throw new Exception("No user loaded in MemoryManager");
        }
        
        $file = $this->getFilePath($this->currentUserId);
        $this->data['last_updated'] = time();
        
        file_put_contents($file, json_encode($this->data, JSON_PRETTY_PRINT));
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
        // Reset internal state if it matches
        if ($this->currentUserId == $userId) {
            $this->data = [
                'user_id' => $userId,
                'summary' => null,
                'recent_messages' => [],
                'counter' => 0,
                'last_updated' => time(),
            ];
        }
    }

    // --- New Accessor Methods ---

    public function getSummary()
    {
        return $this->data['summary'];
    }

    public function setSummary($content)
    {
        $this->data['summary'] = [
            'role' => 'system',
            'content' => $content
        ];
    }

    public function getRecentMessages()
    {
        return $this->data['recent_messages'];
    }

    public function addRecentMessage($role, $content)
    {
        $this->data['recent_messages'][] = [
            'role' => $role,
            'content' => $content
        ];
    }
    
    /**
     * Keep only the last N messages in recent_messages
     */
    public function trimRecentMessages($count)
    {
        if (count($this->data['recent_messages']) > $count) {
            $this->data['recent_messages'] = array_slice($this->data['recent_messages'], -$count);
        }
    }

    public function getCounter()
    {
        return $this->data['counter'] ?? 0;
    }

    public function incrementCounter()
    {
        if (!isset($this->data['counter'])) {
            $this->data['counter'] = 0;
        }
        $this->data['counter']++;
        return $this->data['counter'];
    }

    // --- Private ---
    
    private function getFilePath($userId)
    {
        return $this->memoryPath . 'user_' . $userId . '.json';
    }
    
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
