# Windsurf Development Rules - Muhdin General Trading
## High-Quality, Maintainable Architecture Standards

### 🎯 CORE PRINCIPLES
- **Clean Architecture**: SOLID principles, separation of concerns, single responsibility
- **Maintainability First**: Code should be self-documenting and easily modifiable
- **User Experience Focus**: Every change should improve or maintain UX quality
- **Performance Conscious**: Optimize for both development speed and runtime performance
- **Security by Design**: Implement security measures from the ground up
- **ZERO TOLERANCE FOR ERRORS**: Livewire errors are completely unacceptable
- **MODEL VERIFICATION FIRST**: Always check model structure before using fields or relationships

### 🏗️ ARCHITECTURE STANDARDS

#### Laravel Foundation
- **PHP 8.2+** with strict typing: `declare(strict_types=1)`
- **Laravel 12** framework with modern features
- **PSR-12** coding standards religiously followed
- **Dependency Injection** over static calls
- **Service Container** for all dependencies

#### Livewire Component Structure - CRITICAL RULES
- **SINGLE ROOT ELEMENT**: Every Livewire component MUST have exactly ONE root HTML element
- **NO MULTIPLE ROOTS**: Never have multiple top-level elements in a Livewire view
- **WRAP ALL CONTENT**: All content must be wrapped in a single container (usually `<div>`)
- **MODALS AND SCRIPTS**: Must be inside the main root element, not outside
- **VALIDATION**: Always verify single root element before committing Livewire components
- **ERROR PREVENTION**: Multiple root elements cause fatal Livewire errors - NEVER ALLOW THIS
- **NAMING CONFLICTS**: Avoid naming conflicts between Blade views and Livewire components
- **VIEW NAMING**: Use descriptive names for Blade views (e.g., `bank-accounts-index.blade.php` instead of `bank-accounts.blade.php`)
- **COMPONENT ISOLATION**: Keep Livewire components in `app/Livewire/` and regular views in `resources/views/`

#### Livewire Component Template Structure
```blade
{{-- CORRECT: Single root element --}}
<div>
    {{-- All content goes here --}}
    <div class="header">...</div>
    <div class="content">...</div>
    <div class="footer">...</div>
    
    {{-- Modals and scripts inside root --}}
    <x-modal>...</x-modal>
    @push('scripts')...@endpush
</div>

{{-- WRONG: Multiple root elements --}}
<div>Content 1</div>
<div>Content 2</div>
<x-modal>...</x-modal>
```

#### Component Architecture
- **Livewire 3.6** for reactive components (NOT Vue.js)
- **Bootstrap 5.3** for UI framework (NOT TailwindCSS)
- **Alpine.js 3.14** for lightweight client-side interactions
- **Single Responsibility**: Each component handles ONE concern
- **Max 300 lines** per method, 800 lines per class

