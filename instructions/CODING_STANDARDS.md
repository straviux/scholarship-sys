# Comprehensive Coding Standards - Laravel 11 Scholarship System

**Version 1.0** | Last Updated: 2024 | Status: Active

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [File & Directory Structure](#2-file--directory-structure)
3. [Models: Design & Implementation](#3-models-design--implementation)
4. [Controllers: Routing & Delegation](#4-controllers-routing--delegation)
5. [Validation: FormRequest Pattern](#5-validation-formrequest-pattern)
6. [Services: Business Logic](#6-services-business-logic)
7. [Traits: Reusable Functionality](#7-traits-reusable-functionality)
8. [Database Conventions](#8-database-conventions)
9. [Security Standards](#9-security-standards)
10. [Logging & Debugging](#10-logging--debugging)
11. [Performance Optimization](#11-performance-optimization)
12. [HTTP Response Format](#12-http-response-format)
13. [Testing & Code Quality](#13-testing--code-quality)
14. [Common Pitfalls & Mandatory Rules](#14-common-pitfalls--mandatory-rules)
15. [Frontend Styling & UI Component Standards](#15-frontend-styling--ui-component-standards)
16. [Hybrid Architecture: Inertia Pages + API Modals](#16-hybrid-architecture-inertia-pages--api-modals)

---

## 1. Project Overview

**Technology Stack:**
- **Framework:** Laravel 11 with Inertia.js
- **Frontend:** Vue 3
- **Database:** MySQL with Eloquent ORM
- **Authentication:** Laravel Sanctum + Spatie Role-Based Permissions
- **Testing:** Pest PHP
- **File Processing:** GD image library, Browsershot (PDF rendering)

**Project Architecture:**
- Model-View-Controller with service layer
- **Hybrid routing: Inertia.js for page navigation, API endpoints for modal/component data**
- Role-based access control (RBAC)
- Event-driven activity logging
- File storage abstraction (local + Google Drive)
- Queue-backed jobs for heavy processing

---

## 2. File & Directory Structure

### 2.1 Application Root Structure

```
app/
├── Console/
│   └── Commands/                        # Artisan commands
├── Exports/                             # Laravel Excel exports
├── Http/
│   ├── Controllers/                     # Route handlers
│   ├── Requests/                        # FormRequest validation
│   └── Middleware/                      # Request middleware
├── Jobs/                                # Queued jobs
├── Models/                              # Eloquent models
├── Policies/                            # Authorization policies
├── Providers/                           # Service providers
├── Services/                            # Business logic services
└── Traits/                              # Reusable functionality

config/
├── app.php                              # Application configuration
├── auth.php                             # Authentication config
├── cache.php                            # Cache configuration
├── database.php                         # Database config
├── mail.php                             # Mailing config
├── permission.php                       # Spatie permissions config
└── services.php                         # Third-party services

database/
├── factories/                           # Model factories for testing
├── migrations/                          # Schema migrations
└── seeders/                             # Database seeders

resources/
├── js/
│   ├── Pages/                           # Vue page components
│   ├── Components/                      # Reusable Vue components
│   └── Layouts/                         # Layout components
├── css/                                 # Tailwind CSS
└── views/                               # Blade templates (if any)

routes/
├── api.php                              # API routes (if applicable)
└── web.php                              # Web routes

storage/
├── app/uploads/                         # File uploads
├── app/temp/                            # Temporary files
└── logs/                                # Application logs

tests/
├── Unit/                                # Unit tests
├── Feature/                             # Feature/integration tests
└── TestCase.php                         # Base test class
```

### 2.2 Naming Conventions by Directory

| Directory | Pattern | Example |
|-----------|---------|---------|
| `Models/` | Singular PascalCase | `ScholarshipProfile.php`, `Disbursement.php` |
| `Controllers/` | `{Entity}Controller.php` | `ScholarController.php`, `DisbursementController.php` |
| `Requests/` | `{Action}{Entity}Request.php` | `CreateScholarRequest.php`, `UpdateProfileRequest.php` |
| `Services/` | `{Entity}Service.php` | `ScholarshipApprovalService.php` |
| `Policies/` | `{Entity}Policy.php` | `ScholarPolicy.php` |
| `Traits/` | Descriptive noun | `TokenValidation.php`, `ManagesChromeForPdf.php` |
| `Jobs/` | Verb + context | `ProcessImageUpload.php`, `SendApprovalEmail.php` |
| `Migrations/` | `YYYY_MM_DD_000000_description.php` | `2024_01_15_000000_create_scholars_table.php` |
| `Tests/` | `{Entity}Test.php` or `{Feature}Test.php` | `ScholarTest.php`, `ApprovalWorkflowTest.php` |

---

## 3. Models: Design & Implementation

### 3.1 Model Structure

**Template:**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Entity extends Model
{
    use HasFactory, SoftDeletes, HasUuids;  // Order: HasFactory, SoftDeletes, HasUuids

    // 1. Primary Key Configuration
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    // 2. Mass Assignment
    protected $fillable = [
        'name',
        'email',
        'status',
        'created_by',
        'updated_by',
    ];

    // 3. Casting
    protected $casts = [
        'id' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'is_active' => 'boolean',
        'metadata' => 'json',
        'amount' => 'decimal:2',
    ];

    // 4. Relationships
    public function owner()
    {
        return $this->belongsTo(User::class, 'created_by', 'id')
            ->select(['id', 'name', 'email']);  // Selective columns!
    }

    public function children()
    {
        return $this->hasMany(Child::class)
            ->orderBy('created_at', 'desc');
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    // 5. Computed Properties
    public function getFullStatusAttribute(): string
    {
        return $this->status . ' (' . $this->updated_at->format('M d, Y H:i') . ')';
    }

    // 6. Query Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->whereNull('deleted_at');
    }

    public function scopeRecentFirst($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('unified_status', $status);
    }

    // 7. Boot / Auto-set Fields
    protected static function boot()
    {
        parent::boot();

        // Auto-set created_by on create
        static::creating(function ($model) {
            if (!isset($model->created_by) && auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        // Auto-set updated_by on update
        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    // 8. Helpers
    public function isOwner($user): bool
    {
        return $this->created_by === $user->id;
    }

    public function canEdit($user): bool
    {
        return $user->isAdmin() || $this->isOwner($user);
    }
}
```

### 3.2 Model Standards - Correct ✅ vs Wrong ❌

| Category | Correct ✅ | Wrong ❌ |
|----------|-----------|---------|
| **Primary Key** | `protected $primaryKey = 'id'` | Inconsistent PK names |
| **Fillable** | Explicit `$fillable` list (whitelist) | Using `$guarded = []` (blacklist danger!) |
| **Relationships** | `select(['id', 'name'])` on belongsTo | Loading all columns |
| **Scopes** | `public function scopeActive($q) { ... }` | Inline where in controller |
| **Timestamps** | `use Timestamps` (auto) | Manual timestamp management |
| **Soft Deletes** | `use SoftDeletes` for recoverable records | Hard delete if recoverable |
| **Casting** | `'amount' => 'decimal:2'` | String amounts (type safety!) |
| **Boot Hooks** | Auto-set created_by/updated_by | Manual assignment in controller |
| **Relationships** | Return query builder for dynamic filtering | Computing in loop (N+1) |
| **UUID PKs** | Generate in migration, use `HasUuids` | Random string in column |

### 3.3 Relationship Best Practices

**belongsTo (Many-to-One):**
```php
// Partner has one Account → Account belongsTo Partner
public function partner()
{
    return $this->belongsTo(Partner::class, 'partner_id', 'id')
        ->select(['id', 'name', 'email', 'status']);  // Load only needed columns
}
```

**hasMany (One-to-Many):**
```php
// Partner has many Accounts
public function accounts()
{
    return $this->hasMany(Account::class, 'partner_id')
        ->orderBy('created_at', 'desc')
        ->withCount('payments');  // Include count for dashboard
}
```

**belongsToMany (Many-to-Many):**
```php
// Scholar has many Requirements through pivot
public function requirements()
{
    return $this->belongsToMany(
        Requirement::class,
        'scholar_requirements',  // pivot table
        'scholar_id',            // FK in pivot
        'requirement_id'         // FK in pivot
    )->withPivot('submitted_at', 'status', 'notes')
     ->withTimestamps();  // Track pivot changes
}
```

**Polymorphic (One-to-Many across multiple models):**
```php
// Document belongs to Scholarship or Disbursement
public function documentable()
{
    return $this->morphTo();  // Returns ScholarshipRecord, Disbursement, etc.
}

// In ScholarshipRecord:
public function documents()
{
    return $this->morphMany(Document::class, 'documentable');
}
```

---

## 4. Controllers: Routing & Delegation

### 4.1 Controller Structure

**Template:**

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateEntityRequest;
use App\Http\Requests\UpdateEntityRequest;
use App\Models\Entity;
use App\Services\EntityService;
use Illuminate\Http\Request;

class EntityController extends Controller
{
    private EntityService $service;

    // Dependency injection via constructor
    public function __construct(EntityService $service)
    {
        $this->service = $service;
        $this->middleware('auth');  // All methods require auth
    }

    /**
     * Display all entities (with pagination)
     */
    public function index(Request $request)
    {
        $entities = Entity::query()
            ->when($request->search, fn($q) => $q->where('name', 'like', "%$request->search%"))
            ->with('owner:id,name')  // Eager load
            ->latest()
            ->paginate(15);

        return inertia('Entity/Index', [
            'entities' => $entities,
            'search' => $request->search ?? '',
        ]);
    }

    /**
     * Show create form
     */
    public function create()
    {
        $this->authorize('create', Entity::class);  // Check permission
        return inertia('Entity/Create');
    }

    /**
     * Store new entity
     * 
     * FormRequest validates + authorizes automatically!
     */
    public function store(CreateEntityRequest $request)
    {
        $entity = $this->service->create($request->validated());
        
        return back()->with('success', 'Entity created successfully.');
    }

    /**
     * Show entity details
     */
    public function show(Entity $entity)
    {
        $this->authorize('view', $entity);
        
        $entity->load('relationships');  // Load related data

        return inertia('Entity/Show', ['entity' => $entity]);
    }

    /**
     * Show edit form
     */
    public function edit(Entity $entity)
    {
        $this->authorize('update', $entity);
        
        return inertia('Entity/Edit', ['entity' => $entity]);
    }

    /**
     * Update entity
     */
    public function update(UpdateEntityRequest $request, Entity $entity)
    {
        $this->service->update($entity, $request->validated());
        
        return back()->with('success', 'Entity updated successfully.');
    }

    /**
     * Delete entity
     */
    public function destroy(Entity $entity)
    {
        $this->authorize('delete', $entity);
        
        $this->service->delete($entity);
        
        return back()->with('success', 'Entity deleted successfully.');
    }
}
```

### 4.2 Controller Standards - Correct ✅ vs Wrong ❌

| Aspect | Correct ✅ | Wrong ❌ |
|--------|-----------|---------|
| **Validation** | FormRequest injected as parameter | `$request->validate()` inline |
| **Authorization** | `$this->authorize('action', $model)` | No authorization checks |
| **Business Logic** | Delegated to Service | Mixed in controller |
| **Queries** | Eager load with `with()` | N+1 queries in loop |
| **Response** | Simple `back()->with()` or JSON | Complex response building |
| **Type Hints** | Parameters type-hinted | No type hints |
| **Dependency Injection** | Constructor injection | Global function calls |
| **Method Names** | Standard REST: index, show, create, store, edit, update, destroy | Custom method names |
| **Comments** | PHPDoc on public methods | Missing documentation |
| **Middleware** | Registered in constructor/route | Mixed authorization |

### 4.3 REST Controller Methods

```php
// Routes registered as Route::resource('entities', EntityController)
// Automatically maps to these methods:

GET    /entities              → index()      (list all)
GET    /entities/create       → create()     (show form)
POST   /entities              → store()      (save)
GET    /entities/{id}         → show()       (display one)
GET    /entities/{id}/edit    → edit()       (show edit form)
PUT    /entities/{id}         → update()     (save edit)
DELETE /entities/{id}         → destroy()    (delete)
```

---

## 5. Validation: FormRequest Pattern

### 5.1 FormRequest Structure

**Template:**

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateEntityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return auth()->check();  // Or: $this->user()?->can('create', Entity::class)
    }

    /**
     * Get the validation rules
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:entities,name'],
            'email' => ['required', 'email', 'max:255'],
            'status' => ['required', 'in:pending,active,inactive'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'related_id' => ['nullable', 'exists:relateds,id'],
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is mandatory.',
            'email.unique' => 'This email is already registered.',
            'related_id.exists' => 'The selected related record does not exist.',
        ];
    }

    /**
     * Prepare data before validation
     * 
     * Example: auto-set created_by, trim whitespace, etc.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'created_by' => auth()->id(),
            'name' => trim($this->name ?? ''),
            'status' => $this->status ?? 'pending',
        ]);
    }
}
```

### 5.2 Common Validation Rules

```php
// Required fields
'name' => ['required', 'string', 'max:255']

// Unique across table
'email' => ['email', 'unique:users,email,' . $this->user->id]

// File uploads
'document' => ['required', 'file', 'mimes:pdf,jpeg,png', 'max:2048']  // 2MB

// Relationships
'user_id' => ['required', 'exists:users,id']

// Enum/Select options
'status' => ['required', 'in:pending,approved,denied']

// Numeric with range
'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99']

// Array validation
'items' => ['required', 'array', 'min:1'],
'items.*.id' => ['required', 'exists:items,id'],
'items.*.quantity' => ['required', 'integer', 'min:1'],

// Custom rule
'phone' => ['required', new PhoneFormat()]

// Conditional
'notes' => ['required_if:status,rejected']

// Multiple conditions
'alternate_email' => [
    'nullable',
    'email',
    'different:email',  // Must be different from email field
]
```

### 5.3 FormRequest Standards - Correct ✅ vs Wrong ❌

| Aspect | Correct ✅ | Wrong ❌ |
|--------|-----------|---------|
| **Authorization** | In FormRequest.authorize() | Missing or in controller |
| **Validation** | FormRequest injected in controller | `$request->validate()` inline |
| **Rules** | Array of rule arrays: `['field' => ['rule1', 'rule2']]` | String rules: `'field' => 'rule1\|rule2'` |
| **Messages** | messages() method with custom text | Generic Laravel messages |
| **Preparation** | prepareForValidation() for trimming/defaults | Raw input usage |
| **Inheritance** | Extend FormRequest | Custom base class (use FormRequest!) |
| **Type Safety** | Validated array with type hints | Raw $request data |
| **Scope** | One FormRequest per action (Create/Update separate) | Single reusable validation |

---

## 6. Services: Business Logic

### 6.1 Service Structure

**Template:**

```php
<?php

namespace App\Services;

use App\Models\Entity;
use App\Events\EntityCreated;
use App\Events\EntityUpdated;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class EntityService
{
    /**
     * Create a new entity with validation and related operations
     * 
     * @param array $data Validated data from FormRequest
     * @return Entity
     * @throws InvalidArgumentException
     */
    public function create(array $data): Entity
    {
        return DB::transaction(function () use ($data) {
            // Validate business logic before creation
            $this->validateCanCreate($data);

            // Create the entity
            $entity = Entity::create($data);

            // Perform related operations
            // $this->notifyRelated($entity);
            // $this->logActivity($entity, 'created');

            // Dispatch event
            EntityCreated::dispatch($entity);

            return $entity;
        });
    }

    /**
     * Update existing entity
     * 
     * @param Entity $entity
     * @param array $data Validated data
     * @return Entity
     */
    public function update(Entity $entity, array $data): Entity
    {
        return DB::transaction(function () use ($entity, $data) {
            // Store old values for comparison
            $oldData = $entity->getAttributes();

            // Validate business logic
            $this->validateCanUpdate($entity, $data);

            // Update the entity
            $entity->update($data);

            // Log changes
            // $this->logChanges($entity, $oldData);

            // Dispatch event
            EntityUpdated::dispatch($entity, $oldData);

            return $entity;
        });
    }

    /**
     * Delete entity (soft or hard)
     */
    public function delete(Entity $entity): bool
    {
        return DB::transaction(function () use ($entity) {
            $entity->delete();  // Soft delete
            // $entity->forceDelete();  // Hard delete if needed
            return true;
        });
    }

    /**
     * Private validation method
     * 
     * @throws InvalidArgumentException
     */
    private function validateCanCreate(array $data): void
    {
        if (!isset($data['name']) || empty(trim($data['name']))) {
            throw new InvalidArgumentException('Name is required');
        }

        // Check for duplicates
        if (Entity::where('name', $data['name'])->exists()) {
            throw new InvalidArgumentException('Entity with this name already exists');
        }
    }

    private function validateCanUpdate(Entity $entity, array $data): void
    {
        // Custom validation logic for updates
    }
}
```

### 6.2 Service Standards - Correct ✅ vs Wrong ❌

| Aspect | Correct ✅ | Wrong ❌ |
|--------|-----------|---------|
| **Responsibility** | Single concern (one job) | Multiple unrelated operations |
| **Error Handling** | Throw exceptions with context | Silent failures or generic errors |
| **Transactions** | DB::transaction() for related ops | No transaction management |
| **Validation** | Business logic validation in service | No validation |
| **Return Type** | Consistent return types (Entity, bool) | Mixed types |
| **Dependencies** | Injected via constructor | Hard-coded or global |
| **Testability** | Public methods, injectable deps | Static calls, hidden dependencies |
| **Documentation** | PHPDoc with params and exceptions | Undocumented |
| **Events** | Dispatch events for observers | Direct, coupled operations |

---

## 7. Traits: Reusable Functionality

### 7.1 Trait Examples

**TokenValidation Trait:**
```php
<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

trait TokenValidation
{
    /**
     * Validate upload token from mobile
     * 
     * @param string $token The token to validate
     * @param string $entityType Type of entity (disbursement, record, etc)
     * @throws ValidationException
     */
    public function validateUploadToken(string $token, string $entityType = 'general'): void
    {
        // Token format: base64_encoded_json
        try {
            $decoded = json_decode(base64_decode($token), true);
            
            if (!isset($decoded['exp']) || !isset($decoded['entity_type'])) {
                throw ValidationException::withMessages([
                    'token' => 'Invalid token format'
                ]);
            }

            // Check expiry
            $expiry = Carbon::createFromTimestamp($decoded['exp']);
            if ($expiry->isPast()) {
                throw ValidationException::withMessages([
                    'token' => 'Token expired'
                ]);
            }

            // Check entity type matches
            if ($decoded['entity_type'] !== $entityType) {
                throw ValidationException::withMessages([
                    'token' => 'Token entity type mismatch'
                ]);
            }

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'token' => 'Token validation failed: ' . $e->getMessage()
            ]);
        }
    }
}
```

**ManagesChromeForPdf Trait:**
```php
<?php

namespace App\Traits;

use Spatie\Browsershot\Browsershot;

trait ManagesChromeForPdf
{
    /**
     * Generate PDF from HTML string
     */
    protected function generatePdf(string $html, string $filename): string
    {
        $path = storage_path("app/temp/$filename.pdf");

        Browsershot::html($html)
            ->setChromePath(env('CHROME_BIN_PATH', '/usr/bin/google-chrome'))
            ->pdf()
            ->save($path);

        return $path;
    }

    /**
     * Check if Chrome is available
     */
    protected function isChromeAvailable(): bool
    {
        $chromePath = env('CHROME_BIN_PATH', '/usr/bin/google-chrome');
        return file_exists($chromePath) && is_executable($chromePath);
    }
}
```

### 7.2 Trait Standards

- **Single Responsibility:** Each trait handles one concern
- **Naming:** Descriptive verb + noun (TokenValidation, ManagesChromeForPdf)
- **Conflict Avoidance:** Don't use generic method names
- **Documentation:** PHPDoc on all public methods
- **No Model Traits:** Focus on Controllers, Services, Tests

---

## 8. Database Conventions

### 🔴 CRITICAL DATABASE SAFETY RULES

> **The development database (`scholarship_program_devmode`) contains REAL DATA imported from the live server. Treat it as production data.**

**Database names and their purposes:**
| Database | Purpose | Used By |
|----------|---------|---------|
| `scholarship_program_devmode` | **Development** — the ONLY database for development. Contains real imported data. | `.env` (`DB_DATABASE`) |
| `scholarship_program_testing` | **Testing only** — used by PHPUnit/Pest tests. May be wiped by test suites. | `phpunit.xml` |
| `scholarship_program` | **Live/Production** — source database on the live server. Never connect to this in dev. | Production server only |

**Rules:**
- **Always use `scholarship_program_devmode`** as the development database. Never change `DB_DATABASE` in `.env` to any other database.
- **Never point tests at `scholarship_program_devmode`**. Tests must use `scholarship_program_testing` (configured in `phpunit.xml`).
- **Never connect to or modify `scholarship_program`** from the dev environment.

**NEVER do the following without explicit user approval:**
- `php artisan migrate:fresh` — This **destroys all tables and data**
- `php artisan migrate:refresh` — This **rolls back and re-runs all migrations, deleting data**
- `php artisan migrate:reset` — This **rolls back all migrations, deleting tables**
- `php artisan db:wipe` — This **drops all tables**
- Any raw SQL `DROP TABLE`, `TRUNCATE TABLE`, or `DELETE FROM` without WHERE clause
- Running seeders (`php artisan db:seed`) — **Do NOT create or run seeders unless the user explicitly requests it**

**SAFE migration commands (use these instead):**
- `php artisan migrate` — Only runs **new** pending migrations (safe, additive only)
- `php artisan migrate --pretend` — Shows SQL without executing (for review)
- `php artisan migrate:status` — Shows which migrations have run

**Migration file rules:**
- New migrations must be **additive only** (add columns, add tables, add indexes)
- If a migration needs to remove/rename columns, the `down()` method must restore them
- Never write a migration that drops an existing table
- Test migrations with `--pretend` first when unsure

**Seeder rules:**
- Do NOT create database seeders unless the user explicitly asks for them
- Do NOT run `db:seed` on the development database — it has real imported data
- Factory files may be created for testing purposes only, but must target a separate test database

### 8.1 Table Naming

```php
// Plural snake_case
users              // NOT: user, users_table, tbl_users
scholarship_profiles
scholarship_records
disbursements
fund_transactions
documents          // Polymorphic document storage

// Pivot tables: singular models in alphabetical order + _id
scholar_requirements       // links scholars ↔ requirements
user_roles                 // links users ↔ roles
```

### 8.2 Column Naming

```php
// Primary key
id                 // Standard auto-increment
uuid               // String UUID

// Foreign keys
user_id            // Table_singular_id
partner_id
created_by         // Audit field (FK to users)
updated_by         // Audit field (FK to users)

// Boolean fields
is_active          // Prefix with is_
has_children       // OR has_ for relationships
can_edit           // OR can_ for capabilities

// Status/State fields
unified_status     // Single status field (DO NOT create multiple)
Valid values: pending|approved|active|denied|suspended

// Timestamps (automatic)
created_at
updated_at
deleted_at         // If using SoftDeletes

// Other common
email              // User contact field
phone              // User contact field
name               // Required for most entities
notes              // Text field for comments
metadata           // JSON stored data
amount             // Decimal amounts (decimal:2)
expires_at         // Expiry timestamp
started_at         // Event start time
ended_at           // Event end time
```

### 8.3 Column Types by Use Case

```php
Schema::create('entities', function (Blueprint $table) {
    $table->id();                                      // Primary key
    $table->uuid();                                    // Alternative PK
    
    $table->string('email')->unique();                 // Text, unique
    $table->string('phone', 20);                       // Max length specified
    $table->text('description');                       // Longer text
    
    $table->integer('count')->default(0);              // Integer
    $table->decimal('amount', 10, 2)->default(0);      // Money: 10 digits, 2 decimals
    
    $table->boolean('is_active')->default(true);       // Boolean
    $table->enum('status', ['pending', 'active'])->default('pending');  // Enum
    
    $table->json('metadata')->nullable();               // Stored JSON
    
    $table->foreignId('user_id')->constrained('users');  // FK with constraint
    $table->foreignId('partner_id')->nullable()->constrained('partners');  // Nullable FK
    
    $table->timestamp('created_at')->useCurrent();       // Automatic
    $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();  // Automatic
    $table->timestamp('deleted_at')->nullable();         // Soft delete
    $table->timestamp('expires_at')->nullable();         // Custom timestamp
    
    $table->foreignId('created_by')->constrained('users');  // Audit field
    $table->foreignId('updated_by')->nullable()->constrained('users');  // Audit field
    
    $table->index('email');                             // Index for queries
    $table->fullText('description');                    // Full-text search
});
```

---

## 9. Security Standards

### 9.1 Authentication & Authorization

```php
// Authorization in FormRequest
public function authorize(): bool
{
    return auth()->check();  // User must be authenticated
}

// OR more specific:
public function authorize(): bool
{
    return auth()->user()?->can('create', Entity::class);  // Policy-based
}

// Authorization in Controller
public function show(Entity $entity)
{
    $this->authorize('view', $entity);  // Throws 403 if denied
    // ...
}

// Authorization in routes
Route::get('/entities/{entity}', [EntityController::class, 'show'])
    ->middleware('can:view,entity');
```

### 9.2 Password Security

```php
// Hashing
$hashed = bcrypt($password);          // Hash on registration
Hash::check($input, $hashed);          // Verify on login

// Never log passwords
// ❌ Log::info('Password changed', ['password' => $password]);

// Never store plaintext
// ❌ $user->update(['password' => $request->password]);
```

### 9.3 File Upload Security

```php
// Validate file type
'document' => ['required', 'file', 'mimes:pdf,jpeg,png', 'max:2048']

// Store in non-public directory
Storage::disk('private')->put('documents/' . $filename, $content);

// Return via stream, not direct URL
return Storage::download('documents/' . $filename);

// Verify file content (magic bytes)
$finfo = new \finfo(FILEINFO_MIME_TYPE);
$mimetype = $finfo->buffer(file_get_contents($file));
if (!in_array($mimetype, ['application/pdf', 'image/jpeg'])) {
    throw new Exception('Invalid file type');
}
```

### 9.4 CSRF & CORS

```php
// CSRF tokens in forms (automatic in Inertia)
@csrf

// Disable CSRF for API if using tokens
Route::middleware('api')->group(function () {
    // These don't need CSRF but should use sanctum()
});

// CORS if needed
// Configured in config/cors.php
```

### 9.5 Input Validation

```php
// Use FormRequest for all validation
// Never trust user input
// ❌ $name = request('name');
// ✅ $name = $request->validated()['name'];

// Sanitize HTML input
$clean = strip_tags($input);

// Log sensitive data abbreviation
Log::info('Token received', ['token' => substr($token, 0, 10) . '...']);
```

---

## 10. Logging & Debugging

### 10.1 Logging Standards

**DO Log:**

```php
// Significant state changes
Log::info('scholarship_approved', [
    'record_id' => $record->id,
    'approved_by' => $user->id,
    'amount' => $record->amount,
    'timestamp' => now()->toIso8601String(),
    'status_changed_from' => $oldStatus,
]);

// Errors with context
Log::error('file_upload_failed', [
    'entity_id' => $entity->id,
    'file_size' => $file->getSize(),
    'mime_type' => $file->getMimeType(),
    'error' => $e->getMessage(),
    'line' => $e->getLine(),
]);

// Token validation (abbreviated)
Log::debug('upload_token_validated', [
    'token' => substr($token, 0, 10) . '...',
    'entity_type' => 'disbursement',
    'expires_at' => $expiry->toIso8601String(),
    'user_id' => auth()->id(),
]);
```

**DON'T Log:**

```php
// ❌ Passwords
Log::info('Login attempt', ['password' => $password]);

// ❌ Sensitive personal data
Log::info('Profile created', ['ssn' => $ssn, 'bank_account' => $account]);

// ❌ Full auth tokens
Log::info('Token used', ['token' => $fullToken]);

// ❌ Every trivial step
Log::debug('Query executed');  // Too verbose
Log::debug('Method called');   // Too granular
```

### 10.2 Log Levels

```php
Log::debug('...msg...');   // Development only, verbose
Log::info('...msg...');    // Important state changes
Log::warning('...msg...');  // Potential issues, recoverable
Log::error('...msg...');    // Error occurred, not critical
Log::critical('...msg...');  // Critical system failure
Log::emergency('...msg...'); // System down
```

### 10.3 Conditional Logging

```php
// Only in debug mode
if (config('app.debug')) {
    Log::debug('Detailed state information');
}

// Log only on errors
try {
    // operation
} catch (Exception $e) {
    Log::error('Operation failed', [
        'error' => $e->getMessage(),
        'exception' => class_basename($e),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
}
```

---

## 11. Performance Optimization

### 11.1 Query Optimization - N+1 Prevention

**❌ Bad - N+1 Queries:**

```php
$records = Record::all();  // 1 query

foreach ($records as $record) {
    echo $record->user->name;  // N additional queries!
}
// Total: 1 + N queries
```

**✅ Good - Eager Loading:**

```php
$records = Record::with('user:id,name')->get();  // 2 queries total

foreach ($records as $record) {
    echo $record->user->name;  // No additional queries
}
```

### 11.2 Query Scopes & Pagination

```php
// Use scopes for reusable filters
public function scopeRecent($query)
{
    return $query->orderBy('created_at', 'desc');
}

public function scopeActive($query)
{
    return $query->where('is_active', true);
}

// In controller
$records = Record::active()
    ->recent()
    ->with('user:id,name')
    ->paginate(15);  // Default 15, not loading all
```

### 11.3 Select Specific Columns

```php
// Load only needed columns
$users = User::select(['id', 'name', 'email'])->get();

// Or in relationships
public function owner()
{
    return $this->belongsTo(User::class, 'created_by')
        ->select(['id', 'name', 'email']);
}

// Usage
$records = Record::with('owner:id,name')->get();
```

### 11.4 Count Optimization

```php
// ❌ Bad - loads all records then counts
$count = $user->posts()->count();  // O(n)

// ✅ Good - count in database
$count = $user->posts()->count();  // Efficient when using query builder

// Better - include in list query
$users = User::withCount('posts')->get();
// Now access with: $user->posts_count
```

### 11.5 Lazy Loading vs Eager Loading

```php
// Eager load related data upfront
$records = Record::with('user', 'documents')->paginate(15);

// Use lazy loading modifier if unsure
$record->load('documents');  // Loads if not already loaded

// Don't load all then check existence
$record->exists();  // Just query: exists() is more efficient
```

---

## 12. HTTP Response Format

### 12.1 JSON Response Structure

**Success Response:**
```json
{
    "success": true,
    "message": "Operation completed successfully",
    "data": {
        "id": 123,
        "name": "John Doe",
        "created_at": "2024-01-15T10:30:00Z"
    }
}
```

**Error Response:**
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["Email is required", "Email must be unique"],
        "name": ["Name must be at least 3 characters"]
    }
}
```

**Validation Exception Response (automatic):**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["Email is required"],
        "password": ["Password must be at least 8 characters"]
    }
}
```

### 12.2 Response Methods in Controller

```php
// Base Controller with helper methods
class Controller extends BaseController
{
    protected function success($data = null, string $message = 'OK', int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function error(string $message, $errors = [], int $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    protected function unauthorized(string $message = 'Unauthorized')
    {
        return $this->error($message, code: 403);
    }

    protected function notFound(string $message = 'Not found')
    {
        return $this->error($message, code: 404);
    }
}

// Usage in controllers
public function store(CreateRequest $request)
{
    $entity = $this->service->create($request->validated());
    return $this->success($entity, 'Entity created', 201);
}

public function show(Entity $entity)
{
    if (!$entity) {
        return $this->notFound('Entity not found');
    }
    return $this->success($entity);
}

public function delete(Entity $entity)
{
    if (!auth()->user()->can('delete', $entity)) {
        return $this->unauthorized('Permission denied');
    }
    $this->service->delete($entity);
    return $this->success(null, 'Deleted successfully');
}
```

---

## 13. Testing & Code Quality

### 13.1 Test File Structure

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Entity;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EntityTest extends TestCase
{
    use RefreshDatabase;  // Fresh database for each test

    /**
     * Test creating entity returns success
     */
    public function test_can_create_entity()
    {
        $response = $this->post('/api/entities', [
            'name' => 'Test Entity',
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.name', 'Test Entity');

        // Verify in database
        $this->assertDatabaseHas('entities', [
            'name' => 'Test Entity',
        ]);
    }

    /**
     * Test unauthorized access is forbidden
     */
    public function test_unauthorized_access_is_forbidden()
    {
        $response = $this->post('/api/entities', [...]);
        $response->assertStatus(403);
    }
}
```

### 13.2 Test Standards

- **Naming:** `test_` prefix, descriptive action
- **Setup:** Use factories for test data
- **Assertions:** Check response code, content, and database state
- **Cleanup:** Use RefreshDatabase trait
- **Coverage:** Aim for 80%+ code coverage

```bash
# Run tests
php artisan test

# With coverage report
php artisan test --coverage

# Specific test
php artisan test tests/Feature/EntityTest.php::test_can_create_entity
```

### 13.3 Code Quality Tools

```bash
# Code style (Laravel Pint)
./vendor/bin/pint

# Static analysis (Larastan)
./vendor/bin/phpstan analyse app/

# Security audit
./vendor/bin/phpstan --level=9 analyse app/
```

---

## 14. Common Pitfalls & Mandatory Rules

> Rules derived from recurring bugs found in this codebase. Follow strictly.

### 14.1 Facade Imports — Always Use `use` Statements

```php
// ✅ Correct — import facades at the top of the file
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

DB::transaction(fn() => ...);
Log::info('message');
Auth::id();

// ❌ Wrong — backslash-prefixed global references
\DB::transaction(fn() => ...);
\Log::info('message');
\Auth::id();
```

**Why:** Backslash-prefixed facades (`\DB::`, `\Log::`) bypass the IDE's type resolution and cause "Undefined type" errors in static analysis. Always add a proper `use` import at the top.

### 14.2 Auth Helper — Use `Auth::id()` Not `auth()->id()`

```php
// ✅ Correct
use Illuminate\Support\Facades\Auth;

'user_id' => Auth::id(),
'user' => Auth::user(),

// ❌ Wrong — IDE cannot resolve return type of auth() helper
'user_id' => auth()->id(),      // "Undefined method 'id'" warning
'user_id' => auth()?->id(),     // Same issue with nullsafe
```

**Why:** The `auth()` helper returns `Guard|null` which the IDE can't resolve `->id()` on. `Auth::id()` has proper return type hints. Both work at runtime, but `Auth::id()` is IDE-safe and consistent.

### 14.3 Closures — Always Capture Outer Variables with `use`

```php
// ✅ Correct — $voucher captured via use()
$documents->map(function ($doc) use ($voucher) {
    return ['url' => "/api/vouchers/{$voucher->id}/docs/{$doc->id}"];
});

// ❌ Wrong — $voucher is undefined inside the closure
$documents->map(function ($doc) {
    return ['url' => "/api/vouchers/{$voucher->id}/docs/{$doc->id}"];
});
```

**Why:** PHP closures do not inherit parent scope variables automatically. Omitting `use ($var)` causes "Undefined variable" errors.

### 14.4 PHPUnit Assertions — Use Correct Method Names

```php
// ✅ Correct PHPUnit method names
$this->assertGreaterThan(0, $value);
$this->assertEquals($expected, $actual);
$this->assertLessThan(100, $value);
$this->assertStringContainsString('needle', $haystack);

// ❌ Wrong — these methods do NOT exist in PHPUnit
$this->assertGreater(0, $value);        // Does not exist
$this->assertEqual($expected, $actual);  // Does not exist
$this->assertLesser(100, $value);        // Does not exist
```

**Common PHPUnit assertion reference:**

| Correct ✅ | Wrong ❌ (does not exist) |
| --- | --- |
| `assertGreaterThan()` | `assertGreater()` |
| `assertEquals()` | `assertEqual()` |
| `assertLessThan()` | `assertLesser()` |
| `assertGreaterThanOrEqual()` | `assertGreaterOrEqual()` |
| `assertStringContainsString()` | `assertContainsString()` |

### 14.5 Model Factory PHPDoc — Must Match Actual Factory

```php
// ✅ Correct — PHPDoc references the correct factory
class ScholarshipRecord extends Model
{
    /** @use HasFactory<\Database\Factories\ScholarshipRecordFactory> */
    use HasFactory;
}

// ❌ Wrong — PHPDoc references a different model's factory
class ScholarshipRecord extends Model
{
    /** @use HasFactory<\Database\Factories\ApplicantFactory> */
    use HasFactory;
}
```

**Why:** The `@use HasFactory<Factory>` PHPDoc tells the IDE which factory `::factory()` returns. If it points to the wrong factory, every test using `Model::factory()->create()` will show type mismatch errors.

---

## Summary Checklist

Use this checklist when writing code:

- [ ] **Models:** Explicit $fillable, proper $casts, relationships with selective columns
- [ ] **Models:** `@use HasFactory<\Database\Factories\CorrectFactory>` PHPDoc matches the actual factory
- [ ] **Controllers:** FormRequest validation, $this->authorize(), delegation to Service
- [ ] **Services:** Single responsibility, transactions for related ops, return consistent types
- [ ] **Validation:** FormRequest, authorize(), messages(), prepareForValidation()
- [ ] **Queries:** Eager load with with(), select specific columns, use pagination
- [ ] **Logging:** Log important state changes, abbreviate sensitive data, don't log passwords
- [ ] **Responses:** Use success()/error() helpers, include message and data
- [ ] **Security:** FormRequest validation, authorization checks, no plaintext passwords
- [ ] **Facades:** Always use `use` imports, never backslash-prefixed (`\DB::`, `\Log::`)
- [ ] **Auth:** Use `Auth::id()` not `auth()->id()` for IDE type safety
- [ ] **Closures:** Capture outer variables with `use ($var)` when needed inside closures
- [ ] **Testing:** Correct PHPUnit methods, factories, RefreshDatabase
- [ ] **Documentation:** PHPDoc on public methods, comments for complex logic
- [ ] **Icons:** Only PrimeIcons (`pi pi-*`) — no Heroicons, no inline SVG
- [ ] **Buttons:** PrimeVue `<Button>` — never raw `<button>` for user actions
- [ ] **Forms:** PrimeVue InputText, Select, Textarea, DatePicker
- [ ] **Modals:** PrimeVue `<Dialog>` — no new Headless UI dialogs
- [ ] **Tables:** PrimeVue DataTable (interactive) or DaisyUI `table-zebra` (simple)
- [ ] **Selects:** Use purpose-built select components from `Components/selects/` (Section 15.6)
- [ ] **Colors:** Follow semantic color palette (Section 15.10)
- [ ] **Pages:** Loaded via Inertia routes — never via API
- [ ] **Modals/Drawers:** Data fetched via API endpoints — never via Inertia visits
- [ ] **HTTP Client:** All API calls use `axios` — never `fetch()` or `XMLHttpRequest`

---

## 15. Frontend Styling & UI Component Standards

> Uniform styling across all Vue components. Every developer must follow these rules to keep the UI consistent and the bundle lean.

### 15.1 Technology Stack Roles

Each CSS/UI technology has a **specific role**. Do not mix their responsibilities.

| Technology | Role | When to Use |
| --- | --- | --- |
| **Tailwind CSS v4** | Utility-first layout & spacing | Margins, padding, flex/grid layout, text color, backgrounds, borders, responsive breakpoints, transitions |
| **PrimeVue v4** | Interactive UI components | Buttons, dialogs, drawers, data tables, form inputs, menus, tabs, toolbars, toasts, confirmations |
| **DaisyUI v5** | Semantic prebuilt patterns | Tables (`table table-zebra`), badges (`badge`), alerts, and cases where PrimeVue has no equivalent or is overkill |

**Decision tree:**
1. Need an interactive component (button, dialog, dropdown, form input, data table)? → **PrimeVue**
2. Need layout, spacing, colors, or responsive design? → **Tailwind utilities**
3. Need a simple semantic pattern PrimeVue doesn't cover (e.g., zebra-striped HTML table)? → **DaisyUI**

### 15.2 Icons — PrimeIcons Only

**PrimeIcons is the single icon library for this project.** No other icon library should be used.

```vue
<!-- ✅ Correct — PrimeIcons via class string -->
<i class="pi pi-check"></i>
<i class="pi pi-times"></i>
<i class="pi pi-spinner pi-spin"></i>
<Button icon="pi pi-plus" label="Add" />
<Button icon="pi pi-trash" severity="danger" />

<!-- ❌ Wrong — @heroicons/vue (DO NOT USE) -->
<script setup>
import { XMarkIcon } from '@heroicons/vue/20/solid'  // BANNED
</script>
<XMarkIcon class="h-5 w-5" />

<!-- ❌ Wrong — inline SVG icons -->
<svg xmlns="..." viewBox="...">...</svg>

<!-- ❌ Wrong — any other icon library -->
<i class="fa fa-check"></i>           <!-- Font Awesome -->
<span class="material-icons">check</span>  <!-- Material -->
```

**Common PrimeIcons reference:**

| Category | Icons |
| --- | --- |
| **Navigation** | `pi-chevron-left`, `pi-chevron-right`, `pi-chevron-up`, `pi-chevron-down`, `pi-angle-left`, `pi-angle-right`, `pi-bars`, `pi-arrow-left`, `pi-arrow-right` |
| **Actions** | `pi-plus`, `pi-pencil`, `pi-trash`, `pi-eye`, `pi-download`, `pi-upload`, `pi-refresh`, `pi-save`, `pi-copy`, `pi-print` |
| **Status** | `pi-check`, `pi-check-circle`, `pi-times`, `pi-times-circle`, `pi-exclamation-triangle`, `pi-info-circle`, `pi-ban` |
| **Loading** | `pi-spinner` (add `pi-spin` class to animate) |
| **Objects** | `pi-file`, `pi-file-pdf`, `pi-folder`, `pi-folder-open`, `pi-image`, `pi-calendar`, `pi-clock`, `pi-user`, `pi-users`, `pi-cog`, `pi-search` |
| **Finance** | `pi-credit-card`, `pi-money-bill`, `pi-wallet`, `pi-chart-bar`, `pi-chart-line` |
| **Communication** | `pi-bell`, `pi-envelope`, `pi-comment`, `pi-comments`, `pi-phone`, `pi-qrcode` |

Full catalog: https://primevue.org/icons/#list

### 15.3 Buttons — PrimeVue `<Button>` Component

Always use the PrimeVue `<Button>` component. Never use raw `<button>` elements for user-facing actions.

```vue
<!-- ✅ Correct — PrimeVue Button -->
<Button label="Save" icon="pi pi-save" />
<Button label="Delete" icon="pi pi-trash" severity="danger" />
<Button label="Cancel" severity="secondary" text />
<Button icon="pi pi-ellipsis-v" text rounded />

<!-- ✅ Correct — severity levels for consistent color meaning -->
<Button severity="primary" />    <!-- Default action (zinc/dark) -->
<Button severity="success" />    <!-- Positive: approve, save, confirm -->
<Button severity="warn" />       <!-- Caution: edit, override, modify -->
<Button severity="danger" />     <!-- Destructive: delete, reject, remove -->
<Button severity="info" />       <!-- Informational: view, details, preview -->
<Button severity="secondary" />  <!-- Neutral: cancel, back, dismiss -->
<Button severity="contrast" />   <!-- High contrast: alternative primary -->

<!-- ✅ Correct — button variants -->
<Button label="Solid" />                    <!-- Default filled -->
<Button label="Outlined" outlined />        <!-- Border only -->
<Button label="Text" text />                <!-- No background/border -->
<Button label="Rounded" rounded />          <!-- Pill shape -->
<Button label="Small" size="small" />       <!-- Compact -->
<Button label="Large" size="large" />       <!-- Prominent -->
<Button loading />                          <!-- Shows spinner -->

<!-- ❌ Wrong — raw HTML button -->
<button class="bg-blue-500 text-white px-4 py-2 rounded">Save</button>
<button class="btn btn-primary">Save</button>  <!-- DaisyUI btn -->
```

### 15.4 Dialogs & Modals — PrimeVue `<Dialog>` / `<Drawer>`

Use PrimeVue `<Dialog>` for modals and `<Drawer>` for side panels. Do not use Headless UI `<TransitionRoot>` + `<Dialog>`.

```vue
<!-- ✅ Correct — PrimeVue Dialog -->
<Dialog v-model:visible="showModal" header="Edit Record" modal :style="{ width: '600px' }">
    <template #footer>
        <Button label="Cancel" severity="secondary" text @click="showModal = false" />
        <Button label="Save" icon="pi pi-save" @click="save" />
    </template>
</Dialog>

<!-- ✅ Correct — PrimeVue Drawer for side panels -->
<Drawer v-model:visible="showDrawer" header="Filters" position="right">
    <!-- filter content -->
</Drawer>

<!-- ❌ Wrong — Headless UI for new components -->
<TransitionRoot :show="isOpen">
    <HeadlessDialog @close="isOpen = false">
        <!-- ... -->
    </HeadlessDialog>
</TransitionRoot>
```

> **Migration note:** Existing Headless UI modals (CourseModal, SchoolModal, RequirementModal, ApplicantProfileModal, Modal.vue) are legacy. Convert them to PrimeVue Dialog when modifying those files.

### 15.5 Form Inputs — PrimeVue Components

Use PrimeVue form components for all form fields. Tailwind is for layout around them.

```vue
<!-- ✅ Correct — PrimeVue form components -->
<InputText v-model="name" placeholder="Enter name" class="w-full" />
<Textarea v-model="notes" rows="3" class="w-full" />
<Select v-model="status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />
<DatePicker v-model="date" class="w-full" />
<InputNumber v-model="amount" mode="currency" currency="PHP" class="w-full" />
<Checkbox v-model="agreed" :binary="true" />
<RadioButton v-model="choice" value="A" />
<ToggleSwitch v-model="enabled" />
<FileUpload mode="basic" accept="image/*" @select="onUpload" />

<!-- ✅ Correct — form layout with Tailwind -->
<div class="grid grid-cols-2 gap-4">
    <div class="flex flex-col gap-1">
        <label class="text-sm font-medium text-gray-700">Name</label>
        <InputText v-model="form.name" class="w-full" />
    </div>
    <div class="flex flex-col gap-1">
        <label class="text-sm font-medium text-gray-700">Email</label>
        <InputText v-model="form.email" class="w-full" />
    </div>
</div>

<!-- ❌ Wrong — raw HTML inputs -->
<input type="text" class="border rounded px-3 py-2" v-model="name" />
<select class="border rounded px-3 py-2" v-model="status">...</select>
```

### 15.6 Select Components — Use Existing Purpose-Built Selects

The project has **pre-built select components** in `resources/js/Components/selects/` that wrap PrimeVue Select with built-in API fetching, caching, and domain logic. **Always use these instead of raw PrimeVue Select or third-party select libraries.**

**Available select components:**

| Component | Purpose | Data Source | Key Props |
| --- | --- | --- | --- |
| `<AcademicYearSelect>` | Academic year (range or single) | Computed (2018–current) | `v-model`, `label` |
| `<TermSelect>` | Semester / term | API (system-options) | `v-model`, `label` |
| `<YearLevelSelect>` | Student year level | Hardcoded (G12, 1ST–6TH, etc.) | `v-model`, `label`, `multiple` |
| `<SchoolSelect>` | School / institution | API (schools.getactivelist) | `v-model`, `multiple`, `showNullOption` |
| `<ProgramSelect>` | Scholarship program | API (scholarshipprograms.getactivelist) | `v-model`, `multiple` |
| `<CourseSelect>` | Course (filtered by program) | API (courses by program) | `v-model`, `scholarshipProgramId`, `multiple` |
| `<MunicipalitySelect>` | Municipality | API (municipalities) with cache | `v-model`, `multiple` |
| `<BarangaySelect>` | Barangay (filtered by municipality) | API (barangays by municipality) | `v-model`, `municipalityId`, `multiple` |
| `<GenderSelect>` | Gender | Hardcoded (M/F) | `v-model` |
| `<ReligionSelect>` | Religion | API (system-options) with cache | `v-model` |
| `<CivilStatusSelect>` | Marital status | Hardcoded | `v-model` |
| `<RecordsSelect>` | Pagination page size | Hardcoded (5–500) | `v-model` |
| `<ProfileSelect>` | Search existing scholar profile | API (profiles.existing) with search | `v-model`, `label` |

```vue
<!-- ✅ Correct — use the purpose-built select component -->
<SchoolSelect v-model="form.school_id" />
<CourseSelect v-model="form.course_id" :scholarshipProgramId="form.program_id" />
<ProgramSelect v-model="form.program_id" />
<MunicipalitySelect v-model="form.municipality_id" />
<BarangaySelect v-model="form.barangay_id" :municipalityId="form.municipality_id" />
<AcademicYearSelect v-model="form.academic_year" />
<TermSelect v-model="form.term" />
<YearLevelSelect v-model="form.year_level" />
<GenderSelect v-model="form.gender" />

<!-- ✅ Correct — PrimeVue Select for one-off selects with no existing component -->
<Select v-model="form.status" :options="statusOptions" optionLabel="label" optionValue="value" class="w-full" />

<!-- ❌ Wrong — raw HTML select -->
<select v-model="form.school_id">
    <option v-for="s in schools" :value="s.id">{{ s.name }}</option>
</select>

<!-- ❌ Wrong — vue-multiselect or vue3-select-component for new code -->
<VueMultiselect v-model="form.program" :options="programs" />
<VueSelect v-model="form.role" :options="roles" />
```

> **Migration note:** Existing pages using `vue-multiselect` or `vue3-select-component` directly (CourseModal, RequirementModal, ApplicantProfileModal, Admin/Users/Create, Admin/Users/Edit) are legacy. Convert them to the appropriate purpose-built select component or PrimeVue Select when modifying those files.

### 15.7 Data Display — PrimeVue `<DataTable>` or DaisyUI Tables

```vue
<!-- ✅ Correct — PrimeVue DataTable for interactive tables (sorting, filtering, pagination) -->
<DataTable :value="records" paginator :rows="10" stripedRows>
    <Column field="name" header="Name" sortable />
    <Column field="status" header="Status" sortable />
    <Column header="Actions">
        <template #body="{ data }">
            <Button icon="pi pi-eye" text rounded size="small" @click="view(data)" />
        </template>
    </Column>
</DataTable>

<!-- ✅ Correct — DaisyUI table for simple read-only display -->
<table class="table table-zebra">
    <thead><tr><th>Name</th><th>Status</th></tr></thead>
    <tbody>
        <tr v-for="item in items" :key="item.id">
            <td>{{ item.name }}</td>
            <td>{{ item.status }}</td>
        </tr>
    </tbody>
</table>

<!-- ❌ Wrong — raw HTML table with manual Tailwind striping -->
<table class="w-full">
    <tr v-for="(item, i) in items" :class="i % 2 === 0 ? 'bg-gray-50' : ''">
        <!-- ... -->
    </tr>
</table>
```

### 15.8 Notifications — PrimeVue Toast

```vue
<script setup>
import { useToast } from 'primevue/usetoast';
const toast = useToast();

// ✅ Correct — PrimeVue toast for all notifications
toast.add({ severity: 'success', summary: 'Saved', detail: 'Record updated successfully', life: 3000 });
toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to save record', life: 5000 });
toast.add({ severity: 'warn', summary: 'Warning', detail: 'Unsaved changes', life: 4000 });
toast.add({ severity: 'info', summary: 'Info', detail: 'Processing...', life: 3000 });
</script>
```

### 15.9 Tailwind Usage Rules

Tailwind utilities are for **layout, spacing, and visual tweaks** — not for building component-level styling from scratch.

```vue
<!-- ✅ Correct — Tailwind for layout around PrimeVue components -->
<div class="flex items-center justify-between gap-4 p-4 border-b border-gray-200">
    <h2 class="text-lg font-semibold text-gray-800">Records</h2>
    <Button icon="pi pi-plus" label="Add" />
</div>

<!-- ✅ Correct — Tailwind responsive utilities -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <!-- cards -->
</div>

<!-- ✅ Correct — Tailwind for spacing, colors, typography -->
<p class="text-sm text-gray-500 mt-2">Last updated: {{ date }}</p>
<span class="inline-flex items-center gap-1 text-green-600 font-medium">
    <i class="pi pi-check-circle"></i> Approved
</span>

<!-- ❌ Wrong — recreating a button component with Tailwind -->
<div class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg 
            cursor-pointer transition-colors duration-200 shadow-sm" @click="save">
    Save Record
</div>
```

### 15.10 Color Palette — Consistent Semantic Colors

Use these Tailwind color conventions across all pages:

| Meaning | Text | Background | Border |
| --- | --- | --- | --- |
| **Primary/Default** | `text-gray-800` | `bg-white` | `border-gray-200` |
| **Success/Active** | `text-green-600` | `bg-green-50` | `border-green-200` |
| **Warning/Pending** | `text-yellow-600` | `bg-yellow-50` | `border-yellow-200` |
| **Danger/Error** | `text-red-600` | `bg-red-50` | `border-red-200` |
| **Info/Neutral** | `text-blue-600` | `bg-blue-50` | `border-blue-200` |
| **Muted/Disabled** | `text-gray-400` | `bg-gray-50` | `border-gray-100` |
| **Heading text** | `text-gray-800` | — | — |
| **Body text** | `text-gray-600` | — | — |
| **Label text** | `text-gray-700` | — | — |
| **Subtle text** | `text-gray-500` | — | — |

### 15.11 Status Badges — Uniform Pattern

```vue
<!-- ✅ Correct — reusable status display pattern -->
<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"
      :class="{
          'bg-green-50 text-green-700': status === 'active' || status === 'approved',
          'bg-yellow-50 text-yellow-700': status === 'pending',
          'bg-red-50 text-red-700': status === 'rejected' || status === 'inactive',
          'bg-blue-50 text-blue-700': status === 'processing',
          'bg-gray-100 text-gray-600': status === 'draft',
      }">
    {{ status }}
</span>

<!-- ✅ Also correct — PrimeVue Tag for simpler cases -->
<Tag :value="status" :severity="statusSeverity" />
```

### 15.12 Typography & Sizing Standards

These are the global sizing defaults set in `resources/css/app.css`:

| Element | Font Size | Notes |
| --- | --- | --- |
| `body` | 14px | Global base |
| DataTable header | 12px | Column headers |
| DataTable body | 13px | Row content |
| Dialog | 14px | Modal content |
| Menu / Dropdown | 13px | Menu items |
| Panel header | 13px | Section titles |

**Heading hierarchy within pages:**
```vue
<h1 class="text-xl font-bold text-gray-800">Page Title</h1>
<h2 class="text-lg font-semibold text-gray-800">Section Title</h2>
<h3 class="text-base font-semibold text-gray-700">Subsection</h3>
<p class="text-sm text-gray-600">Body content</p>
<span class="text-xs text-gray-500">Helper text / caption</span>
```

### 15.13 Banned Patterns

These patterns are **not allowed** in new code:

| Banned | Use Instead |
| --- | --- |
| `@heroicons/vue` imports | PrimeIcons (`pi pi-*`) |
| `vue-dynamic-heroicons` imports | PrimeIcons (`pi pi-*`) |
| Inline `<svg>` icons | PrimeIcons (`pi pi-*`) |
| Font Awesome / Material icons | PrimeIcons (`pi pi-*`) |
| Raw `<button>` for actions | PrimeVue `<Button>` |
| Raw `<input>`, `<select>`, `<textarea>` | PrimeVue InputText, Select, Textarea |
| `vue-multiselect` (new code) | Purpose-built select component or PrimeVue Select |
| `vue3-select-component` (new code) | Purpose-built select component or PrimeVue Select |
| Headless UI `<Dialog>` (new code) | PrimeVue `<Dialog>` |
| Headless UI `<TransitionRoot>` (new code) | PrimeVue `<Dialog>` with built-in transitions |
| Tailwind-only button styling | PrimeVue `<Button>` with severity |
| DaisyUI `btn` classes | PrimeVue `<Button>` |
| Custom CSS in `<style>` blocks for common patterns | Tailwind utilities or PrimeVue component props |
| `fetch()` / `XMLHttpRequest` for API calls | `axios` (import from 'axios') or `useApi` composable |

### 15.14 Summary Checklist — Styling

- [ ] **Icons:** Only `pi pi-*` classes — no Heroicons, no inline SVG, no other icon libraries
- [ ] **Buttons:** PrimeVue `<Button>` — never raw `<button>` for user actions
- [ ] **Forms:** PrimeVue InputText, Select, Textarea, DatePicker, etc.
- [ ] **Modals:** PrimeVue `<Dialog>` — no new Headless UI dialogs
- [ ] **Tables:** PrimeVue `<DataTable>` for interactive, DaisyUI `table table-zebra` for simple display
- [ ] **Notifications:** PrimeVue Toast — no custom alert divs
- [ ] **Layout:** Tailwind flex/grid utilities for positioning and spacing
- [ ] **Colors:** Follow the semantic color palette (Section 15.10)
- [ ] **Status badges:** Use the uniform pattern (Section 15.11) or PrimeVue `<Tag>`
- [ ] **API calls:** Use `axios` — never `fetch()` or `XMLHttpRequest`

---

## 16. Hybrid Architecture: Inertia Pages + API Modals

This application uses a **hybrid routing architecture**. Main page navigation is handled by Inertia.js, while modals, drawers, and other overlay components fetch their data through API endpoints.

### 16.1 The Rule

| Layer | Routing Method | Example |
| --- | --- | --- |
| **Pages** (full-page views) | Inertia routes (`web.php` → `return inertia(...)`) | Scholar list, Dashboard, Settings |
| **Modals / Drawers / Overlays** | API routes (`api.php` → JSON response) | Edit scholar modal, View details drawer, Create form dialog |

> **Never** use `Inertia::visit()`, `router.visit()`, or `<Link>` to load modal content. Modals must always fetch data via `axios` to an API endpoint.

### 16.2 HTTP Client — Axios Only

**`axios` is the sole HTTP client for all API calls in this project.** Do not use `fetch()`, `XMLHttpRequest`, or any other HTTP library.

Axios is configured globally in `resources/js/bootstrap.js` with:
- **CSRF token** auto-injected from `<meta name="csrf-token">` header
- **`X-Requested-With: XMLHttpRequest`** header set by default
- Available as `import axios from 'axios'` in any component

**Existing composable:** The project provides a `useApi` composable in `resources/js/composable/api.js` for simple GET requests with built-in `data`, `loading`, and `error` refs:

```js
import { useApi } from '@/composable/api';

const { data, loading, error, fetchData } = useApi('/api/scholars');
await fetchData({ status: 'active' });  // pass query params
```

For mutations (POST/PUT/DELETE) or more complex flows, use `axios` directly:

```js
import axios from 'axios';

// GET
const { data } = await axios.get('/api/scholars', { params: { status: 'active' } });

// POST
await axios.post('/api/scholars', formData);

// PUT
await axios.put(`/api/scholars/${id}`, formData);

// DELETE
await axios.delete(`/api/scholars/${id}`);
```

**URL generation:** Use Laravel's `route()` helper when named routes are available:
```js
const { data } = await axios.get(route('api.scholars.show', scholarId));
await axios.post(route('disbursements.store'), formData);
```

### 16.3 Why Hybrid?

- **No full-page reload** when opening a modal — smoother UX.
- **Decoupled modal logic** — modals can be reused across different pages without coupling to Inertia props.
- **Simpler state management** — modal data lives in the component, not in shared Inertia page props.
- **Better performance** — only fetch the data the modal needs, not the entire page payload.

### 16.4 Backend Pattern

**Page controller (Inertia — `web.php`):**
```php
// routes/web.php
Route::get('/scholars', [ScholarController::class, 'index'])->name('scholars.index');

// ScholarController.php
public function index(Request $request)
{
    $scholars = Scholar::query()->paginate(15);
    return inertia('Scholar/Index', [
        'scholars' => $scholars,
    ]);
}
```

**Modal data endpoint (API — `api.php`):**
```php
// routes/api.php
Route::get('/scholars/{scholar}', [ScholarApiController::class, 'show'])->name('api.scholars.show');
Route::put('/scholars/{scholar}', [ScholarApiController::class, 'update'])->name('api.scholars.update');

// ScholarApiController.php
public function show(Scholar $scholar)
{
    return response()->json([
        'success' => true,
        'data' => $scholar->load('course', 'school'),
    ]);
}

public function update(UpdateScholarRequest $request, Scholar $scholar)
{
    $scholar->update($request->validated());
    return response()->json([
        'success' => true,
        'message' => 'Scholar updated successfully',
        'data' => $scholar->fresh(),
    ]);
}
```

### 16.5 Frontend Pattern

```vue
<script setup>
import { ref } from 'vue';
import axios from 'axios';
import Dialog from 'primevue/dialog';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';

const toast = useToast();
const showModal = ref(false);
const scholar = ref(null);
const loading = ref(false);

// ✅ Correct — fetch modal data via API
async function openEditModal(scholarId) {
    loading.value = true;
    try {
        const { data } = await axios.get(`/api/scholars/${scholarId}`);
        scholar.value = data.data;
        showModal.value = true;
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load scholar', life: 3000 });
    } finally {
        loading.value = false;
    }
}

// ✅ Correct — submit modal form via API
async function saveScholar() {
    try {
        await axios.put(`/api/scholars/${scholar.value.id}`, scholar.value);
        toast.add({ severity: 'success', summary: 'Saved', detail: 'Scholar updated', life: 3000 });
        showModal.value = false;
        // Refresh the Inertia page data
        router.reload({ only: ['scholars'] });
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Save failed', life: 3000 });
    }
}
</script>

<template>
    <!-- ✅ Correct — modal fetches and submits via API -->
    <Dialog v-model:visible="showModal" header="Edit Scholar" modal :style="{ width: '600px' }">
        <div v-if="scholar" class="flex flex-col gap-4">
            <!-- form fields -->
        </div>
        <template #footer>
            <Button label="Cancel" severity="secondary" text @click="showModal = false" />
            <Button label="Save" icon="pi pi-save" @click="saveScholar" />
        </template>
    </Dialog>
</template>
```

### 16.6 Anti-Patterns

```vue
<!-- ❌ Wrong — using Inertia visit to load modal data -->
<script setup>
import { router } from '@inertiajs/vue3';

function openModal(id) {
    // DO NOT do this — this causes a full Inertia page visit
    router.visit(`/scholars/${id}/edit`);
}
</script>

<!-- ❌ Wrong — using fetch() instead of axios -->
<script setup>
async function loadData() {
    // DO NOT use fetch — always use axios
    const response = await fetch('/api/scholars');       // ❌ BANNED
    const data = await response.json();                  // ❌
}

// ✅ Correct — use axios
import axios from 'axios';
const { data } = await axios.get('/api/scholars');       // ✅
</script>

<!-- ❌ Wrong — passing all modal data through Inertia page props -->
<!-- ScholarController.php -->
// Do NOT bloat the page with modal data that may never be used
public function index()
{
    return inertia('Scholar/Index', [
        'scholars' => Scholar::paginate(15),
        'scholarDetail' => $selectedScholar,  // ❌ Don't preload modal data in page props
        'editFormOptions' => $options,         // ❌ Don't preload modal options in page props
    ]);
}
```

### 16.7 When to Refresh Inertia Page Data

After a successful API mutation (create, update, delete) from a modal, refresh the parent Inertia page so the list stays in sync:

```js
import { router } from '@inertiajs/vue3';

// After successful API save in a modal:
router.reload({ only: ['scholars'] });  // partial reload — only refresh the 'scholars' prop
```

### 16.8 Summary

| Action | Method |
| --- | --- |
| Navigate to a page | Inertia `<Link>` / `router.visit()` |
| Load data for a modal/drawer | `axios.get('/api/...')` |
| Submit a modal form | `axios.post/put/delete('/api/...')` |
| Refresh page after modal save | `router.reload({ only: [...] })` |
| Dropdown/select option lists | `axios.get('/api/...')` (in select components) |

---

**Document Version:** 1.3  
**Last Updated:** 2026  
**Author:** Code Standards Agreement  
**Status:** Active & Enforced

---

*This document is the source of truth for all development on this project. Reference it daily.*
