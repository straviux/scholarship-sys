# Code Review Checklist - Pull Request Template

Use this checklist when creating pull requests (PRs) and during code reviews. Copy and paste into PR description.

---

## PR Information

**Title:** [Type: Feature/Fix/Refactor] - Brief description

**Related Issue:** #123  
**Linked Zenhub:** [Link]  
**Test Environment:** [staging/production]  

**Changes Summary:**
- [ ] Describe 3-5 key changes made
- [ ] Explain why changes were necessary

---

## ✅ Pre-Submission Checklist (Author)

Before submitting PR, author must verify:

### 1. Code Quality
- [ ] Code follows CODING_STANDARDS.md conventions
- [ ] No commented-out code left behind
- [ ] No debug console.log() or dd() statements
- [ ] No var_dump() or print_r() statements
- [ ] All TODO comments have issue numbers: `// TODO: Issue #123`
- [ ] Functions/classes have PHPDoc comments
- [ ] Complex logic has inline comments explaining "why"

### 2. Database
- [ ] New migrations created for schema changes
- [ ] Migration names descriptive: `add_status_column_to_users`
- [ ] rollBack() method implemented (for down migrations)
- [ ] No modifying existing migration files (create new ones)
- [ ] Foreign key constraints added if needed
- [ ] Indexes added for frequently queried columns

### 3. Models
- [ ] Primary key strategy consistent (`id` or `uuid`, not mixed)
- [ ] $fillable list explicitly defined (never $guarded)
- [ ] $casts includes all non-string types (bool, datetime, decimal)
- [ ] Relationships use selective columns: `select(['id', 'name'])`
- [ ] No N+1 query issues (use `with()` for eager loading)
- [ ] Appropriate timestamps set (created_at, updated_at)

### 4. Controllers
- [ ] Using FormRequest for validation (not inline `$request->validate()`)
- [ ] Authorization checks with `$this->authorize('action', $model)`
- [ ] No business logic in controller (delegated to Service)
- [ ] Using type hints on all parameters
- [ ] Proper HTTP response codes: 201 (created), 404 (not found), 403 (forbidden)
- [ ] Error handling with try-catch for exceptions

### 5. Validation (FormRequest)
- [ ] FormRequest created for complex inputs
- [ ] authorize() method implemented
- [ ] rules() array with proper validation rules
- [ ] Custom messages() method with user-friendly text
- [ ] prepareForValidation() used for data transformation

### 6. Services/Business Logic
- [ ] Complex logic extracted to Service classes
- [ ] Single responsibility per service
- [ ] Transactions used for related operations: `DB::transaction(fn() => ...)`
- [ ] Exceptions thrown with meaningful messages
- [ ] No direct controller-to-model updates
- [ ] All public methods documented with PHPDoc

### 7. Testing
- [ ] Unit tests written (aim for 80%+ coverage)
- [ ] Feature/integration tests for API endpoints
- [ ] Tests follow Pest PHP conventions
- [ ] Factories used for test data (not fixtures)
- [ ] Tests verify both success and error cases
- [ ] Test names describe what's being tested: `test_user_can_create_entity`
- [ ] All tests passing locally: `php artisan test`

### 8. Security
- [ ] No hardcoded secrets (API keys, passwords, tokens)
- [ ] Sensitive data not logged (passwords, SSN, tokens)
- [ ] SQL injection prevention (use Eloquent, not raw queries)
- [ ] CSRF tokens included on forms
- [ ] File uploads whitelisted by type/extension
- [ ] No authentication bypass
- [ ] Authorization checks on all protected endpoints

### 9. Performance
- [ ] No N+1 queries (verified with `query` debugger)
- [ ] Pagination used for large result sets (not loading all)
- [ ] Database indexes on frequently queried columns
- [ ] Complex queries optimized (explain plan reviewed)
- [ ] No large loops with DB operations inside
- [ ] Caching used for expensive operations if needed

### 10. Documentation
- [ ] CHANGELOG.md updated with new features
- [ ] API documentation updated (if API changes)
- [ ] README updated if setup changes
- [ ] Complex functions have usage examples
- [ ] Admin guide updated if new permissions/features

