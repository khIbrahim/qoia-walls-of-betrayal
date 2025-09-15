# 🏪 Store Members API - Restaurant POS Ecosystem

A comprehensive, production-ready API system for managing store members in restaurant Point of Sale (POS) ecosystems. Built with PHP 8.1+, designed for scalability, security, and Laravel integration.

## ✨ Features

### 🏗️ **Architecture & Design**
- **Modular Architecture**: Clean separation of concerns with dedicated classes for entities, managers, handlers, and database operations
- **Laravel-Friendly**: Ready for seamless integration with Laravel applications
- **Production-Ready**: Built with performance, security, and scalability in mind
- **Type-Safe**: Comprehensive use of PHP 8.1+ features including enums, typed properties, and strict typing

### 👥 **Store Member Management**
- **Complete CRUD Operations**: Create, read, update, and delete store members
- **Role-Based Access Control**: Manager, Supervisor, Cashier, Employee roles with hierarchical permissions
- **Status Management**: Active, Inactive, Suspended, Terminated statuses with controlled transitions
- **Permission System**: Granular permission management for fine-tuned access control
- **Work Scheduling**: Flexible work schedule management for each member

### 🏬 **Store Management**
- **Multi-Store Support**: Manage multiple restaurant locations
- **Store Settings**: Configurable settings per store (timezone, currency, etc.)
- **Metadata Support**: Extensible metadata system for custom store information
- **Store Analytics**: Built-in statistics and reporting capabilities

### 🔒 **Security & Validation**
- **Input Validation**: Comprehensive validation for all data inputs
- **SQL Injection Protection**: Parameterized queries and prepared statements
- **Email Uniqueness**: Enforced unique email addresses across all stores
- **Role-Based Permissions**: Users can only perform actions within their authority level
- **Audit Logging**: Complete audit trail of all actions

### ⚡ **Performance & Optimization**
- **Database Indexing**: Optimized database schema with proper indexing
- **Caching Layer**: In-memory caching for frequently accessed data
- **Pagination Support**: Efficient pagination for large datasets
- **Search Optimization**: Fast search across members and stores
- **Connection Pooling**: Efficient database connection management

### 🌐 **API & Integration**
- **RESTful API**: Clean, intuitive REST API endpoints
- **JSON Responses**: Consistent JSON response format
- **CORS Support**: Built-in CORS headers for web integration
- **Error Handling**: Comprehensive error handling with meaningful messages
- **API Documentation**: Complete API documentation with examples

## 📋 Requirements

- PHP 8.1 or higher
- MySQL 5.7+ or MariaDB 10.3+
- PocketMine-MP (for Minecraft plugin integration)
- Composer for dependency management

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone https://github.com/khIbrahim/qoia-walls-of-betrayal.git
cd qoia-walls-of-betrayal
```

### 2. Database Setup
Execute the SQL schema to create the required tables:

```sql
-- Execute the contents of resources/sql/store/create_tables.sql
mysql -u your_username -p your_database < resources/sql/store/create_tables.sql
```

### 3. Configuration
Update your database configuration in the plugin's config files.

### 4. Integration
The store members system is automatically initialized when the plugin loads.

## 📚 API Documentation

### 🏬 Store Endpoints

#### Get All Stores
```http
GET /api/stores?page=1&per_page=25&active_only=true
```

#### Create Store
```http
POST /api/stores
Content-Type: application/json

{
    "name": "Downtown Restaurant",
    "description": "Main location in downtown area",
    "address": "123 Main St, City, State 12345",
    "phone": "+1-555-123-4567",
    "email": "downtown@restaurant.com",
    "settings": {
        "timezone": "America/New_York",
        "currency": "USD"
    }
}
```

#### Get Specific Store
```http
GET /api/stores/{storeId}
```

#### Update Store
```http
PUT /api/stores/{storeId}
Content-Type: application/json

