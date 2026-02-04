<?php

return [
    'app' => [
        'name' => 'Kei Bot',
        'timezone' => 'Asia/Jakarta',
        'debug' => true,
        'encryption_key' => getenv('APP_KEY') ?: 'base64:default_insecure_key_change_me_1234567890abcdef',
    ],
    
    'paths' => [
        'memory' => __DIR__ . '/../storage/memory/',
        'logs' => __DIR__ . '/../storage/logs/',
    ],
    
    'facebook' => [
        'page_id' => getenv('FB_PAGE_ID'),
        'access_token' => getenv('FB_ACCESS_TOKEN'),
        'app_secret' => getenv('FB_APP_SECRET'),
        'verify_token' => getenv('FB_VERIFY_TOKEN'),
        'api_version' => 'v18.0',
    ],
    
    'telegram' => [
        'bot_token' => getenv('TELEGRAM_BOT_TOKEN'),
    ],
    
    'groq' => [
        'api_key' => getenv('GROQ_API_KEY'),
        'model' => 'llama-3.1-8b-instant',
        'max_tokens' => 1024,
        'temperature' => 0.7,
    ],
    
    'danbooru' => [
        'tag' => 'kei_(new_body)_(blue_archive)',
        'rating' => 'safe', // Only safe images
    ],
    
    'memory' => [
        'max_messages' => 20, // Simpan 20 pesan terakhir
        'ttl' => 86400, // 24 jam
    ],
    
    'greetings' => [
        [
            'time_range' => [5, 10],
            'text' => "Selamat Pagi! ☀️\n\nSemoga harimu menyenangkan!",
        ],
        [
            'time_range' => [10, 14],
            'text' => "Selamat Siang! 🌤️\n\nTetap semangat!",
        ],
        [
            'time_range' => [14, 18],
            'text' => "Selamat Sore! 🌅\n\nWaktunya istirahat sejenak.",
        ],
        [
            'time_range' => [18, 5],
            'text' => "Selamat Malam! 🌙\n\nSelamat beristirahat.",
        ],
    ],
];
