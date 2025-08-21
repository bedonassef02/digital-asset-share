<?php

return [
    'allowed_file_types' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'audio/mpeg',
        'audio/wav',
        'text/plain',
        'application/json',
        'text/csv',
        'text/html',
        'text/css',
        'application/xml',
        'application/zip',
        'application/x-rar-compressed',
        'application/x-tar',
        'application/gzip',
        'application/x-7z-compressed',
    ],
    'max_file_sizes_kb' => [
        'text/plain' => 1024, // 1MB
        'text/csv' => 1024, // 1MB
        'text/html' => 1024, // 1MB
        'text/css' => 1024, // 1MB
        'application/json' => 5120, // 5MB
        'application/xml' => 5120, // 5MB
        'image/jpeg' => 5120, // 5MB
        'image/png' => 5120,   // 5MB
        'image/gif' => 2048,   // 2MB
        'application/pdf' => 10240, // 10MB
        'application/msword' => 10240, // 10MB
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 10240, // 10MB
        'application/vnd.ms-excel' => 15360, // 15MB
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 15360, // 15MB
        'application/vnd.ms-powerpoint' => 20480, // 20MB
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 20480, // 20MB
        'audio/mpeg' => 10240, // 10MB
        'audio/wav' => 20480, // 20MB
        'application/zip' => 51200, // 50MB - for general zip files
        'application/x-rar-compressed' => 51200, // 50MB
        'application/x-tar' => 51200, // 50MB
        'application/gzip' => 51200, // 50MB
        'application/x-7z-compressed' => 51200, // 50MB
        // Default max size if not specified for a MIME type
        'default' => 5120, // 5MB
    ],
];
