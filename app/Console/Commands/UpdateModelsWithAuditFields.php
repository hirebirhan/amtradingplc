<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UpdateModelsWithAuditFields extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'models:add-audit-fields 
                           {--dry-run : Show what would be changed without making changes}
                           {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add audit fields and relationships to all models';

    protected $dryRun = false;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->dryRun = $this->option('dry-run');
        
        if ($this->dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        if (!$this->option('force') && !$this->dryRun) {
            if (!$this->confirm('⚠️  This will modify model files. Continue?')) {
                $this->info('Operation cancelled.');
                return;
            }
        }

        $this->info('🔧 Adding audit fields to models...');
        $this->newLine();

        $models = [
            'Category',
            'Sale', 
            'Purchase',
            'Transfer',
            'Credit',
            'Customer',
            'Supplier',
            'Stock',
            'Branch',
            'Warehouse',
            'User',
        ];

        foreach ($models as $model) {
            $this->updateModel($model);
        }

        $this->newLine();
        $this->info('✅ Model updates completed!');
    }

    protected function updateModel($modelName)
    {
        $modelPath = app_path("Models/{$modelName}.php");
        
        if (!File::exists($modelPath)) {
            $this->error("  ❌ Model not found: {$modelName}");
            return;
        }

        $content = File::get($modelPath);
        $originalContent = $content;

        // Add audit fields to fillable array
        $content = $this->addAuditFieldsToFillable($content);
        
        // Add audit relationships
        $content = $this->addAuditRelationships($content);

        if ($content !== $originalContent) {
            if (!$this->dryRun) {
                File::put($modelPath, $content);
            }
            $this->line("  ✓ Updated {$modelName} model");
        } else {
            $this->line("  ↪ {$modelName} model already up to date");
        }
    }

    protected function addAuditFieldsToFillable($content)
    {
        // Pattern to match the fillable array
        $pattern = '/protected\s+\$fillable\s*=\s*\[(.*?)\];/s';
        
        if (preg_match($pattern, $content, $matches)) {
            $fillableContent = $matches[1];
            
            // Check if audit fields are already present
            if (strpos($fillableContent, 'created_by') !== false) {
                return $content; // Already has audit fields
            }
            
            // Add audit fields to the end of the fillable array
            $auditFields = "        'created_by',\n        'updated_by',\n        'deleted_by',";
            
            // Remove trailing comma and whitespace, then add audit fields
            $fillableContent = rtrim(trim($fillableContent), ',');
            $fillableContent .= ",\n" . $auditFields;
            
            $newFillable = "protected \$fillable = [\n{$fillableContent}\n    ];";
            
            return preg_replace($pattern, $newFillable, $content);
        }
        
        return $content;
    }

    protected function addAuditRelationships($content)
    {
        // Check if audit relationships already exist
        if (strpos($content, 'public function creator()') !== false) {
            return $content; // Already has audit relationships
        }

        // Add audit relationships before the closing class brace
        $auditRelationships = '
    /**
     * Get the user who created this record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, \'created_by\');
    }

    /**
     * Get the user who last updated this record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, \'updated_by\');
    }

    /**
     * Get the user who deleted this record.
     */
    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, \'deleted_by\');
    }';

        // Find the last closing brace of the class
        $lastBracePos = strrpos($content, '}');
        
        if ($lastBracePos !== false) {
            // Insert audit relationships before the last closing brace
            $content = substr_replace($content, $auditRelationships . "\n", $lastBracePos, 0);
            
            // Make sure we have the proper imports
            $content = $this->addImportsIfNeeded($content);
        }

        return $content;
    }

    protected function addImportsIfNeeded($content)
    {
        // Check if BelongsTo is already imported
        if (strpos($content, 'use Illuminate\Database\Eloquent\Relations\BelongsTo;') === false) {
            // Find the last use statement
            $lines = explode("\n", $content);
            $lastUseIndex = -1;
            
            foreach ($lines as $index => $line) {
                if (strpos($line, 'use ') === 0) {
                    $lastUseIndex = $index;
                }
            }
            
            if ($lastUseIndex !== -1) {
                // Insert the BelongsTo import after the last use statement
                array_splice($lines, $lastUseIndex + 1, 0, 'use Illuminate\Database\Eloquent\Relations\BelongsTo;');
                $content = implode("\n", $lines);
            }
        }

        // Check if User model is imported
        if (strpos($content, 'use App\Models\User;') === false) {
            // Find the last use statement
            $lines = explode("\n", $content);
            $lastUseIndex = -1;
            
            foreach ($lines as $index => $line) {
                if (strpos($line, 'use ') === 0) {
                    $lastUseIndex = $index;
                }
            }
            
            if ($lastUseIndex !== -1) {
                // Insert the User import after the last use statement
                array_splice($lines, $lastUseIndex + 1, 0, 'use App\Models\User;');
                $content = implode("\n", $lines);
            }
        }

        return $content;
    }
}
