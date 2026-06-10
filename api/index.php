<?php
/**
 * Collexa Past Papers API - Main Router
 * Routes all API requests to appropriate endpoints
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/jwt.php';
require_once __DIR__ . '/helpers/cache.php';
require_once __DIR__ . '/middleware/auth.php';

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];

// Parse the path relative to /api/
// Auto-detect base path from script location
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
$basePath = $scriptPath; // e.g., /papers/api or /pp/api
$path = parse_url($requestUri, PHP_URL_PATH);
$path = str_replace($basePath, '', $path);
$path = trim($path, '/');
$segments = $path ? explode('/', $path) : [];

// ===========================================
// Route Definitions
// ===========================================

try {
    // Empty path - API info
    if (empty($segments) || (count($segments) === 1 && $segments[0] === '')) {
        ApiResponse::success([
            'name' => 'Collexa Past Papers API',
            'version' => '1.0.0',
            'status' => 'active',
            'documentation' => SITE_URL . '/api/docs'
        ]);
    }

    $resource = $segments[0] ?? '';
    $action = $segments[1] ?? '';
    $id = $segments[2] ?? ($segments[1] ?? null);

    // ===========================================
    // AUTH ROUTES
    // ===========================================
    if ($resource === 'auth') {
        switch ($action) {
            case 'login':
                if ($method !== 'POST')
                    ApiResponse::error('METHOD_NOT_ALLOWED', 'Use POST method', null, 405);
                require __DIR__ . '/endpoints/auth/login.php';
                break;

            case 'register':
                if ($method !== 'POST')
                    ApiResponse::error('METHOD_NOT_ALLOWED', 'Use POST method', null, 405);
                require __DIR__ . '/endpoints/auth/register.php';
                break;

            case 'verify':
                if ($method !== 'POST')
                    ApiResponse::error('METHOD_NOT_ALLOWED', 'Use POST method', null, 405);
                require __DIR__ . '/endpoints/auth/verify.php';
                break;

            case 'forgot-password':
                if ($method !== 'POST')
                    ApiResponse::error('METHOD_NOT_ALLOWED', 'Use POST method', null, 405);
                require __DIR__ . '/endpoints/auth/forgot_password.php';
                break;

            case 'reset-password':
                if ($method !== 'POST')
                    ApiResponse::error('METHOD_NOT_ALLOWED', 'Use POST method', null, 405);
                require __DIR__ . '/endpoints/auth/reset_password.php';
                break;

            case 'refresh':
                if ($method !== 'POST')
                    ApiResponse::error('METHOD_NOT_ALLOWED', 'Use POST method', null, 405);
                require __DIR__ . '/endpoints/auth/refresh.php';
                break;

            case 'me':
                if ($method !== 'GET')
                    ApiResponse::error('METHOD_NOT_ALLOWED', 'Use GET method', null, 405);
                require __DIR__ . '/endpoints/auth/me.php';
                break;

            case 'firebase-login':
                if ($method !== 'POST')
                    ApiResponse::error('METHOD_NOT_ALLOWED', 'Use POST method', null, 405);
                require __DIR__ . '/endpoints/auth/firebase_login.php';
                break;

            default:
                ApiResponse::error('NOT_FOUND', 'Auth endpoint not found', null, 404);
        }
    }

    // ===========================================
    // PAPERS ROUTES
    // ===========================================
    elseif ($resource === 'papers') {
        if (empty($action)) {
            // GET /papers - List papers
            if ($method === 'GET') {
                require __DIR__ . '/endpoints/papers/list.php';
            }
            // POST /papers - Upload paper
            elseif ($method === 'POST') {
                require __DIR__ . '/endpoints/papers/upload.php';
            } else {
                ApiResponse::error('METHOD_NOT_ALLOWED', 'Method not allowed', null, 405);
            }
        } elseif ($action === 'trending') {
            require __DIR__ . '/endpoints/papers/trending.php';
        } elseif ($action === 'recent') {
            require __DIR__ . '/endpoints/papers/recent.php';
        } elseif (is_numeric($action)) {
            $paperId = (int) $action;
            $subAction = $segments[2] ?? '';

            if ($subAction === 'download') {
                require __DIR__ . '/endpoints/papers/download.php';
            } elseif ($subAction === 'view') {
                if ($method === 'POST') {
                    require __DIR__ . '/endpoints/papers/view.php';
                }
            } elseif (empty($subAction)) {
                // GET /papers/{id} - Single paper
                if ($method === 'GET') {
                    require __DIR__ . '/endpoints/papers/show.php';
                }
            } else {
                ApiResponse::error('NOT_FOUND', 'Endpoint not found', null, 404);
            }
        } else {
            ApiResponse::error('NOT_FOUND', 'Papers endpoint not found', null, 404);
        }
    }

    // ===========================================
    // FAVORITES ROUTES
    // ===========================================
    elseif ($resource === 'favorites') {
        if (empty($action)) {
            // GET /favorites - List favorites
            if ($method === 'GET') {
                require __DIR__ . '/endpoints/favorites/list.php';
            } else {
                ApiResponse::error('METHOD_NOT_ALLOWED', 'Method not allowed', null, 405);
            }
        } elseif (is_numeric($action)) {
            $paperId = (int) $action;
            $subAction = $segments[2] ?? '';

            if ($subAction === 'check') {
                require __DIR__ . '/endpoints/favorites/check.php';
            } elseif ($method === 'POST') {
                require __DIR__ . '/endpoints/favorites/add.php';
            } elseif ($method === 'DELETE') {
                require __DIR__ . '/endpoints/favorites/remove.php';
            } else {
                ApiResponse::error('METHOD_NOT_ALLOWED', 'Method not allowed', null, 405);
            }
        } elseif ($action === 'check' && isset($segments[2]) && is_numeric($segments[2])) {
            $paperId = (int) $segments[2];
            require __DIR__ . '/endpoints/favorites/check.php';
        } else {
            ApiResponse::error('NOT_FOUND', 'Favorites endpoint not found', null, 404);
        }
    }

    // ===========================================
    // REQUESTS ROUTES (Community Request Board)
    // ===========================================
    elseif ($resource === 'requests') {
        if (empty($action)) {
            if ($method === 'GET') {
                require __DIR__ . '/endpoints/requests/list.php';
            } elseif ($method === 'POST') {
                require __DIR__ . '/endpoints/requests/create.php';
            } else {
                ApiResponse::error('METHOD_NOT_ALLOWED', 'Method not allowed', null, 405);
            }
        } elseif (is_numeric($action)) {
            $requestId = (int) $action;
            $subAction = $segments[2] ?? '';

            if ($subAction === 'vote') {
                if ($method === 'POST' || $method === 'DELETE') {
                    require __DIR__ . '/endpoints/requests/vote.php';
                }
            } elseif ($subAction === 'comments') {
                if ($method === 'GET') {
                    require __DIR__ . '/endpoints/requests/comments.php';
                } elseif ($method === 'POST') {
                    require __DIR__ . '/endpoints/requests/add_comment.php';
                }
            } elseif ($subAction === 'close') {
                if ($method === 'PUT') {
                    require __DIR__ . '/endpoints/requests/close.php';
                }
            } elseif ($subAction === 'fulfill') {
                if ($method === 'PUT') {
                    require __DIR__ . '/endpoints/requests/fulfill.php';
                }
            } elseif (empty($subAction)) {
                if ($method === 'GET') {
                    require __DIR__ . '/endpoints/requests/show.php';
                }
            } else {
                ApiResponse::error('NOT_FOUND', 'Endpoint not found', null, 404);
            }
        } else {
            ApiResponse::error('NOT_FOUND', 'Requests endpoint not found', null, 404);
        }
    }

    // ===========================================
    // USER ROUTES
    // ===========================================
    elseif ($resource === 'user') {
        if ($action === 'profile') {
            if ($method === 'GET') {
                require __DIR__ . '/endpoints/user/profile.php';
            } elseif ($method === 'PUT') {
                require __DIR__ . '/endpoints/user/update.php';
            }
        } elseif ($action === 'avatar') {
            if ($method === 'POST') {
                require __DIR__ . '/endpoints/user/avatar.php';
            }
        } elseif ($action === 'stats') {
            require __DIR__ . '/endpoints/user/stats.php';
        } elseif ($action === 'uploads') {
            require __DIR__ . '/endpoints/user/uploads.php';
        } else {
            ApiResponse::error('NOT_FOUND', 'User endpoint not found', null, 404);
        }
    }

    // ===========================================
    // NOTIFICATIONS ROUTES
    // ===========================================
    elseif ($resource === 'notifications') {
        if (empty($action)) {
            if ($method === 'GET') {
                require __DIR__ . '/endpoints/notifications/list.php';
            }
        } elseif ($action === 'unread') {
            require __DIR__ . '/endpoints/notifications/unread.php';
        } elseif ($action === 'read-all') {
            if ($method === 'PUT') {
                require __DIR__ . '/endpoints/notifications/read_all.php';
            }
        } elseif (is_numeric($action)) {
            $notificationId = (int) $action;
            $subAction = $segments[2] ?? '';

            if ($subAction === 'read' && $method === 'PUT') {
                require __DIR__ . '/endpoints/notifications/read.php';
            } elseif ($method === 'DELETE') {
                require __DIR__ . '/endpoints/notifications/delete.php';
            }
        } else {
            ApiResponse::error('NOT_FOUND', 'Notifications endpoint not found', null, 404);
        }
    }

    // ===========================================
    // ACTIVITY ROUTES
    // ===========================================
    elseif ($resource === 'activity') {
        if (empty($action) || $action === '') {
            require __DIR__ . '/endpoints/activity/list.php';
        } elseif ($action === 'stats') {
            require __DIR__ . '/endpoints/activity/stats.php';
        } else {
            ApiResponse::error('NOT_FOUND', 'Activity endpoint not found', null, 404);
        }
    }

    // ===========================================
    // META ROUTES (Campuses, Departments, etc.)
    // ===========================================
    elseif ($resource === 'meta') {
        if ($action === 'campuses') {
            require __DIR__ . '/endpoints/meta/campuses.php';
        } elseif ($action === 'departments') {
            $campus = $segments[2] ?? $_GET['campus'] ?? '';
            require __DIR__ . '/endpoints/meta/departments.php';
        } elseif ($action === 'semesters') {
            require __DIR__ . '/endpoints/meta/semesters.php';
        } elseif ($action === 'exam-types') {
            require __DIR__ . '/endpoints/meta/exam_types.php';
        } else {
            ApiResponse::error('NOT_FOUND', 'Meta endpoint not found', null, 404);
        }
    }

    // ===========================================
    // DOCUMENTATION ROUTE
    // ===========================================
    elseif ($resource === 'docs') {
        header('Content-Type: text/html; charset=utf-8');
        require __DIR__ . '/docs/index.html';
        exit;
    }

    // ===========================================
    // NOT FOUND
    // ===========================================
    else {
        ApiResponse::error('NOT_FOUND', 'API endpoint not found', null, 404);
    }

} catch (PDOException $e) {
    error_log("API Database Error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', 'Database error occurred', null, 500);
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    ApiResponse::error('SERVER_ERROR', $e->getMessage(), null, 500);
}
