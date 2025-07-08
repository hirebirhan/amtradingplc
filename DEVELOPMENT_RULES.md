# Muhdin General Trading - Development Rules & Standards

## 🎯 CORE PRINCIPLES

### Architecture Philosophy
- **Clean Architecture**: SOLID principles, separation of concerns, single responsibility
- **Maintainability First**: Code should be self-documenting and easily modifiable
- **User Experience Focus**: Every change should improve or maintain UX quality
- **Performance Conscious**: Optimize for both development speed and runtime performance
- **Security by Design**: Implement security measures from the ground up

---

## 🧩 CODING PATTERN PREFERENCES

- **Always prefer simple solutions.**
- **Avoid code duplication:** Check for similar code/functionality before adding new logic.
- **Environment awareness:** Write code that works in dev, test, and prod.
- **Change discipline:** Only make changes you fully understand and that are directly related to the request.
- **No new patterns/tech for bugfixes:**  
  When fixing bugs, do **not** introduce new patterns or technologies without first exhausting all options for the existing implementation.  
  If a new pattern is necessary, remove the old one to avoid duplicate logic.
- **Keep the codebase clean and organized.**
- **Avoid one-off scripts:** Don’t write scripts in files unless they’ll be reused.
- **File size discipline:** Refactor files that exceed 200–300 lines.
- **Mocking:** Only mock data for tests, never for dev/prod.
- **No stubbing/fake data in prod/dev code.**
- **Never overwrite .env without explicit confirmation.**

---

## 🏗️ TECHNICAL FOUNDATION

### Laravel Standards (Laravel 12 + PHP 8.2+)
```php
<?php

declare(strict_types=1);

namespace App\Livewire\{{ Module }};

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\View\View;
use Exception;
use Illuminate\Support\Facades\{DB, Log};

#[Layout('layouts.app')]
class {{ ComponentName }} extends Component
{
    // 1. Properties (typed)
    public string $property = '';
    
    // 2. Lifecycle hooks
    public function mount(): void {}
    
    // 3. Validation rules
    protected function rules(): array {}
    
    // 4. Business logic methods
    public function businessMethod(): void {}
    
    // 5. Render method last
    public function render(): View {}
}
```

### Livewire 3.6 Standards
- **Component Structure**: Follow established 5-section pattern
- **Error Handling**: Always wrap in try-catch with logging
- **Database Operations**: Use transactions for data integrity
- **Validation**: Centralized rules() method with custom messages
- **Performance**: Use #[Url] attributes for query string management
- **Security**: Implement proper authorization checks

### Database & Model Standards
```php
// Model Structure
class ExampleModel extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = ['field1', 'field2'];
    protected $casts = ['date_field' => 'date'];
    
    // Relationships with return types
    public function relatedModel(): BelongsTo
    {
        return $this->belongsTo(RelatedModel::class);
    }
    
    // Model events for business logic
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            // Business logic
        });
    }
}
```

---

## 🎨 UI/UX STANDARDS

### Bootstrap 5.3 Framework - STRICT COMPLIANCE
**🚨 CRITICAL: NO CUSTOM CSS UNLESS ABSOLUTELY NECESSARY**

#### UI/UX Principles
- **Consistency:** All UI must follow the established Bootstrap 5.3 patterns and use the theme color system.
- **Simplicity:** Avoid over-engineering; minimal, clean, and professional (YouTube Studio style).
- **Accessibility:** All interactive elements must be accessible (ARIA, keyboard navigation).
- **Feedback:** Use Toastr for notifications, loading states for async, and confirmation dialogs for destructive actions.
- **Responsiveness:** Mobile-first, test all breakpoints, no custom CSS unless absolutely necessary.
- **No custom classes:** Only Bootstrap utilities and theme variables.

#### Layout Structure
```html
<!-- Standard Page Layout -->
<div>
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-4">
        <div class="flex-grow-1">
            <h4 class="fw-bold mb-0">{{ Page Title }}</h4>
            <p class="text-secondary mb-0 small">{{ Description }}</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <!-- Action Buttons -->
        </div>
    </div>

    <!-- Main Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <!-- Content -->
        </div>
    </div>
</div>
```

