<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'staff_id', 'designation', 'department', 'phone', 'employee_level', 'signature_data', 'override_enabled', 'override_enabled_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ==========================================
    // Role helpers
    // ==========================================

    public function isAdmission(): bool { return $this->role === 'admission'; }
    public function isStaff1(): bool    { return $this->role === 'staff1'; }
    public function isStaff2(): bool    { return $this->role === 'staff2'; }
    public function isAdmin(): bool     { return $this->role === 'admin'; }
    public function isAdmissions(): bool { return $this->isAdmission(); }

    // ==========================================
    // Permission helpers
    // ==========================================

    public function canAccessAdminPanel(): bool
    {
        return $this->isAdmin() || $this->role === 'staff2';
    }

    public function canManageRequestTypes(): bool
    {
        return $this->isAdmin();
    }

    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    public function canManageTemplates(): bool
    {
        return $this->isAdmin();
    }

    public function canExportData(): bool
    {
        return $this->isAdmin() || $this->isStaff2();
    }

    public function canOverrideRequests(): bool
    {
        return $this->isStaff2(); // Staff 2 keeps override capabilities
    }

    // ==========================================
    // Override system methods
    // ==========================================

    public function enableOverride(): void
    {
        $this->update([
            'override_enabled' => true,
            'override_enabled_at' => now(),
        ]);
    }

    public function disableOverride(): void
    {
        $this->update([
            'override_enabled' => false,
            'override_enabled_at' => null,
        ]);
    }

    public function toggleOverride(): void
    {
        if ($this->override_enabled) {
            $this->disableOverride();
        } else {
            $this->enableOverride();
        }
    }

    // ==========================================
    // Profile helpers
    // ==========================================

    public function hasCompleteProfile(): bool
    {
        return !empty($this->staff_id)
            && !empty($this->designation)
            && !empty($this->department)
            && !empty($this->phone);
    }

    public function displayName(): string
    {
        return $this->name . ($this->staff_id ? ' (' . $this->staff_id . ')' : '');
    }

    // ==========================================
    // Relationships
    // ==========================================

    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class)->latest('created_at');
    }
}