### 11. Git Hygiene
- [ ] Branch named descriptively: `feat/user-profile-upload` or `fix/token-validation`
- [ ] Commits grouped logically (not 50 tiny commits)
- [ ] Commit messages start with verb: `feat:`, `fix:`, `refactor:`, `docs:`
- [ ] No merge commits in PR (rebase if needed)
- [ ] All commits are buildable (no broken intermediate states)

---

## 🔍 Reviewer Checklist

Reviewer must verify before approving:

### 1. Architecture Review
- [ ] Design follows established patterns
- [ ] No circular dependencies
- [ ] Appropriate use of services vs controllers vs models
- [ ] Relationships correctly defined
- [ ] Scope of change justified

### 2. Code Quality
- [ ] Code is readable without extensive comments
- [ ] Naming is clear and consistent
- [ ] DRY principle followed (no unnecessary duplication)
- [ ] SOLID principles not violated
- [ ] Complexity appropriate for function size (consider extracting)

### 3. Security Review
- [ ] Authorization checks on all protected routes
- [ ] Input validation on all user inputs
- [ ] No SQL injection vulnerabilities
- [ ] Sensitive data not exposed in logs
- [ ] No hardcoded credentials
- [ ] File uploads properly validated

### 4. Performance Review
- [ ] Database queries optimized
- [ ] No N+1 problems
- [ ] Pagination used where appropriate
- [ ] No unnecessary computations
- [ ] Indexes added for new foreign keys

### 5. Testing Review
- [ ] Tests are meaningful (not just coverage % for vanity)
- [ ] Edge cases tested (empty, null, boundary values)
- [ ] Error scenarios tested
- [ ] Tests actually test the feature (not just happy path)

### 6. Database Review
- [ ] Migrations have rollback/down logic
- [ ] No data loss on migration
- [ ] New columns have appropriate defaults
- [ ] Constraints properly defined
- [ ] No breaking changes to existing columns

### 7. Compatibility Review
- [ ] No breaking API changes (or documented with migration guide)
- [ ] Backwards compatible with production
- [ ] No deprecated Laravel methods used
- [ ] Works with current PHP version

### 8. Documentation Review
- [ ] Updated API docs if endpoints changed
- [ ] Clear commit messages for future reference
- [ ] Complex sections have comments explaining intent
- [ ] Examples provided if new patterns introduced

### 9. Testing Quality
- [ ] `php artisan test` passes 100%
- [ ] No skipped tests (skip for legitimate reasons, not laziness)
- [ ] Code coverage > 80% for changed files
- [ ] Tests run in < 5 minutes
- [ ] Tests not dependent on execution order

---

## 🚩 RED FLAGS (Stop Review, Request Changes)

Stop review and request changes if you see:

- **Security Issues**
  - [ ] Hardcoded API keys/passwords
  - [ ] SQL injection vulnerability (raw queries with user input)
  - [ ] No authorization checks on endpoints
  - [ ] Sensitive data logged (passwords, tokens, SSN)
  - [ ] Password stored as plaintext
  - [ ] File upload not validated/whitelisted

- **Critical Quality Issues**
  - [ ] N+1 query problem causing performance issues
  - [ ] Breaking changes without documentation/migration
  - [ ] No tests (especially for bug fixes)
  - [ ] Significant code duplication (copy-paste)
  - [ ] No error handling (unguarded try/throw)
  - [ ] Business logic in controller

- **Database Issues**
  - [ ] Migration without down() method
  - [ ] Modifying existing migration (create new one!)
  - [ ] No foreign key constraints
  - [ ] Data loss possible in migration
  - [ ] No indexes on new foreign keys

- **Standards Violations**
  - [ ] Using $guarded instead of $fillable
  - [ ] Inline validation instead of FormRequest
  - [ ] Inconsistent status field names
  - [ ] Missing type hints
  - [ ] No PHPDoc on public methods

---

## 📝 Common Issues to Comment On

When reviewing, provide constructive feedback on:

