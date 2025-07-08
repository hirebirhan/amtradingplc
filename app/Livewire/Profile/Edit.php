<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

#[\Livewire\Attributes\Layout('layouts.app')]
class Edit extends Component
{
    use WithFileUploads;

    public User $user;

    public string $name = '';
    public string $email = '';
    public ?string $phone = null;
    public ?string $position = null;
    public ?string $branch_name = null;
    public ?string $warehouse_name = null;

    /** @var \Livewire\TemporaryUploadedFile|null */
    public $avatar = null;

    // Password fields
    public ?string $current_password = null;
    public ?string $new_password = null;
    public ?string $new_password_confirmation = null;

    public function mount(): void
    {
        $this->user = Auth::user();
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->phone = $this->user->phone ?? '';
        $this->position = $this->user->position ?? '';
        $this->branch_name = $this->user->branch ? $this->user->branch->name : null;
        $this->warehouse_name = $this->user->warehouse ? $this->user->warehouse->name : null;
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $this->user->id],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s-]{8,}$/'],
            'position' => ['nullable', 'string', 'max:50'],
            'avatar' => ['nullable', 'image', 'max:2048', 'dimensions:min_width=100,min_height=100'],
            'current_password' => ['nullable', 'required_with:new_password', 'current_password'],
            'new_password' => ['nullable', 'confirmed', PasswordRule::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised()],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Please enter a valid phone number (e.g., +251 91 234 5678)',
            'avatar.dimensions' => 'The avatar must be at least 100x100 pixels.',
            'avatar.max' => 'The avatar must not be larger than 2MB.',
            'position.max' => 'Position must not exceed 50 characters.',
        ];
    }

    public function saveProfile(): void
    {
        try {
            $this->validateOnly('name');
            $this->validateOnly('email');
            $this->validateOnly('phone');
            $this->validateOnly('position');
            $this->validateOnly('avatar');

            if ($this->avatar) {
                // Delete old avatar if exists
                if ($this->user->avatar && Storage::disk('public')->exists($this->user->avatar)) {
                    Storage::disk('public')->delete($this->user->avatar);
                }

                $path = $this->avatar->storePublicly('avatars', 'public');
                if (Schema::hasColumn('users', 'avatar')) {
                    $this->user->avatar = $path;
                }
            }

            // Only update fields that exist in the users table
            $updateData = [
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
            ];

            // Only add position if the column exists
            if (Schema::hasColumn('users', 'position')) {
                $updateData['position'] = $this->position;
            }

            $this->user->update($updateData);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Profile updated successfully.',
                'title' => 'Success'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions so they're handled by Livewire
            throw $e;
        } catch (\Exception $e) {
            Log::error('Profile update failed', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to update profile. Please try again.',
                'title' => 'Error'
            ]);
        }
    }

    public function changePassword(): void
    {
        try {
            $this->validateOnly('current_password');
            $this->validateOnly('new_password');

            if (!$this->new_password) {
                return;
            }

            $this->user->password = Hash::make($this->new_password);
            $this->user->save();

            // Reset password fields
            $this->reset(['current_password', 'new_password', 'new_password_confirmation']);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Password changed successfully.',
                'title' => 'Success'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions so they're handled by Livewire
            throw $e;
        } catch (\Exception $e) {
            Log::error('Password change failed', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage()
            ]);

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Failed to change password. Please try again.',
                'title' => 'Error'
            ]);
        }
    }

    public function render()
    {
        return view('livewire.profile.edit');
    }
} 