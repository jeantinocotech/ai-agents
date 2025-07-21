<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'asaas_customer_id', // Adicione este campo
        'profile_photo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
        ];
    }
    
    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
    public function activePurchases()
    {
        return $this->hasMany(Purchase::class)->where('active', true);
    }

    public function hasActivePurchaseForAgent($agentId): bool
    {
        return $this->purchases()
            ->where('agent_id', $agentId)
            ->where('active', true)
            ->exists();
    }


}
