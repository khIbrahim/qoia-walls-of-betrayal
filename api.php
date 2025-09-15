<?php

/**
 * Store Members API Endpoint
 * 
 * This file serves as a web interface for the store members API.
 * It should be placed in a web-accessible directory and configured
 * to work with your Laravel or other web application.
 * 
 * Example usage:
 * GET  /api.php/stores                           - Get all stores
 * POST /api.php/stores                           - Create new store
 * GET  /api.php/stores/{storeId}                 - Get specific store
 * PUT  /api.php/stores/{storeId}                 - Update store
 * DELETE /api.php/stores/{storeId}               - Delete store
 * 
 * GET  /api.php/store/{storeId}/members          - Get store members
 * POST /api.php/store/{storeId}/members          - Create new member
 * GET  /api.php/store/{storeId}/members/{id}     - Get specific member
 * PUT  /api.php/store/{storeId}/members/{id}     - Update member
 * DELETE /api.php/store/{storeId}/members/{id}   - Delete member
 * 
 * PATCH /api.php/store/{storeId}/members/{id}/status - Change member status
 * PATCH /api.php/store/{storeId}/members/{id}/role   - Change member role
 * 
 * For Laravel integration, you would create routes in your web.php or api.php:
 * 
 * Route::prefix('api/store-members')->group(function () {
 *     Route::get('/stores', [StoreMemberController::class, 'getAllStores']);
 *     Route::post('/stores', [StoreMemberController::class, 'createStore']);
 *     Route::get('/stores/{storeId}', [StoreMemberController::class, 'getStore']);
 *     Route::put('/stores/{storeId}', [StoreMemberController::class, 'updateStore']);
 *     Route::delete('/stores/{storeId}', [StoreMemberController::class, 'deleteStore']);
 *     
 *     Route::get('/store/{storeId}/members', [StoreMemberController::class, 'getStoreMembers']);
 *     Route::post('/store/{storeId}/members', [StoreMemberController::class, 'createMember']);
 *     Route::get('/store/{storeId}/members/{memberId}', [StoreMemberController::class, 'getMember']);
 *     Route::put('/store/{storeId}/members/{memberId}', [StoreMemberController::class, 'updateMember']);
 *     Route::delete('/store/{storeId}/members/{memberId}', [StoreMemberController::class, 'deleteMember']);
 *     
 *     Route::patch('/store/{storeId}/members/{memberId}/status', [StoreMemberController::class, 'changeMemberStatus']);
 *     Route::patch('/store/{storeId}/members/{memberId}/role', [StoreMemberController::class, 'changeMemberRole']);
 * });
 */

// This is a standalone API endpoint for direct use
// Uncomment the following lines if you want to use this as a standalone API

