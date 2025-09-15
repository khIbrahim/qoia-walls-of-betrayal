<?php

namespace fenomeno\WallsOfBetrayal\Handlers\API;

use fenomeno\WallsOfBetrayal\Main;
use Throwable;

/**
 * Simple HTTP Router for Store Member API
 * Handles routing of API requests to appropriate handlers
 */
final class ApiRouter {

    private StoreMemberApiHandler $memberHandler;
    private StoreApiHandler $storeHandler;

    public function __construct(private readonly Main $main) {
        $this->memberHandler = new StoreMemberApiHandler($main);
        $this->storeHandler = new StoreApiHandler($main);
    }

    /**
     * Route API request to appropriate handler
     */
    public function route(string $method, string $path, array $data = [], array $query = []): array {
        try {
            // Remove leading/trailing slashes and split path
            $path = trim($path, '/');
            $segments = explode('/', $path);

            // Basic path validation
            if (empty($segments[0]) || $segments[0] !== 'api') {
                return $this->errorResponse('Invalid API endpoint', 404);
            }

            // Remove 'api' from segments
            array_shift($segments);

            if (empty($segments)) {
                return $this->errorResponse('No resource specified', 400);
            }

            $resource = $segments[0];

            switch ($resource) {
                case 'stores':
                    return $this->routeStoreEndpoints($method, $segments, $data, $query);
                
                case 'store':
                    return $this->routeStoreMemberEndpoints($method, $segments, $data, $query);
                
                case 'members':
                    return $this->routeGlobalMemberEndpoints($method, $segments, $data, $query);
                
                default:
                    return $this->errorResponse('Unknown resource: ' . $resource, 404);
            }

        } catch (Throwable $e) {
            $this->main->getLogger()->error("API routing error: " . $e->getMessage());
            return $this->errorResponse('Internal server error', 500);
        }
    }

    /**
     * Route store-related endpoints
     * /api/stores/*
     */
    private function routeStoreEndpoints(string $method, array $segments, array $data, array $query): array {
        switch ($method) {
            case 'GET':
                if (count($segments) === 1) {
                    // GET /api/stores
                    return $this->storeHandler->getAllStores($query);
                } elseif (count($segments) === 2) {
                    $storeId = $segments[1];
                    
                    if ($storeId === 'active') {
                        // GET /api/stores/active
                        return $this->storeHandler->getActiveStores();
                    } elseif ($storeId === 'export') {
                        // GET /api/stores/export
                        return $this->storeHandler->exportAllStoresData();
                    } else {
                        // GET /api/stores/{storeId}
                        $includeMembers = !isset($query['exclude_members']) || !$query['exclude_members'];
                        return $this->storeHandler->getStore($storeId, $includeMembers);
                    }
                } elseif (count($segments) === 3) {
                    $storeId = $segments[1];
                    $action = $segments[2];
                    
                    switch ($action) {
                        case 'export':
                            // GET /api/stores/{storeId}/export
                            return $this->storeHandler->exportStoreData($storeId);
                        
                        case 'settings':
                            // GET /api/stores/{storeId}/settings
                            return $this->storeHandler->getStoreSettings($storeId);
                        
                        default:
                            return $this->errorResponse('Unknown store action: ' . $action, 404);
                    }
                }
                break;

            case 'POST':
                if (count($segments) === 1) {
                    // POST /api/stores
                    return $this->storeHandler->createStore($data);
                } elseif (count($segments) === 2 && $segments[1] === 'import') {
                    // POST /api/stores/import
                    return $this->storeHandler->importStoreData($data);
                }
                break;

            case 'PUT':
                if (count($segments) === 2) {
                    // PUT /api/stores/{storeId}
                    $storeId = $segments[1];
                    return $this->storeHandler->updateStore($storeId, $data);
                }
                break;

            case 'PATCH':
                if (count($segments) === 3) {
                    $storeId = $segments[1];
                    $action = $segments[2];
                    
                    switch ($action) {
                        case 'settings':
                            // PATCH /api/stores/{storeId}/settings
                            return $this->storeHandler->updateStoreSettings($storeId, $data);
                        
                        case 'metadata':
                            // PATCH /api/stores/{storeId}/metadata
                            return $this->storeHandler->updateStoreMetadata($storeId, $data);
                        
                        case 'status':
                            // PATCH /api/stores/{storeId}/status
                            return $this->storeHandler->updateStoreStatus($storeId, $data);
                        
                        default:
                            return $this->errorResponse('Unknown patch action: ' . $action, 404);
                    }
                }
                break;

            case 'DELETE':
                if (count($segments) === 2) {
                    // DELETE /api/stores/{storeId}
                    $storeId = $segments[1];
                    return $this->storeHandler->deleteStore($storeId);
                }
                break;
        }

        return $this->errorResponse('Invalid endpoint or method', 405);
    }

