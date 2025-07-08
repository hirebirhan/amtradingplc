<?php

namespace App\Models;

use App\Enums\Department;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'position',
        'department',
        'hire_date',
        'birth_date',
        'address',
        'emergency_contact',
        'emergency_phone',
        'user_id',
        'branch_id',
        'warehouse_id',
        'status',
        'employee_id',
        'notes',
        'base_salary',
        'allowance',
        'position_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'hire_date' => 'date',
        'birth_date' => 'date',
        'base_salary' => 'decimal:2',
        'allowance' => 'decimal:2',
        'department' => Department::class,
        'status' => Status::class,
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::creating(function ($employee) {
            if (empty($employee->employee_id)) {
                $employee->employee_id = static::generateEmployeeId();
            }
        });
    }

    /**
     * Generate a unique employee ID.
     */
    public static function generateEmployeeId(): string
    {
        $prefix = 'EMP';
        $year = date('Y');
        $lastEmployee = static::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastEmployee ? (int)substr($lastEmployee->employee_id, -4) + 1 : 1;
        
        return sprintf('%s%s%04d', $prefix, substr($year, -2), $sequence);
    }

    /**
     * Get the user associated with the employee.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the branch associated with the employee.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the warehouse associated with the employee.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the employee's full name.
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the employee's total salary.
     */
    public function getTotalSalaryAttribute()
    {
        return $this->base_salary + ($this->allowance ?? 0);
    }

    /**
     * Get the position record associated with the employee.
     */
    public function positionRecord()
    {
        return $this->belongsTo(Position::class, 'position_id');
    }
} 