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
    ],
    'max_file_sizes_kb' => [
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
        // Default max size if not specified for a MIME type
        'default' => 5120, // 5MB
    ],
];
