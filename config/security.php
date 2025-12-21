<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Production Security Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains security settings specifically for
    | production environments. These settings help protect against common
    | security vulnerabilities and ensure data protection.
    |
    */

    'force_https' => env('FORCE_HTTPS', true),
    
    'secure_headers' => env('SECURE_HEADERS', true),
    
    'content_security_policy' => [
        'default-src' => "'self'",
        'script-src' => "'self' 'unsafe-inline' https://cdn.jsdelivr.net",
        'style-src' => "'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
        'font-src' => "'self' https://fonts.gstatic.com",
        'img-src' => "'self' data: https:",
        'connect-src' => "'self'",
    ],
    
    'hsts_max_age' => 31536000, // 1 year
    
    'session_security' => [
        'secure' => true,
        'http_only' => true,
        'same_site' => 'strict',
    ],
    
    'rate_limiting' => [
        'login_attempts' => 5,
        'api_requests' => 60,
        'dashboard_requests' => 120,
    ],
    
    'audit_logging' => [
        'enabled' => true,
        'sensitive_fields' => [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'api_key',
        ],
    ],
];