#### Code Organization Rules
```php
// Livewire Component Structure
class ExampleComponent extends Component
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

### 🎨 UI/UX STANDARDS

#### UI Enhancement Philosophy - BALANCED APPROACH REQUIRED
**🔑 KEY LESSON: Avoid Over-Engineering UI Changes**

##### Enhancement Balance Rules
- **Minimal Intervention**: Only modify what's necessary to achieve the goal
- **Functional First**: Prioritize functionality over visual complexity
- **Clean & Simple**: Follow YouTube Studio design principles - minimal, clean, professional
- **Incremental Changes**: Make small, focused improvements rather than complete overhauls
- **User Impact Priority**: Consider how changes affect existing user workflows
- **Performance Over Polish**: Maintain fast loading times and smooth interactions

##### UI Complexity Guidelines
- **Avoid**: Excessive animations, complex gradients, over-engineered hover effects
- **Prefer**: Clean backgrounds, subtle transitions, simple hover states
- **Color Scheme**: Use CSS custom properties for theme consistency
- **Spacing**: Rely on Bootstrap spacing utilities, minimal custom padding/margins
- **Icons**: Consistent sizing (24px standard), simple Font Awesome icons
- **Borders**: Simple borders, avoid complex box-shadows unless essential

##### When NOT to Enhance UI
- If current design is functional and clean
- When changes would impact existing user muscle memory
- If enhancement adds complexity without clear user benefit
- When simpler solutions achieve the same goal
- If changes require extensive custom CSS instead of Bootstrap utilities

#### CRITICAL RESTRICTIONS - STRICTLY FORBIDDEN
**🚨 NEVER MODIFY THESE SYSTEM COMPONENTS**

##### Route Modifications - ABSOLUTELY FORBIDDEN
- **NEVER add, remove, or modify any routes in routes/web.php**
- **NEVER change route names or route parameters**
- **NEVER suggest route modifications or "improvements"**
- **NEVER create new route definitions**
- **ALWAYS use existing routes exactly as defined**
- **Routes are system infrastructure - DO NOT TOUCH**

##### Permission System - HANDS OFF
- **NEVER add new permission checks with @can directives**
- **NEVER modify existing permission logic**
- **NEVER suggest permission-based navigation changes**
- **NEVER add role-based functionality**
- **Permissions are controlled by system administrators**
- **UI should work for all users regardless of permissions**

##### Custom Classes - BOOTSTRAP ONLY
- **NEVER create custom CSS classes**
- **NEVER modify existing custom classes**
- **ALWAYS use pure Bootstrap 5.3 classes**
- **NEVER add custom styles that aren't Bootstrap utilities**
- **Keep styling simple and Bootstrap-native**

#### Bootstrap 5 Guidelines - STRICT COMPLIANCE REQUIRED
- **Pure Bootstrap classes only** - no custom CSS unless absolutely necessary
- **Mobile-first responsive design** - test on mobile devices first
- **Consistent spacing**: use Bootstrap's spacing utilities (mt-3, mb-4, etc.)
- **Professional SaaS aesthetics**: clean, modern, business-focused
- **Accessibility**: proper ARIA labels, keyboard navigation, screen reader support

##### MANDATORY Bootstrap Layout Rules
- **Container Structure**: MUST use proper Bootstrap container/row/col structure
- **Flexbox Layout**: MUST use Bootstrap flex utilities (d-flex, justify-content, align-items)
- **Responsive Classes**: MUST test responsive breakpoints (sm, md, lg, xl, xxl)
- **Component Integrity**: MUST verify all Bootstrap components render correctly
- **Asset Loading**: MUST use CDN or verify local Bootstrap assets load

##### CRITICAL Asset Management Rules
- **NEVER use @vite() without confirming Vite is configured**
- **ALWAYS use {{ asset() }} helper for Laravel assets**
- **CDN First**: Use CDN links for external libraries (Bootstrap, Font Awesome)
- **Zero 404s**: All assets MUST load without errors
- **Console Clean**: No JavaScript errors in browser console

#### User Experience Patterns
- **Confirmation workflows** for destructive actions
- **Toast notifications** using Toastr for user feedback
- **Loading states** for all async operations
- **Error handling** with user-friendly messages
- **Progressive disclosure** - show complexity gradually

### 📊 DATA & VALIDATION

#### Model Standards
- **Eloquent relationships** properly defined with return types
- **Mutators/Accessors** for data transformation
- **Model events** for business logic automation
- **Validation rules** centralized in Form Requests
- **Database transactions** for data integrity

#### CRITICAL MODEL VERIFICATION RULES
**🚨 MANDATORY: ALWAYS CHECK MODELS BEFORE USING FIELDS**

##### Model Field Verification Process
1. **ALWAYS check model structure first** before using any field in queries
2. **Verify field existence** in `$fillable`, `$casts`, and database schema
3. **Check relationships** before using `whereHas`, `with`, or relationship methods
4. **Validate field types** to ensure proper casting and validation
5. **Test queries** with actual data before deploying

##### Common Field Verification Checklist
- [ ] **Field exists in model's `$fillable` array**
- [ ] **Field exists in database schema** (check migrations)
- [ ] **Field has proper casting** if needed (dates, booleans, etc.)
- [ ] **Relationship exists** before using `whereHas` or `with`
- [ ] **Field name is correct** (check for typos, case sensitivity)

##### Examples of Required Verification
```php
// WRONG: Using field without checking model
$warehouses = Warehouse::where('is_active', true)->get(); // Error if field doesn't exist

// CORRECT: Check model first, then use appropriate fields
// Check Warehouse model: no 'is_active' field exists
$warehouses = Warehouse::orderBy('name')->get(); // Use existing fields only

// CORRECT: Check Branch model: 'is_active' field exists
$branches = Branch::where('is_active', true)->orderBy('name')->get();
```

#### 🛡️ ERROR PREVENTION & NULL SAFETY - MANDATORY RULES

##### CRITICAL NULL CHECK REQUIREMENTS
- **ALWAYS check for null before accessing object properties**
- **ALWAYS verify array keys exist before accessing them**
- **ALWAYS validate service method returns before using them**
- **ALWAYS use null coalescing operator (??) for safe property access**
- **NEVER assume service methods return expected data types**

##### Service Usage Patterns
```php
// ❌ WRONG - No null check
$result = $service->method();
$data = $result->property; // Could fail if $result is null

