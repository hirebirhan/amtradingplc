# Notification Standards

## Overview
The application uses a standardized notification system to provide consistent user feedback across all Livewire components.

## Implementation

### HasNotifications Trait
All Livewire components should use the `HasNotifications` trait for consistent toast notifications:

```php
<?php

namespace App\Livewire\YourComponent;

use App\Traits\HasNotifications;
use Livewire\Component;

class YourComponent extends Component
{
    use HasNotifications;
    
    public function someAction()
    {
        try {
            // Your business logic here
            
            $this->notifySuccess('Operation completed successfully!');
        } catch (\Exception $e) {
            $this->notifyError('Operation failed: ' . $e->getMessage());
        }
    }
}
```

### Available Methods

#### Basic Notification
```php
$this->notify('Your message here', 'info'); // Default type is 'info'
```

#### Specific Type Methods
```php
$this->notifySuccess('Success message');
$this->notifyError('Error message');
$this->notifyWarning('Warning message');
$this->notifyInfo('Info message');
```

### Notification Types
- **success**: Green toast for successful operations
- **error**: Red toast for errors and failures
- **warning**: Yellow/orange toast for warnings
- **info**: Blue toast for informational messages

## Migration from Session Flash

### ❌ Old Pattern (Inconsistent)
```php
// DON'T use session()->flash() for notifications
session()->flash('success', 'Operation completed');
session()->flash('error', 'Something went wrong');
session()->flash('message', 'Generic message');
```

### ✅ New Pattern (Consistent)
```php
// DO use the HasNotifications trait
$this->notifySuccess('Operation completed');
$this->notifyError('Something went wrong');
$this->notifyInfo('Generic message');
```

## Frontend Integration

The notification system works with:
- **Toastr.js**: For toast notifications
- **Livewire Events**: Components dispatch 'notify' events
- **Auto-handling**: Layout automatically handles session flash messages

### JavaScript Event Listener
```javascript
// Automatically handled in layouts/app.blade.php
Livewire.on('notify', (data) => {
    if (toastr && toastr[data.type]) {
        toastr[data.type](data.message);
    }
});
```

## Best Practices

### 1. Use Appropriate Types
- **Success**: For completed operations, saved data, successful updates
- **Error**: For failures, validation errors, exceptions
- **Warning**: For non-critical issues, confirmations needed
- **Info**: For status updates, informational messages

### 2. Message Guidelines
- Keep messages concise and user-friendly
- Avoid technical jargon in user-facing messages
- Include relevant context (amounts, names, etc.)
- Use consistent tone and language

### 3. Examples

#### Good Messages
```php
$this->notifySuccess('Payment of ETB 1,500.00 recorded successfully.');
$this->notifyError('Credit is already fully paid. No additional payments can be made.');
$this->notifyWarning('You are paying the full amount. Consider using closing payment option.');
$this->notifyInfo('Credit data refreshed at 14:30:25');
```

#### Bad Messages
```php
$this->notifyError('DB constraint violation'); // Too technical
$this->notifySuccess('OK'); // Too vague
$this->notifyError('Error: ' . $e->getMessage()); // Raw exception message
```

## Validation vs Notifications

### Validation Errors
Use `$this->addError()` for form validation errors that should appear next to form fields:

```php
$this->addError('amount', 'Payment amount cannot exceed credit balance.');
```

### User Feedback
Use notifications for operation results, status updates, and general user feedback:

```php
$this->notifySuccess('Payment recorded successfully!');
```

## Component Examples

### Credit Payment Component
```php
public function store()
{
    try {
        // Validate input
        $this->validate();
        
        // Process payment
        $result = $this->processPayment();
        
        if ($result['success']) {
            $this->notifySuccess('Payment recorded successfully!');
            return redirect()->route('credits.show', $this->credit->id);
        } else {
            $this->notifyError($result['message']);
        }
    } catch (\Exception $e) {
        $this->notifyError('Failed to process payment. Please try again.');
    }
}
```

### Bulk Operations
```php
public function bulkDelete()
{
    try {
        $count = $this->deleteSelectedItems();
        $this->notifySuccess("Successfully deleted {$count} items.");
    } catch (\Exception $e) {
        $this->notifyError('Failed to delete items. Please try again.');
    }
}
```

## Testing Notifications

### Manual Testing
1. Trigger the action in browser
2. Check that toast appears with correct type and message
3. Verify toast disappears after timeout
4. Ensure no duplicate notifications

### Automated Testing
```php
public function test_payment_shows_success_notification()
{
    $this->actingAs($user)
        ->livewire(CreditPayment\Create::class, ['credit' => $credit])
        ->set('amount', 100)
        ->call('store')
        ->assertDispatched('notify', function ($data) {
            return $data['type'] === 'success' && 
                   str_contains($data['message'], 'Payment recorded');
        });
}
```

## Migration Checklist

When updating existing components:

1. ✅ Add `use HasNotifications` trait
2. ✅ Replace `session()->flash()` calls with `$this->notify*()`
3. ✅ Remove session flash displays from Blade templates
4. ✅ Test all notification scenarios
5. ✅ Update any JavaScript that handles session messages
6. ✅ Verify no duplicate notifications appear

## Troubleshooting

### Notifications Not Appearing
- Check browser console for JavaScript errors
- Verify Toastr.js is loaded
- Ensure Livewire event listeners are active
- Check for conflicting CSS that might hide toasts

### Duplicate Notifications
- Remove session flash message displays from templates
- Ensure only one notification method is used per action
- Check for multiple event listeners

### Wrong Notification Type
- Verify you're using the correct method (`notifySuccess`, `notifyError`, etc.)
- Check the message content matches the notification type
- Ensure consistent color coding (green=success, red=error, etc.) 