#### Component Patterns
```html
<!-- Search Input -->
<div class="input-group">
    <span class="input-group-text bg-transparent border-end-0">
        <i class="bi bi-search"></i>
    </span>
    <input type="text" class="form-control border-start-0" 
           placeholder="Search..." 
           wire:model.live.debounce.300ms="search">
</div>

<!-- Status Badge -->
<span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis">
    {{ $status }}
</span>

<!-- Action Buttons -->
<div class="btn-group">
    <a href="{{ route('show') }}" class="btn btn-sm btn-outline-info" title="View">
        <i class="bi bi-eye"></i>
    </a>
    <a href="{{ route('edit') }}" class="btn btn-sm btn-outline-primary" title="Edit">
        <i class="bi bi-pencil"></i>
    </a>
    <button class="btn btn-sm btn-outline-danger" title="Delete" wire:click="delete">
        <i class="bi bi-trash"></i>
    </button>
</div>
```

### Theme & Color System
```css
/* Use CSS Custom Properties for consistency */
:root {
    --bs-primary: 222 47% 11%;
    --bs-secondary: 215 16% 47%;
    --bs-success: 142 76% 36%;
    --bs-info: 217 91% 60%;
    --bs-warning: 43 96% 56%;
    --bs-danger: 0 84% 60%;
}

/* Dark theme support */
[data-bs-theme="dark"] {
    --bs-primary: 213 94% 68%;
    --background: 240 10% 4%;
    --foreground: 0 0% 98%;
}
```

### Responsive Design Rules
- **Mobile-First**: Design for mobile, enhance for desktop
- **Bootstrap Grid**: Use proper container/row/col structure
- **Breakpoints**: Test on sm, md, lg, xl, xxl
- **Flexbox**: Use Bootstrap flex utilities (d-flex, justify-content, align-items)
- **No Custom Media Queries**: Use Bootstrap's responsive classes

---

## 📊 BUSINESS LOGIC STANDARDS

### Data Validation
```php
protected function rules(): array
{
    return [
        'field' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users'],
        'amount' => ['required', 'numeric', 'min:0'],
    ];
}

protected function messages(): array
{
    return [
        'field.required' => 'This field is required.',
        'email.unique' => 'This email is already registered.',
    ];
}
```

### Error Handling Pattern
```php
public function criticalOperation(): void
{
    try {
        DB::beginTransaction();
        
        // Business logic here
        
        DB::commit();
        $this->notify('Success message', 'success');
        
    } catch (Exception $e) {
        DB::rollBack();
        Log::error('Operation failed', [
            'error' => $e->getMessage(),
            'user_id' => auth()->id(),
        ]);
        $this->notify('User-friendly error message', 'error');
    }
}
```

### User Authorization
```php
// Always check permissions
public function mount(): void
{
    if (!Auth::user()->can('resource.action')) {
        abort(403, 'Insufficient permissions.');
    }
}

// Use policies for model-level authorization
@can('view', $model)
    <!-- Content -->
@endcan
```

---

## 🔄 REUSABILITY & SHARED RESOURCES

### Component Library
```php
// Reusable Livewire Components
class SearchDropdown extends Component
{
    public $search = '';
    public $filteredResults = [];
    public $selected = null;
    public $showDropdown = false;
    public $placeholder = 'Search...';
    public $minimumCharacters = 2;
    public $maxResults = 10;
    public $emitUpEvent = 'itemSelected';
    
    // Standard methods for all dropdowns
    public function updatedSearch() {}
    public function selectItem($item) {}
    public function clearSelected() {}
}
```

### Blade Components
```php
// Reusable Blade Components
@props([
    'label' => null,
    'options' => [],
    'model' => '',
    'placeholder' => 'Select option',
])

<select class="form-select" wire:model.live="{{ $model }}">
    <option value="">{{ $placeholder }}</option>
    @foreach($options as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach
</select>
```

### Service Layer
```php
// Business logic in services
class ExampleService
{
    public function processData(array $data): bool
    {
        return DB::transaction(function () use ($data) {
            // Business logic
            return true;
        });
    }
}
```

---

## 🎯 USER EXPERIENCE STANDARDS

### Loading States
```php
// Always show loading states for async operations
public $isSubmitting = false;

public function save(): void
{
    $this->isSubmitting = true;
    
    try {
        // Operation
        $this->notify('Success!', 'success');
    } finally {
        $this->isSubmitting = false;
    }
}
```

### User Feedback
```php
// Use Toastr for notifications
$this->notify('Operation completed successfully!', 'success');
$this->notify('An error occurred. Please try again.', 'error');
$this->notify('Please check the form for errors.', 'warning');
```