// ✅ CORRECT - With null check
$result = $service->method();
if ($result && isset($result->property)) {
    $data = $result->property;
}

// ✅ CORRECT - Using null coalescing
$data = $result?->property ?? 'default';

// ✅ CORRECT - Array access with null check
$array = $service->getArray();
$value = $array['key'] ?? null;
```

##### Model Property Access Safety
```php
// ❌ WRONG - Direct property access
$name = $user->profile->name;

// ✅ CORRECT - Safe property access
$name = $user->profile?->name ?? 'Unknown';

// ✅ CORRECT - With relationship check
if ($user->profile) {
    $name = $user->profile->name;
}
```

##### Collection/Array Safety
```php
// ❌ WRONG - Direct array access
$first = $collection[0];

// ✅ CORRECT - Safe array access
$first = $collection->first() ?? null;
$first = $collection[0] ?? null;

// ✅ CORRECT - With count check
if ($collection->count() > 0) {
    $first = $collection->first();
}
```

##### Service Method Validation
```php
// ❌ WRONG - No validation
$service = new SomeService();
$data = $service->getData();

// ✅ CORRECT - With validation
$service = new SomeService();
$data = $service->getData();
if (!$data) {
    // Handle empty/null case
    return;
}
```

##### Database Query Safety
```php
// ❌ WRONG - Direct access
$user = User::find($id);
$name = $user->name;

// ✅ CORRECT - With null check
$user = User::find($id);
if (!$user) {
    // Handle not found case
    return;
}
$name = $user->name;
```

##### Livewire Property Safety
```php
// ❌ WRONG - Direct property access
public $data;
public function method() {
    $this->data->property;
}

// ✅ CORRECT - With initialization and checks
public $data = [];
public function method() {
    if (empty($this->data)) {
        $this->data = [];
    }
    $property = $this->data['property'] ?? null;
}
```

##### Service Return Type Validation
```php
// ❌ WRONG - Assume return type
$bankAccounts = $bankService->getBankAccountsForDropdown();
$firstAccount = $bankAccounts->first(); // Error if returns array

// ✅ CORRECT - Check return type first
$bankAccounts = $bankService->getBankAccountsForDropdown();
if (is_array($bankAccounts)) {
    $firstAccount = $bankAccounts[0] ?? null;
} elseif ($bankAccounts instanceof Collection) {
    $firstAccount = $bankAccounts->first();
}
```

##### Error Prevention Strategy
- **Never assume field existence** - always verify in model file
- **Check both `$fillable` and database schema** for complete picture
- **Use IDE autocomplete** to verify field names
- **Test queries in development** before production deployment
- **Add model verification to code review checklist**

#### Validation Approach
```php
// Centralized validation rules
protected function rules(): array
{
    return [
        'field' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users'],
    ];
}