    /**
     * Route store member endpoints
     * /api/store/{storeId}/members/*
     */
    private function routeStoreMemberEndpoints(string $method, array $segments, array $data, array $query): array {
        if (count($segments) < 3 || $segments[2] !== 'members') {
            return $this->errorResponse('Invalid store member endpoint', 404);
        }

        $storeId = $segments[1];

        switch ($method) {
            case 'GET':
                if (count($segments) === 3) {
                    // GET /api/store/{storeId}/members
                    return $this->memberHandler->getStoreMembers($storeId, $query);
                } elseif (count($segments) === 4) {
                    // GET /api/store/{storeId}/members/{memberId}
                    $memberId = $segments[3];
                    return $this->memberHandler->getMember($storeId, $memberId);
                } elseif (count($segments) === 5 && $segments[4] === 'statistics') {
                    // GET /api/store/{storeId}/members/{memberId}/statistics
                    return $this->memberHandler->getStoreStatistics($storeId);
                }
                break;

            case 'POST':
                if (count($segments) === 3) {
                    // POST /api/store/{storeId}/members
                    return $this->memberHandler->createMember($storeId, $data);
                } elseif (count($segments) === 5) {
                    $memberId = $segments[3];
                    $action = $segments[4];
                    
                    switch ($action) {
                        case 'login':
                            // POST /api/store/{storeId}/members/{memberId}/login
                            return $this->memberHandler->recordLogin($storeId, $memberId);
                        
                        case 'permissions':
                            // POST /api/store/{storeId}/members/{memberId}/permissions
                            return $this->memberHandler->grantPermission($storeId, $memberId, $data);
                        
                        default:
                            return $this->errorResponse('Unknown member action: ' . $action, 404);
                    }
                }
                break;

            case 'PUT':
                if (count($segments) === 4) {
                    // PUT /api/store/{storeId}/members/{memberId}
                    $memberId = $segments[3];
                    return $this->memberHandler->updateMember($storeId, $memberId, $data);
                }
                break;

            case 'PATCH':
                if (count($segments) === 5) {
                    $memberId = $segments[3];
                    $action = $segments[4];
                    
                    switch ($action) {
                        case 'status':
                            // PATCH /api/store/{storeId}/members/{memberId}/status
                            return $this->memberHandler->changeMemberStatus($storeId, $memberId, $data);
                        
                        case 'role':
                            // PATCH /api/store/{storeId}/members/{memberId}/role
                            return $this->memberHandler->changeMemberRole($storeId, $memberId, $data);
                        
                        default:
                            return $this->errorResponse('Unknown patch action: ' . $action, 404);
                    }
                }
                break;

            case 'DELETE':
                if (count($segments) === 4) {
                    // DELETE /api/store/{storeId}/members/{memberId}
                    $memberId = $segments[3];
                    return $this->memberHandler->deleteMember($storeId, $memberId);
                } elseif (count($segments) === 6 && $segments[4] === 'permissions') {
                    // DELETE /api/store/{storeId}/members/{memberId}/permissions/{permission}
                    $memberId = $segments[3];
                    $permission = $segments[5];
                    return $this->memberHandler->revokePermission($storeId, $memberId, $permission);
                }
                break;
        }

        return $this->errorResponse('Invalid member endpoint or method', 405);
    }

    /**
     * Route global member endpoints
     * /api/members/*
     */
    private function routeGlobalMemberEndpoints(string $method, array $segments, array $data, array $query): array {
        switch ($method) {
            case 'GET':
                if (count($segments) === 2 && $segments[1] === 'search') {
                    // GET /api/members/search
                    return $this->memberHandler->searchMembers($query);
                }
                break;
        }

        return $this->errorResponse('Invalid global member endpoint', 404);
    }

    /**
     * Get HTTP method from request
     */
    public function getRequestMethod(): string {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Get request path
     */
    public function getRequestPath(): string {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query string
        if (($pos = strpos($path, '?')) !== false) {
            $path = substr($path, 0, $pos);
        }
        
        return $path;
    }

    /**
     * Get request data based on method
     */
    public function getRequestData(): array {
        $method = $this->getRequestMethod();
        
        switch ($method) {
            case 'POST':
            case 'PUT':
            case 'PATCH':
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                return is_array($data) ? $data : [];
            
            case 'GET':
            case 'DELETE':
            default:
                return [];
        }
    }

    /**
     * Get query parameters
     */
    public function getQueryParams(): array {
        return $_GET ?? [];
    }

    /**
     * Handle API request
     */
    public function handleRequest(): array {
        $method = $this->getRequestMethod();
        $path = $this->getRequestPath();
        $data = $this->getRequestData();
        $query = $this->getQueryParams();

        return $this->route($method, $path, $data, $query);
    }

    /**
     * Send JSON response
     */
    public function sendResponse(array $response): void {
        $statusCode = $response['status_code'] ?? 200;
        
        http_response_code($statusCode);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        echo json_encode($response, JSON_THROW_ON_ERROR);
    }

    /**
     * Handle OPTIONS request for CORS
     */
    public function handleOptions(): void {
        http_response_code(200);
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Max-Age: 86400');
    }

    private function errorResponse(string $message, int $statusCode = 400): array {
        return [
            'success' => false,
            'status_code' => $statusCode,
            'error' => [
                'message' => $message,
                'code' => $statusCode
            ],
            'timestamp' => time()
        ];
    }
}