<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    /**
     * Handle the model "creating" event.
     */
    public function creating(Model $model): void
    {
        if (auth()->check() && $this->hasAuditFields($model, 'created_by')) {
            $model->created_by = auth()->id();
        }
    }

    /**
     * Handle the model "created" event.
     */
    public function created(Model $model): void
    {
        // Log activity for created records
        $this->logActivity($model, 'created');
    }

    /**
     * Handle the model "updating" event.
     */
    public function updating(Model $model): void
    {
        if (auth()->check() && $this->hasAuditFields($model, 'updated_by')) {
            $model->updated_by = auth()->id();
        }
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated(Model $model): void
    {
        // Log activity for updated records
        $this->logActivity($model, 'updated');
    }

    /**
     * Handle the model "deleting" event.
     */
    public function deleting(Model $model): void
    {
        if (auth()->check() && $this->hasAuditFields($model, 'deleted_by')) {
            $model->deleted_by = auth()->id();
            $model->save(); // Save the deleted_by field before deletion
        }
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        // Log activity for deleted records
        $this->logActivity($model, 'deleted');
    }

    /**
     * Handle the model "restored" event.
     */
    public function restored(Model $model): void
    {
        // Reset deleted_by when restored
        if ($this->hasAuditFields($model, 'deleted_by')) {
            $model->deleted_by = null;
            $model->save();
        }
        
        // Log activity for restored records
        $this->logActivity($model, 'restored');
    }

    /**
     * Check if the model has the specified audit field.
     */
    private function hasAuditFields(Model $model, string $field): bool
    {
        return in_array($field, $model->getFillable()) || 
               array_key_exists($field, $model->getAttributes()) ||
               $model->hasAttribute($field);
    }

    /**
     * Log activity for audit trail.
     */
    private function logActivity(Model $model, string $action): void
    {
        try {
            // Only log if we have an authenticated user
            if (!auth()->check()) {
                return;
            }

            $description = $this->generateDescription($model, $action);
            
            // Log using Spatie Activity Log if available
            if (class_exists('\Spatie\Activitylog\Models\Activity')) {
                activity()
                    ->performedOn($model)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'model_id' => $model->getKey(),
                        'model_type' => get_class($model),
                        'action' => $action,
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ])
                    ->log($description);
            }
            
        } catch (\Exception $e) {
            // Silently fail to avoid breaking the main operation
            \Log::warning('Audit logging failed: ' . $e->getMessage());
        }
    }

    /**
     * Generate a human-readable description for the activity.
     */
    private function generateDescription(Model $model, string $action): string
    {
        $modelName = class_basename($model);
        $identifier = $this->getModelIdentifier($model);
        
        $actionMessages = [
            'created' => "Created {$modelName} {$identifier}",
            'updated' => "Updated {$modelName} {$identifier}",
            'deleted' => "Deleted {$modelName} {$identifier}",
            'restored' => "Restored {$modelName} {$identifier}",
        ];
        
        return $actionMessages[$action] ?? "Performed {$action} on {$modelName} {$identifier}";
    }

    /**
     * Get a human-readable identifier for the model.
     */
    private function getModelIdentifier(Model $model): string
    {
        // Try common identifier fields
        $identifierFields = ['name', 'title', 'reference_code', 'sku', 'email', 'code'];
        
        foreach ($identifierFields as $field) {
            if ($model->hasAttribute($field) && !empty($model->{$field})) {
                return "'{$model->{$field}}'";
            }
        }
        
        // Fall back to ID
        return "#{$model->getKey()}";
    }
} 