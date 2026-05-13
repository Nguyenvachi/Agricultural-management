<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Constants\LookupCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use SoftDeletes;

    /**
     * DB-first: bảng users dùng username + password_hash.
     * Laravel auth cần override để biết đúng field.
     */
    protected $table = 'users';

    // Không có remember_token column trong schema
    protected $rememberTokenName = null;

    protected $fillable = [
        'role_id',
        'agency_id',
        'username',
        'password_hash',
        'full_name',
        'phone',
        'is_active',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    // ── Laravel Auth: map password_hash → password interface ──────────
    /**
     * Override để Auth::attempt() hash và so sánh đúng field.
     */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    // ── Role helpers ──────────────────────────────────────────────────
    public function isAdmin(): bool
    {
        return $this->role?->code === LookupCode::USER_ADMIN;
    }

    public function isAgency(): bool
    {
        return $this->role?->code === LookupCode::USER_AGENCY;
    }

    public function isFarmer(): bool
    {
        return $this->role?->code === LookupCode::USER_FARMER;
    }

    public function isCustomer(): bool
    {
        return $this->role?->code === LookupCode::USER_CUSTOMER;
    }

    /** Role code nhanh để dùng trong blade/middleware */
    public function roleCode(): string
    {
        return $this->role?->code ?? '';
    }

    // ── Relationships ─────────────────────────────────────────────────
    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function role()
    {
        return $this->belongsTo(SysLookupValue::class, 'role_id');
    }

    public function orders()
    {
        return $this->hasMany(\App\Models\Order::class, 'user_id');
    }
}