{
    "name": "Updated Restaurant Name",
    "description": "Updated description"
}
```

#### Delete Store
```http
DELETE /api/stores/{storeId}
```

### 👥 Store Member Endpoints

#### Get Store Members
```http
GET /api/store/{storeId}/members?role=cashier&status=active&page=1
```

#### Create Member
```http
POST /api/store/{storeId}/members
Content-Type: application/json

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
    }
}
```

#### Get Specific Member
```http
GET /api/store/{storeId}/members/{memberId}
```

#### Update Member
```http
PUT /api/store/{storeId}/members/{memberId}
Content-Type: application/json

{
    "firstName": "Jane",
    "hourlyWage": 16.00
}
```

#### Change Member Status
```http
PATCH /api/store/{storeId}/members/{memberId}/status
Content-Type: application/json

{
    "status": "suspended"
}
```

#### Change Member Role
```http
PATCH /api/store/{storeId}/members/{memberId}/role
Content-Type: application/json

{
    "role": "supervisor"
}
```

#### Record Login
```http
POST /api/store/{storeId}/members/{memberId}/login
```

### 📊 Analytics Endpoints

#### Store Statistics
```http
GET /api/store/{storeId}/statistics
```

#### Search Members
```http
GET /api/members/search?firstName=John&role=cashier&status=active
```

## 🔐 Roles & Permissions

### Role Hierarchy
1. **Manager** (Level 4) - Full access to all operations
2. **Supervisor** (Level 3) - Can manage employees and cashiers
3. **Cashier** (Level 2) - Can process orders and payments
4. **Employee** (Level 1) - Basic operations only

### Permission Matrix

| Permission | Manager | Supervisor | Cashier | Employee |
|------------|---------|------------|---------|----------|
| view_all_orders | ✅ | ❌ | ❌ | ❌ |
| manage_inventory | ✅ | ✅ | ❌ | ❌ |
| manage_staff | ✅ | ❌ | ❌ | ❌ |
| view_reports | ✅ | ✅ | ❌ | ❌ |
| process_refunds | ✅ | ✅ | ❌ | ❌ |
| override_prices | ✅ | ✅ | ❌ | ❌ |
| process_payments | ✅ | ✅ | ✅ | ❌ |
| view_orders | ✅ | ✅ | ✅ | ✅ |
| basic_operations | ✅ | ✅ | ✅ | ✅ |

## 🔄 Status Transitions

```
Active ──→ Inactive, Suspended, Terminated
Inactive ──→ Active, Suspended, Terminated
Suspended ──→ Active, Inactive, Terminated
Terminated ──→ (Final state - no transitions)
```

## 🌐 Laravel Integration

### 1. Create Controller
```php
<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use fenomeno\WallsOfBetrayal\Handlers\API\StoreMemberApiHandler;
use fenomeno\WallsOfBetrayal\Main;

class StoreMemberController extends Controller
{
    private StoreMemberApiHandler $apiHandler;
    
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
    
    public function updateMember(Request $request, $storeId, $memberId)
    {
        $response = $this->apiHandler->updateMember($storeId, $memberId, $request->all());
        return response()->json($response, $response['status_code']);
    }
    