// Custom validation messages
protected function messages(): array
{
    return [
        'field.required' => 'This field is required.',
    ];
}
```

### 🔧 DEVELOPMENT WORKFLOW

#### MANDATORY UI DELIVERY STANDARDS - ZERO TOLERANCE POLICY
**🚨 CRITICAL: NO BROKEN LAYOUTS OR MISSING COMPONENTS ALLOWED**

##### Pre-Delivery Validation Checklist (MANDATORY)
1. **Livewire Root Element Check**: MUST verify single root element in all Livewire components
2. **Visual Verification**: MUST test layout in browser before claiming completion
3. **Component Integrity**: ALL layout components (header, sidebar, main content) MUST be visible and functional
4. **Responsive Testing**: MUST test on mobile, tablet, and desktop breakpoints
5. **Asset Loading**: MUST verify all CSS, JS, and assets load without errors
6. **Browser Console**: MUST be error-free (no JavaScript errors or 404s)
7. **Layout Structure**: MUST verify Bootstrap grid and flexbox layouts render correctly

##### Delivery Quality Gates
1. **Before coding**: Review existing patterns in Purchases module
2. **During coding**: Follow established patterns from successful refactors  
3. **Pre-delivery**: MANDATORY browser testing and visual verification
4. **After coding**: Test user workflows, not just technical functionality
5. **Final check**: Screenshot verification that layout is professional and complete
6. **Code review**: Check against these rules before committing

#### UNACCEPTABLE DELIVERY PATTERNS - IMMEDIATE REJECTION
- ❌ Multiple root elements in Livewire components
- ❌ Missing header, sidebar, or main content sections
- ❌ Broken CSS or layout structure
- ❌ JavaScript errors in browser console
- ❌ Assets failing to load (404 errors)
- ❌ Responsive layout breaking on mobile/desktop
- ❌ Claims of "premium UI" without actual testing
- ❌ Delivering layouts that haven't been visually verified

#### Error Handling Pattern
```php
public function criticalOperation(): void
{
    try {
        DB::beginTransaction();
        
        // Business logic here
        
        DB::commit();
        $this->notify('Success message');
        
    } catch (Exception $e) {
        DB::rollBack();
        Log::error('Operation failed', ['error' => $e->getMessage()]);
        $this->notify('User-friendly error message', 'error');
    }
}
```

### 🚀 PERFORMANCE STANDARDS

#### Database Optimization
- **Eager loading** for relationships
- **Query optimization** - avoid N+1 problems
- **Indexing strategy** for frequently queried fields
- **Pagination** for large datasets
- **Caching** for expensive operations

#### Frontend Performance
- **Lazy loading** for heavy components
- **Debounced search** for user inputs
- **Optimized asset loading** via Vite
- **Minimal DOM manipulation** - let Livewire handle state

### 🔐 SECURITY PROTOCOLS

#### Data Protection
- **CSRF protection** on all forms
- **Input sanitization** and validation
- **SQL injection prevention** via Eloquent ORM
- **XSS protection** with proper escaping
- **Authorization checks** on all sensitive operations

#### Authentication & Authorization
- **Role-based access control** using Spatie Permission
- **Route protection** with middleware
- **API authentication** with Sanctum
- **Session management** best practices

### 📝 DOCUMENTATION STANDARDS

#### Code Documentation
- **PHPDoc blocks** for all public methods
- **Inline comments** for complex business logic
- **README files** for each major module
- **Changelog** for significant updates

#### Naming Conventions
- **Classes**: PascalCase (UserController)
- **Methods**: camelCase (getUserData)
- **Variables**: camelCase (userData)
- **Constants**: UPPER_SNAKE_CASE (MAX_USERS)
- **Database**: snake_case (user_profiles)

### 🧪 TESTING PHILOSOPHY

#### Testing Strategy
- **Feature tests** for user workflows
- **Unit tests** for business logic
- **Browser tests** for critical user paths
- **Test-driven development** for complex features

### 🌟 EXCELLENCE MARKERS

#### Code Quality Indicators
- ✅ **Self-documenting code** - readable without comments
- ✅ **Consistent patterns** - follows established project conventions
- ✅ **Error resilience** - graceful handling of edge cases
- ✅ **User-centric design** - prioritizes user experience
- ✅ **Maintainable architecture** - easy to modify and extend

#### Success Metrics
- **Code reviews pass** without major architectural concerns
- **User workflows complete** without confusion or errors
- **Performance benchmarks** meet or exceed expectations
- **Security audits** pass without critical vulnerabilities
- **Team velocity** increases due to code clarity

### 🚨 ACCOUNTABILITY & QUALITY ASSURANCE

#### Zero Tolerance Quality Policy
- **Livewire errors are UNACCEPTABLE** - single root element is mandatory
- **Broken deliveries are UNACCEPTABLE**
- **Every UI change MUST be browser-tested before delivery**
- **No excuses for missing components or broken layouts**
- **"Premium UI" claims require actual premium results**
- **User feedback indicates immediate quality failure - take it seriously**

#### Delivery Validation Protocol
1. **Livewire Check**: Verify single root element in all components
2. **Browser Test**: Open localhost and verify layout works
3. **Component Check**: Confirm header, sidebar, main content all visible
4. **Console Check**: No JavaScript errors or 404s
5. **Mobile Test**: Verify responsive behavior on mobile viewport
6. **Screenshot**: Take screenshot to confirm professional appearance
7. **Only then**: Claim work is complete

#### Quality Recovery Process
- **Immediate acknowledgment** of quality failure
- **Root cause analysis** of what went wrong
- **Fix implementation** with proper testing
- **Prevention measures** to avoid repeat failures
- **Updated rules** to prevent similar issues

---

**REMEMBER**: These rules exist to create code that is not just functional, but exceptional. Every line of code should contribute to a system that is robust, maintainable, and delightful to use. 

**CRITICAL**: Livewire errors, broken layouts, or claiming "premium UI" without proper testing is completely unacceptable and violates the core principles of professional development. 