### Architecture & Patterns
```php
// ❌ Business logic in controller
public function store(Request $request) {
    $amount = $request->amount * 1.1;  // Calculation in controller
    Entity::create($request->all());
}

// ✅ Delegate to service
public function store(CreateRequest $request, EntityService $service) {
    $entity = $service->create($request->validated());
    return $this->success($entity);
}
```

### N+1 Queries
```php
// ❌ N+1 problem
$records = Record::all();
foreach ($records as $r) {
    echo $r->user->name;  // Query per record!
}

// ✅ Eager load
$records = Record::with('user:id,name')->get();
```

### Validation
```php
// ❌ Inline validation
public function store(Request $request) {
    $request->validate(['email' => 'required|email']);
}

// ✅ FormRequest
public function store(CreateRequest $request) {
    // Already validated, authorization checked
}
```

### Error Handling
```php
// ❌ Silent failure
$user = User::find($id);
echo $user->name;  // Crashes if null

// ✅ Proper handling
$user = User::findOrFail($id);  // Throws 404
$user?->name ?? 'Unknown';  // Safe null check
```

### Type Safety
```php
// ❌ No type hints
public function create($data) {
    return Entity::create($data);
}

// ✅ Type hints
public function create(array $data): Entity {
    return Entity::create($data);
}
```

---

## 🎯 Approval Checklist

Before approving PR:

- [ ] All reviewer checklist items completed
- [ ] No red flags raised
- [ ] Tests are passing
- [ ] Comments addressed or discussed
- [ ] Code follows team standards
- [ ] No security vulnerabilities
- [ ] Ready for production deployment

---

## 📋 Post-Merge Checklist (After Approval)

After PR is merged:

- [ ] PR labeled appropriately (bug, feature, refactor)
- [ ] Related GitHub issue closed (if applicable)
- [ ] Zenhub story moved to "Done"
- [ ] PR description updated with final results
- [ ] Release notes prepared if production feature
- [ ] Staging deployment verified
- [ ] Production deployment scheduled (if needed)

---

## 📊 Code Review Statistics to Track

Over time, track these metrics:

- Average review time per PR
- Most common feedback types
- Most common red flags
- Test coverage trend (aim for > 80%)
- Number of critical issues found
- Security vulnerabilities caught

---

## 🎓 Learning from Code Reviews

As reviewer, help team learn by:

1. **Explaining the "why"** not just "change this"
2. **Linking to standards** (reference CODING_STANDARDS.md)
3. **Providing examples** of correct pattern
4. **Sharing knowledge** about why pattern matters
5. **Being respectful** (feedback on code, not person)

---

## Template for PR Review Comments

```
### Issue
[Describe the problem]

### Why
[Explain why this matters - security, performance, maintainability]

### Suggested Fix
[Provide code example]

### Reference
[Link to CODING_STANDARDS.md section or best practice guide]
```

---

## Time Estimates

- **Small PR** (< 100 lines): 15 min review
- **Medium PR** (100-300 lines): 30 min review
- **Large PR** (300+ lines): 60+ min review (or split into smaller PRs)

If PR takes > 60 min to review, request author to split into smaller PRs.

---

## Example PR Description

```
## Description
Fixed MobileUploadController to use FileUploadService instead of duplicated image processing code.

## Type
- [x] Bug fix (non-breaking change)
- [ ] New feature
- [ ] Breaking change
- [x] Refactoring (no functional change)

## Related Issues
Closes #456

## Changes Made
- Created FileUploadService for centralized image compression (40% quality JPEG)
- Extracted TokenValidation trait (reusable in 5 methods)
- Created FormRequest classes for validation
- Refactored MobileUploadController (800 → 250 lines)
- Added comprehensive tests

## Testing
- All 45 tests passing
- Code coverage: 87%
- Manual tested: disbursement, scholarship record uploads

## Deployment Notes
- Database migration: Adds compression_ratio & original_size columns
- No breaking API changes
- Staging tested 2024-01-15

## Checklist
- [x] My code follows the style guidelines
- [x] I have performed a self-review
- [x] Tests pass locally
- [x] Documentation updated
```

---

**Last Updated:** 2024  
**Version:** 1.0

Use this template for every PR to ensure code quality and team alignment.