### Confirmation Dialogs
```php
// Use wire:confirm for destructive actions
<button wire:click="delete({{ $id }})" 
        wire:confirm="Are you sure? This action cannot be undone."
        class="btn btn-danger">
    Delete
</button>
```

### Progressive Enhancement
- **Core Functionality**: Works without JavaScript
- **Enhanced Experience**: Better UX with JavaScript enabled
- **Accessibility**: Proper ARIA labels and keyboard navigation
- **Performance**: Lazy loading for heavy components

---

## 📱 RESPONSIVENESS STANDARDS

### Mobile-First Approach
```html
<!-- Responsive Table -->
<div class="d-none d-lg-block">
    <!-- Desktop table -->
</div>
<div class="d-lg-none">
    <!-- Mobile cards -->
</div>
```

### Responsive Utilities
```html
<!-- Responsive visibility -->
<div class="d-none d-md-block">Desktop only</div>
<div class="d-md-none">Mobile only</div>

<!-- Responsive spacing -->
<div class="p-3 p-md-4 p-lg-5">Responsive padding</div>

<!-- Responsive text -->
<h1 class="h3 h-md-2 h-lg-1">Responsive heading</h1>
```

### Touch-Friendly Design
- **Button Size**: Minimum 44px touch targets
- **Spacing**: Adequate spacing between interactive elements
- **Gestures**: Support for touch gestures where appropriate
- **Feedback**: Visual feedback for touch interactions

---

## 🛡️ EXCEPTION HANDLING & DATA INTEGRITY

### Exception Handling
- **Pattern:** Always use try/catch for critical operations.
- **Logging:** Log errors with context (user, operation, etc).
- **User Feedback:** Show user-friendly error messages, never raw exceptions.

**Example:**
```php
public function criticalOperation(): void
{
    try {
        DB::beginTransaction();
        // ... business logic ...
        DB::commit();
        $this->notify('Operation successful!', 'success');
    } catch (Exception $e) {
        DB::rollBack();
        Log::error('Operation failed', [
            'error' => $e->getMessage(),
            'user_id' => auth()->id(),
        ]);
        $this->notify('An error occurred. Please try again.', 'error');
    }
}
```

### Data Integrity
- **Transactions:** Use database transactions for any operation that affects multiple tables or could leave data in an inconsistent state.
- **Validation:** Always validate input before processing.
- **Atomicity:** Never allow partial updates—commit only if all steps succeed.
- **Relationships:** Use Eloquent relationships and model events for cascading changes (e.g., deletes, updates).

**Example:**
```php
DB::transaction(function () {
    // All related DB changes here
});
```

---

## 🔐 SECURITY STANDARDS

### Input Validation
```php
// Always validate input
protected function rules(): array
{
    return [
        'email' => ['required', 'email', 'max:255'],
        'amount' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        'file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:10240'],
    ];
}
```

### CSRF Protection
```php
// All forms must include CSRF token
<form method="POST" action="{{ route('action') }}">
    @csrf
    <!-- Form fields -->
</form>
```

### Authorization
```php
// Check permissions at multiple levels
// 1. Route level
Route::middleware('permission:resource.action')->group(function () {});

// 2. Component level
public function mount(): void
{
    if (!Auth::user()->can('resource.action')) {
        abort(403);
    }
}

// 3. View level
@can('resource.action')
    <!-- Content -->
@endcan
```

### Data Sanitization
```php
// Always escape output
{{ $user->name }} // Safe
{!! $user->name !!} // Only when HTML is intentional

// Use prepared statements (Eloquent handles this)
User::where('email', $email)->first();
```

---

## 🚀 PERFORMANCE STANDARDS

### Database Optimization
```php
// Eager loading to prevent N+1
$users = User::with(['profile', 'posts'])->get();

// Use indexes for frequently queried fields
// Add database indexes in migrations

// Pagination for large datasets
$items = Item::paginate(25);
```

### Frontend Performance
```php
// Debounced search
wire:model.live.debounce.300ms="search"

// Lazy loading for heavy components
@livewire('heavy-component', [], ['lazy' => true])

// Optimistic updates
wire:model.live="field"
```

### Asset Optimization
```html
<!-- Use CDN for external libraries -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Defer non-critical JavaScript -->
<script src="script.js" defer></script>

<!-- Preload critical resources -->
<link rel="preload" href="critical.css" as="style">
```

