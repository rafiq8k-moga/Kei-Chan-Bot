<?php

class Logger
{
    private $logPath;
    
    public function __construct($logPath)
    {
        $this->logPath = $logPath;
        
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }
    }
    
    public function info($message, $context = [])
    {
        $this->log('INFO', $message, $context);
    }
    
    public function error($message, $context = [])
    {
        $this->log('ERROR', $message, $context);
    }
    
    public function debug($message, $context = [])
    {
        $this->log('DEBUG', $message, $context);
    }
    
    private function log($level, $message, $context = [])
    {
        $filename = $this->logPath . '/' . date('Y-m-d') . '.log';
        
        $logEntry = sprintf(
            "[%s] [%s] %s %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message,
            !empty($context) ? json_encode($context) : ''
        );
        
        file_put_contents($filename, $logEntry, FILE_APPEND);
    }
}