    // Add other methods as needed
}
```

### 2. Define Routes
```php
// routes/api.php
Route::prefix('store-members')->group(function () {
    // Store routes
    Route::get('/stores', [StoreMemberController::class, 'getAllStores']);
    Route::post('/stores', [StoreMemberController::class, 'createStore']);
    Route::get('/stores/{storeId}', [StoreMemberController::class, 'getStore']);
    Route::put('/stores/{storeId}', [StoreMemberController::class, 'updateStore']);
    Route::delete('/stores/{storeId}', [StoreMemberController::class, 'deleteStore']);
    
    // Member routes
    Route::get('/store/{storeId}/members', [StoreMemberController::class, 'getStoreMembers']);
    Route::post('/store/{storeId}/members', [StoreMemberController::class, 'createMember']);
    Route::get('/store/{storeId}/members/{memberId}', [StoreMemberController::class, 'getMember']);
    Route::put('/store/{storeId}/members/{memberId}', [StoreMemberController::class, 'updateMember']);
    Route::delete('/store/{storeId}/members/{memberId}', [StoreMemberController::class, 'deleteMember']);
    
    // Member actions
    Route::patch('/store/{storeId}/members/{memberId}/status', [StoreMemberController::class, 'changeMemberStatus']);
    Route::patch('/store/{storeId}/members/{memberId}/role', [StoreMemberController::class, 'changeMemberRole']);
    Route::post('/store/{storeId}/members/{memberId}/login', [StoreMemberController::class, 'recordLogin']);
    
    // Analytics
    Route::get('/store/{storeId}/statistics', [StoreMemberController::class, 'getStoreStatistics']);
    Route::get('/members/search', [StoreMemberController::class, 'searchMembers']);
});
```

### 3. Add Middleware (Optional)
```php
// For authentication and rate limiting
Route::middleware(['auth:api', 'throttle:60,1'])->prefix('store-members')->group(function () {
    // Your routes here
});
```

## 📊 Database Schema

### Stores Table
```sql
CREATE TABLE stores (
    store_id VARCHAR(64) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    address TEXT,
    phone VARCHAR(32),
    email VARCHAR(255),
    settings JSON,
    metadata JSON,
    is_active BOOLEAN DEFAULT 1,
    created_at INT UNSIGNED NOT NULL,
    updated_at INT UNSIGNED NOT NULL
);
```

### Store Members Table
```sql
CREATE TABLE store_members (
    member_id VARCHAR(64) PRIMARY KEY,
    store_id VARCHAR(64) NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(32),
    role ENUM('manager', 'supervisor', 'cashier', 'employee') NOT NULL,
    status ENUM('active', 'inactive', 'suspended', 'terminated') NOT NULL,
    permissions JSON,
    hourly_wage DECIMAL(10,2) DEFAULT 0.00,
    work_schedule JSON,
    metadata JSON,
    created_at INT UNSIGNED NOT NULL,
    updated_at INT UNSIGNED NOT NULL,
    last_login_at INT UNSIGNED NULL,
    FOREIGN KEY (store_id) REFERENCES stores(store_id) ON DELETE CASCADE
);
```

## 🧪 Testing

### Example Test Case
```php
// Test creating a store member
$response = $apiHandler->createMember('store_123', [
    'firstName' => 'John',
    'lastName' => 'Doe',
    'email' => 'john.doe@test.com',
    'phone' => '+1-555-123-4567',
    'role' => 'cashier',
    'hourlyWage' => 15.50
]);

assert($response['success'] === true);
assert($response['data']['member']['role'] === 'cashier');
```

## 🚀 Production Deployment

### 1. Environment Setup
- Configure proper database connections
- Set up SSL certificates for HTTPS
- Configure rate limiting
- Set up monitoring and logging

### 2. Security Considerations
- Use environment variables for sensitive configuration
- Implement API authentication (JWT, OAuth)
- Set up proper CORS policies
- Enable SQL query logging for security audits

### 3. Performance Optimization
- Enable database query caching
- Use Redis for session storage
- Implement CDN for static assets
- Set up database read replicas for scaling

## 📈 Monitoring & Analytics

The system includes built-in analytics:
- Store member counts by role/status
- Login tracking and activity monitoring
- Wage analytics and reporting
- Audit trail for all operations

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Implement your changes with tests
4. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

For support and questions:
- Create an issue on GitHub
- Check the API documentation at `/api.php`
- Review the code examples in this README

## 🔮 Roadmap

- [ ] GraphQL API support
- [ ] Real-time notifications
- [ ] Advanced reporting dashboard
- [ ] Mobile app integration
- [ ] Multi-language support
- [ ] Advanced scheduling features
- [ ] Time tracking integration
- [ ] Payroll integration APIs

---

Built with ❤️ for restaurant POS ecosystems. Ready for production use.