---

## 📝 CODE QUALITY STANDARDS

### Naming Conventions
```php
// Classes: PascalCase
class UserController extends Controller {}

// Methods: camelCase
public function getUserData(): array {}

// Variables: camelCase
$userData = [];

// Constants: UPPER_SNAKE_CASE
const MAX_USERS = 1000;

// Database: snake_case
$table->string('user_name');
```

### Documentation
```php
/**
 * Get user statistics for dashboard
 *
 * @param int $userId
 * @return array
 * @throws UserNotFoundException
 */
public function getUserStats(int $userId): array
{
    // Implementation
}
```

### Testing
```php
// Feature tests for user workflows
public function test_user_can_create_purchase()
{
    $user = User::factory()->create();
    $this->actingAs($user);
    
    $response = $this->post('/purchases', [
        'supplier_id' => 1,
        'items' => [['item_id' => 1, 'quantity' => 5]]
    ]);
    
    $response->assertRedirect();
    $this->assertDatabaseHas('purchases', ['user_id' => $user->id]);
}
```

---

## 🔄 WORKFLOW STANDARDS

### Development Process
1. **Plan**: Understand requirements and existing patterns
2. **Design**: Follow established UI/UX patterns
3. **Implement**: Use established code patterns
4. **Test**: Verify functionality and user experience
5. **Review**: Check against these standards
6. **Deploy**: Ensure production readiness

### Code Review Checklist
- [ ] Follows established patterns
- [ ] Uses Bootstrap classes only
- [ ] Implements proper error handling
- [ ] Includes authorization checks
- [ ] Optimized for performance
- [ ] Responsive design
- [ ] Accessibility compliant
- [ ] Security measures in place

### Quality Gates
- **No Custom CSS**: Unless absolutely necessary
- **No Broken Layouts**: Must work on all screen sizes
- **No JavaScript Errors**: Console must be clean
- **No 404s**: All assets must load
- **No Security Vulnerabilities**: Follow security standards
- **No Performance Issues**: Meet performance benchmarks

---

## 🎨 UI CONSISTENCY RULES

### Design System
- **Colors**: Use Bootstrap theme colors only
- **Typography**: Inter font family
- **Spacing**: Bootstrap spacing utilities
- **Icons**: Bootstrap Icons (bi-*)
- **Shadows**: Minimal, flat design approach
- **Borders**: Simple, consistent border styles

### Component Consistency
- **Cards**: Always use `card border-0 shadow-sm`
- **Buttons**: Consistent sizing and styling
- **Forms**: Standard form layout and validation
- **Tables**: Responsive with hover effects
- **Modals**: Bootstrap modal components
- **Alerts**: Toastr for notifications

### Layout Consistency
- **Header**: Standard 2-row header pattern
- **Sidebar**: Fixed sidebar with navigation
- **Main Content**: Proper spacing and padding
- **Footer**: Minimal, clean footer design

---

## 🚨 CRITICAL RESTRICTIONS

### NEVER DO
- ❌ Add custom CSS classes
- ❌ Modify existing routes without approval
- ❌ Change permission system logic
- ❌ Use non-Bootstrap UI frameworks
- ❌ Skip error handling
- ❌ Ignore responsive design
- ❌ Skip authorization checks
- ❌ Use inline styles

### ALWAYS DO
- ✅ Follow established patterns
- ✅ Use Bootstrap classes
- ✅ Implement proper error handling
- ✅ Check user permissions
- ✅ Test responsive behavior
- ✅ Validate all inputs
- ✅ Log errors appropriately
- ✅ Use transactions for data integrity

---

## 📚 RESOURCES & REFERENCES

### Official Documentation
- [Laravel 12 Documentation](https://laravel.com/docs/12.x)
- [Livewire 3.6 Documentation](https://livewire.laravel.com/docs)
- [Bootstrap 5.3 Documentation](https://getbootstrap.com/docs/5.3/)
- [Alpine.js Documentation](https://alpinejs.dev/)

### Project-Specific Resources
- Existing component patterns in `app/Livewire/`
- UI components in `resources/views/components/`
- Theme configuration in `public/css/theme/`
- Route definitions in `routes/web.php`

---

**Remember**: These rules exist to maintain the high quality and consistency achieved so far. Every line of code should contribute to a system that is robust, maintainable, and delightful to use.
