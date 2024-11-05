<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'birthday',
        'id_passport',
        'address',
        'city',
        'postal_code',
        'ahv_number',
        'phone',
        'documents',
        'password',
        'role_id',
        'email_verified_at',
        'password_reset_token',
        'password_reset_token_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'role_id',
        'remember_token',
        'password_reset_token',
        'password_reset_token_expires_at',
        'created_at',
        'updated_at',
        'email_verified_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birthday' => 'date',
        ];
    }

    public function role() {
        return $this->belongsTo(Role::class);
    }

    public function vacations() {
        return $this->hasMany(Vacation::class);
    }

    public function contract() {
        return $this->hasOne(Contract::class);
    }

    public function documents() {
        return $this->hasMany(UserDocument::class);
    }
}
