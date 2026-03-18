# Coding Standards - Quick Reference (1-Page)

**Print this and pin it on your desk!**

---

## File Structure Pattern

```
app/
├── Models/EntityName.php                    # Business logic, relationships
├── Http/Controllers/EntityController.php    # Route handling, delegation
├── Http/Requests/CreateEntityRequest.php    # Validation & authorization
├── Services/EntityService.php               # Complex business workflows
└── Traits/SomeFunctionality.php             # Reusable utilities
```

---

## Model Pattern

```php
class Entity extends Model
{
    protected $fillable = [...];           // Explicit - never use $guarded
    protected $casts = [                   // Always cast: date, decimal, bool
        'created_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    protected static function boot()      // Auto-set created_by/updated_by
    {
        parent::boot();
        static::creating(fn($m) => $m->created_by = auth()->id());
        static::updating(fn($m) => $m->updated_by = auth()->id());
    }

    public function relationship()         // Use selective columns
    {
        return $this->belongsTo(Other::class)
            ->select(['id', 'name', 'email']);
    }

    public function scopeActive($query)    // Reusable query filters
    {
        return $query->where('is_active', true);
    }
}
```

---

## Controller Pattern

```php
class EntityController extends Controller
{
    public function store(CreateEntityRequest $request, EntityService $service)
    {
        // Request validates + authorizes automatically
        $service->create($request->validated());  // Delegate to service
        return back()->with('success', 'Created'); // Simple response
    }

    public function update(Request $request, Entity $entity, EntityService $service)
    {
        $service->update($entity, $request->validate([...]));
        return back()->with('success', 'Updated');
    }
}
```

---

## FormRequest Pattern

```php
class CreateEntityRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge(['created_by' => auth()->id()]);
    }
}

// Usage: Inject directly - validation happens automatically
public function store(CreateEntityRequest $request)
{
    $validated = $request->validated();  // Already validated!
}
```

---

## Service Pattern

```php
class EntityService
{
    /**
     * Create entity with all related records
     * 
     * @param array $data Validated data
     * @return Entity
     */
    public function create(array $data): Entity
    {
        return DB::transaction(function () use ($data) {
            $entity = Entity::create($data);
            // Related operations
            return $entity;
        });
    }

    private function validateCanCreate(array $data): void
    {
        if (condition) throw new InvalidArgumentException('...');
    }
}
```

---

## API Response Pattern

```php
// Create in base Controller
protected function success($data = null, string $message = 'OK', $code = 200)
{
    return response()->json([
        'success' => true,
        'message' => $message,
        'data' => $data,
    ], $code);
}

protected function error(string $message, $errors = [], $code = 400)
{
    return response()->json([
        'success' => false,
        'message' => $message,
        'errors' => $errors,
    ], $code);
}

// Usage
return $this->success($user, 'Created', 201);
return $this->error('Not found', code: 404);
```

---

## Logging Pattern

```php
// DO log: Meaningful state changes with context
Log::info('Record approved', [
    'record_id' => $record->id,
    'approved_by' => $user->id,
    'status_changed_from' => $oldStatus,
    'timestamp' => now()->toIso8601String(),
]);

// DON'T log: Sensitive data or every step
// ❌ Log::info('Password changed', ['password' => $password]);
```

---

## Database Query Optimization

```php
// ❌ Bad - N+1 problem
$records = Record::all();
foreach ($records as $r) { echo $r->user->name; }

// ✅ Good - Eager load
$records = Record::with('user:id,name')->get();

// ✅ Good - Query scope
$records = Record::active()->recent()->paginate(15);

// ✅ Good - Count optimization
$records = Record::withCount('comments')->get();
```

---

## Status Handling Rule

```
❌ WRONG: Multiple status fields (approval_status, unified_status, is_active)
✅ CORRECT: Single 'unified_status' with values: pending|approved|active|denied|suspended

# Map relationship:
unified_status = 'pending'     → scholarship_status_id = 0 (pending applicant)
unified_status = 'approved'    → scholarship_status_id = 1 (active)
unified_status = 'denied'      → scholarship_status_id = 2 (denied)
```

---

## Security Checklist

- [ ] All inputs validated via FormRequest
- [ ] Authorization checks: `Gate::allows()` or `$this->authorize()`
- [ ] Passwords hashed: `bcrypt()`
- [ ] CSRF tokens on forms: `@csrf`
- [ ] File uploads whitelisted by extension/mime
- [ ] Sensitive data NOT logged (passwords, tokens, SSN)
- [ ] SQL injection prevented (Eloquent with parameters)
- [ ] Token abbreviate in logs: `substr($token, 0, 10) . '...'`

---

## Naming Convention

| Item | Pattern | Example |
|------|---------|---------|
| Tables | plural snake_case | `scholarship_profiles` |
| PK | `id` or `{table}_id` | `profile_id`, `disbursement_id` |
| FK | `{table}_id` | `user_id`, `profile_id` |
| Flag | `is_*` or `has_*` | `is_active`, `has_children` |
| Status | `*_status` | `approval_status`, `unified_status` |
| Models | Singular PascalCase | `ScholarshipProfile` |
| Controllers | `{Entity}Controller` | `ScholarController` |
| Services | `{Entity}Service` | `ScholarshipApprovalService` |
| Requests | `{Create\|Update}{Entity}Request` | `CreateScholarRequest` |
| Methods | camelCase verbs | `approve()`, `generateReport()` |

---

## Code Review Red Flags 🚩

- [ ] No validation (missing FormRequest)
- [ ] Business logic in controller
- [ ] N+1 queries (missing eager load)
- [ ] Duplicate code (3+ same patterns)
- [ ] No error handling (missing try-catch)
- [ ] No logging of important changes
- [ ] Multiple status fields
- [ ] No authorization check
- [ ] Sensitive data in logs
- [ ] Inline SQL queries

---

## Git Commit Messages

```
Format: <type>: <subject>

Types: feat, fix, refactor, docs, test, perf, style

Examples:
✅ feat: add file upload service for image compression
✅ fix: prevent N+1 query in scholarship list
✅ refactor: extract token validation to trait
✅ docs: add coding standards guide
✅ test: add unit tests for approval service
```

---

## Quick Wins for Your Codebase

1. **Extract image processing** → `app/Services/FileUploadService.php`
2. **Extract token validation** → `app/Traits/TokenValidation.php`
3. **Create FormRequest** → `app/Http/Requests/MobileUploadRequest.php`
4. **Create base response methods** → `app/Http/Controllers/Controller.php`
5. **Add service for file operations** → `app/Services/DisbursementFileService.php`

---

**Print this page and reference it while coding!**
