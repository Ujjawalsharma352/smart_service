<?php
// API Documentation and Index
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
    
    $api_docs = [
        'name' => 'Smart Service Finder API',
        'version' => '1.0.0',
        'description' => 'RESTful API for Smart Service Finder platform',
        'base_url' => $baseUrl . '/api',
        'endpoints' => [
            'Authentication' => [
                'POST /auth/login' => [
                    'description' => 'User login',
                    'parameters' => [
                        'email' => 'string (required)',
                        'password' => 'string (required)'
                    ],
                    'response' => [
                        'success' => 'boolean',
                        'user' => 'object',
                        'session_id' => 'string'
                    ]
                ],
                'POST /auth/register' => [
                    'description' => 'User registration',
                    'parameters' => [
                        'name' => 'string (required)',
                        'email' => 'string (required)',
                        'password' => 'string (required)',
                        'role' => 'string (required: user|provider)',
                        'phone' => 'string (optional)',
                        'address' => 'string (optional)'
                    ]
                ],
                'POST /auth/logout' => [
                    'description' => 'User logout',
                    'requires_auth' => true
                ]
            ],
            'Services' => [
                'GET /services/list' => [
                    'description' => 'Get list of services',
                    'parameters' => [
                        'search' => 'string (optional)',
                        'category' => 'string (optional)',
                        'limit' => 'integer (optional, default: 50)',
                        'offset' => 'integer (optional, default: 0)'
                    ],
                    'response' => [
                        'services' => 'array of service objects',
                        'pagination' => 'object with total, limit, offset, has_more'
                    ]
                ],
                'GET /services/detail' => [
                    'description' => 'Get service details',
                    'parameters' => [
                        'id' => 'integer (required)'
                    ],
                    'response' => [
                        'service' => 'object with full details',
                        'provider' => 'object',
                        'other_services' => 'array',
                        'recent_reviews' => 'array'
                    ]
                ]
            ],
            'Bookings' => [
                'POST /bookings/create' => [
                    'description' => 'Create new booking',
                    'requires_auth' => true,
                    'requires_role' => 'user',
                    'parameters' => [
                        'service_id' => 'integer (required)',
                        'booking_date' => 'string (required, format: Y-m-d)',
                        'time_slot' => 'string (optional)',
                        'address' => 'string (required)'
                    ]
                ],
                'GET /bookings/list' => [
                    'description' => 'Get user/provider bookings',
                    'requires_auth' => true,
                    'parameters' => [
                        'status' => 'string (optional: pending|accepted|rejected|completed|cancelled)',
                        'limit' => 'integer (optional, default: 50)',
                        'offset' => 'integer (optional, default: 0)'
                    ]
                ],
                'POST /bookings/update' => [
                    'description' => 'Update booking status',
                    'requires_auth' => true,
                    'parameters' => [
                        'booking_id' => 'integer (required)',
                        'action' => 'string (required: accept|reject|complete|cancel)'
                    ]
                ]
            ],
            'Users' => [
                'GET /users/profile' => [
                    'description' => 'Get user profile with statistics',
                    'requires_auth' => true
                ],
                'POST /users/profile' => [
                    'description' => 'Update user profile',
                    'requires_auth' => true,
                    'parameters' => [
                        'name' => 'string (required)',
                        'email' => 'string (required)',
                        'phone' => 'string (optional)',
                        'address' => 'string (optional)'
                    ]
                ]
            ],
            'Admin' => [
                'GET /admin/stats' => [
                    'description' => 'Get system statistics',
                    'requires_auth' => true,
                    'requires_role' => 'admin',
                    'response' => [
                        'statistics' => 'object with all system stats',
                        'recent_activity' => 'array of recent bookings'
                    ]
                ]
            ]
        ],
        'response_format' => [
            'success' => [
                'success' => 'boolean (true)',
                'message' => 'string',
                'data' => 'object/array',
                'timestamp' => 'string (Y-m-d H:i:s)'
            ],
            'error' => [
                'success' => 'boolean (false)',
                'message' => 'string',
                'timestamp' => 'string (Y-m-d H:i:s)'
            ]
        ],
        'authentication' => [
            'method' => 'Session-based (same as web app)',
            'headers' => [
                'Content-Type: application/json',
                'Access-Control-Allow-Origin: *'
            ]
        ],
        'examples' => [
            'login' => [
                'url' => $baseUrl . '/api/auth/login',
                'method' => 'POST',
                'body' => [
                    'email' => 'user@example.com',
                    'password' => 'password123'
                ]
            ],
            'get_services' => [
                'url' => $baseUrl . '/api/services/list?search=plumber&limit=10',
                'method' => 'GET'
            ],
            'create_booking' => [
                'url' => $baseUrl . '/api/bookings/create',
                'method' => 'POST',
                'headers' => [
                    'Content-Type: application/json'
                ],
                'body' => [
                    'service_id' => 1,
                    'booking_date' => '2024-12-25',
                    'address' => '123 Main St'
                ]
            ]
        ]
    ];
    
    echo json_encode($api_docs, JSON_PRETTY_PRINT);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