/*
require_once __DIR__ . '/vendor/autoload.php';

use fenomeno\WallsOfBetrayal\Main;
use fenomeno\WallsOfBetrayal\Handlers\API\ApiRouter;

try {
    // Initialize the plugin (you may need to adapt this for your setup)
    $main = Main::getInstance();
    $router = new ApiRouter($main);
    
    // Handle OPTIONS request for CORS
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        $router->handleOptions();
        exit;
    }
    
    // Handle the API request
    $response = $router->handleRequest();
    $router->sendResponse($response);
    
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'status_code' => 500,
        'error' => [
            'message' => 'Internal server error',
            'code' => 500
        ],
        'timestamp' => time()
    ]);
}
*/

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Members API Documentation</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1, h2, h3 {
            color: #2c3e50;
        }
        h1 {
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        .endpoint {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 15px 0;
            border-radius: 0 5px 5px 0;
        }
        .method {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
            margin-right: 10px;
        }
        .get { background-color: #28a745; color: white; }
        .post { background-color: #007bff; color: white; }
        .put { background-color: #ffc107; color: black; }
        .patch { background-color: #fd7e14; color: white; }
        .delete { background-color: #dc3545; color: white; }
        .code {
            background-color: #f1f3f4;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
        }
        .response-example {
            background-color: #e8f5e8;
            border: 1px solid #4caf50;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .error-example {
            background-color: #ffe8e8;
            border: 1px solid #f44336;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
        }
        .feature {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin: 15px 0;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏪 Store Members API Documentation</h1>
        <p>Complete API system for managing store members in restaurant POS ecosystem</p>

        <div class="feature">
            <h3>✨ Key Features</h3>
            <ul>
                <li><strong>Complete CRUD Operations</strong> - Create, read, update, delete stores and members</li>
                <li><strong>Role-Based Access Control</strong> - Manager, Supervisor, Cashier, Employee roles</li>
                <li><strong>Status Management</strong> - Active, Inactive, Suspended, Terminated statuses</li>
                <li><strong>Permission System</strong> - Granular permissions for each member</li>
                <li><strong>Laravel-Friendly</strong> - Ready for integration with Laravel applications</li>
                <li><strong>Performance Optimized</strong> - Caching, indexing, and efficient queries</li>
                <li><strong>Comprehensive Validation</strong> - Input validation and security</li>
                <li><strong>Audit Logging</strong> - Complete audit trail of all actions</li>
            </ul>
        </div>

        <h2>🏬 Store Management Endpoints</h2>

        <div class="endpoint">
            <span class="method get">GET</span><strong>/api/stores</strong>
            <p>Get all stores with optional filtering and pagination</p>
            <div class="code">
Query Parameters:
- page (int): Page number (default: 1)
- per_page (int): Items per page (default: 25, max: 100)
- active_only (bool): Only active stores
- search (string): Search in name, description, address
- exclude_members (bool): Exclude member details</div>
        </div>

        <div class="endpoint">
            <span class="method post">POST</span><strong>/api/stores</strong>
            <p>Create a new store</p>
            <div class="code">
{
    "name": "Downtown Restaurant",
    "description": "Main location in downtown area",
    "address": "123 Main St, City, State 12345",
    "phone": "+1-555-123-4567",
    "email": "downtown@restaurant.com",
    "settings": {
        "timezone": "America/New_York",
        "currency": "USD"
    },
    "metadata": {
        "capacity": 100,
        "established": 2020
    }
}</div>
        </div>

        <div class="endpoint">
            <span class="method get">GET</span><strong>/api/stores/{storeId}</strong>
            <p>Get specific store details</p>
        </div>

        <div class="endpoint">
            <span class="method put">PUT</span><strong>/api/stores/{storeId}</strong>
            <p>Update store information</p>
        </div>

        <div class="endpoint">
            <span class="method delete">DELETE</span><strong>/api/stores/{storeId}</strong>
            <p>Delete store and all its members</p>
        </div>

        <h2>👥 Store Member Management Endpoints</h2>

        <div class="endpoint">
            <span class="method get">GET</span><strong>/api/store/{storeId}/members</strong>
            <p>Get all members for a specific store</p>
            <div class="code">
Query Parameters:
- page (int): Page number
- per_page (int): Items per page
- role (string): Filter by role (manager, supervisor, cashier, employee)
- status (string): Filter by status (active, inactive, suspended, terminated)
- search (string): Search in name or email</div>
        </div>

        <div class="endpoint">
            <span class="method post">POST</span><strong>/api/store/{storeId}/members</strong>
            <p>Create a new store member</p>
            <div class="code">
{
    "firstName": "John",
    "lastName": "Doe",
    "email": "john.doe@restaurant.com",
    "phone": "+1-555-987-6543",
    "role": "cashier",
    "status": "active",
    "hourlyWage": 15.50,
    "permissions": ["view_orders", "process_payments"],
    "workSchedule": {
        "monday": [{"start": "09:00", "end": "17:00"}],
        "tuesday": [{"start": "09:00", "end": "17:00"}]
    },
    "metadata": {
        "hireDate": "2024-01-15",
        "department": "front-of-house"
    }
}</div>
        </div>

        <div class="endpoint">
            <span class="method get">GET</span><strong>/api/store/{storeId}/members/{memberId}</strong>
            <p>Get specific member details</p>
        </div>

        <div class="endpoint">
            <span class="method put">PUT</span><strong>/api/store/{storeId}/members/{memberId}</strong>
            <p>Update member information</p>
        </div>

        <div class="endpoint">
            <span class="method delete">DELETE</span><strong>/api/store/{storeId}/members/{memberId}</strong>
            <p>Delete member from store</p>
        </div>

        <h2>🔧 Member Management Actions</h2>

        <div class="endpoint">
            <span class="method patch">PATCH</span><strong>/api/store/{storeId}/members/{memberId}/status</strong>
            <p>Change member status</p>
            <div class="code">
{
    "status": "suspended"
}

Valid statuses: active, inactive, suspended, terminated</div>
        </div>

        <div class="endpoint">
            <span class="method patch">PATCH</span><strong>/api/store/{storeId}/members/{memberId}/role</strong>
            <p>Change member role</p>
            <div class="code">
{
    "role": "supervisor"
}

Valid roles: manager, supervisor, cashier, employee</div>
        </div>

        <div class="endpoint">
            <span class="method post">POST</span><strong>/api/store/{storeId}/members/{memberId}/permissions</strong>
            <p>Grant permission to member</p>
            <div class="code">
{
    "permission": "manage_inventory"
}</div>
        </div>

        <div class="endpoint">
            <span class="method delete">DELETE</span><strong>/api/store/{storeId}/members/{memberId}/permissions/{permission}</strong>
            <p>Revoke permission from member</p>
        </div>

        <div class="endpoint">
            <span class="method post">POST</span><strong>/api/store/{storeId}/members/{memberId}/login</strong>
            <p>Record member login</p>
        </div>

        <h2>📊 Analytics & Search</h2>

        <div class="endpoint">
            <span class="method get">GET</span><strong>/api/store/{storeId}/statistics</strong>
            <p>Get store statistics and analytics</p>
        </div>

        <div class="endpoint">
            <span class="method get">GET</span><strong>/api/members/search</strong>
            <p>Search members across all stores</p>
            <div class="code">
Query Parameters:
- firstName (string): Search by first name
- lastName (string): Search by last name
- email (string): Search by email
- role (string): Filter by role
- status (string): Filter by status</div>
        </div>

        <h2>📱 Response Format</h2>

        <h3>Success Response</h3>
        <div class="response-example">
            <div class="code">
{
    "success": true,
    "status_code": 200,
    "data": {
        "member": {
            "id": "member_507f1f77bcf86cd799439011_1234",
            "firstName": "John",
            "lastName": "Doe",
            "fullName": "John Doe",
            "email": "john.doe@restaurant.com",
            "phone": "+1-555-987-6543",
            "role": "cashier",
            "status": "active",
            "permissions": ["view_orders", "process_payments"],
            "hourlyWage": 15.50,
            "createdAt": 1703980800,
            "updatedAt": 1703980800,
            "lastLoginAt": 1703980800
        }
    },
    "timestamp": 1703980800
}</div>
        </div>

        <h3>Error Response</h3>
        <div class="error-example">
            <div class="code">
{
    "success": false,
    "status_code": 400,
    "error": {
        "message": "Validation failed: Email is required",
        "code": 400
    },
    "timestamp": 1703980800
}</div>
        </div>

        <h2>🔐 Permission System</h2>

        <h3>Available Permissions</h3>
        <div class="code">
Manager:
- view_all_orders, manage_inventory, manage_staff
- view_reports, manage_settings, process_refunds
- override_prices, access_admin_panel

Supervisor:
- view_orders, manage_inventory, view_basic_reports
- process_refunds, override_prices

Cashier:
- view_orders, process_payments, basic_inventory_view

Employee:
- view_orders, basic_operations</div>

        <h2>🔄 Status Transitions</h2>

        <div class="code">
Active → Inactive, Suspended, Terminated
Inactive → Active, Suspended, Terminated
Suspended → Active, Inactive, Terminated
Terminated → (No transitions - final state)</div>

        <div class="warning">
            <h3>⚠️ Integration Notes</h3>
            <ul>
                <li>All timestamps are Unix timestamps (seconds since epoch)</li>
                <li>IDs are auto-generated and should not be manually set</li>
                <li>Email addresses must be unique across all stores</li>
                <li>Role changes require appropriate permissions</li>
                <li>Terminated members cannot be reactivated</li>
                <li>All API responses include CORS headers for web integration</li>
            </ul>
        </div>

        <h2>🚀 Laravel Integration Example</h2>

        <div class="code">
// StoreMemberController.php
class StoreMemberController extends Controller
{
    private $apiHandler;
    
    public function __construct()
    {
        $this->apiHandler = new StoreMemberApiHandler(Main::getInstance());
    }
    
    public function createMember(Request $request, $storeId)
    {
        $response = $this->apiHandler->createMember($storeId, $request->all());
        return response()->json($response, $response['status_code']);
    }
    
    public function getStoreMembers(Request $request, $storeId)
    {
        $response = $this->apiHandler->getStoreMembers($storeId, $request->all());
        return response()->json($response, $response['status_code']);
    }
    
    // ... other methods
}</div>

        <div class="feature">
            <h3>🎯 Next Steps</h3>
            <ol>
                <li>Set up your database with the provided SQL schema</li>
                <li>Configure your web server to route API requests</li>
                <li>Integrate with your Laravel application using the provided examples</li>
                <li>Customize permissions and roles based on your business needs</li>
                <li>Set up monitoring and logging for production use</li>
            </ol>
        </div>
    </div>
</body>
</html>