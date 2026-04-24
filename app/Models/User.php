<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'linkedin_url',
        'password',
        'asaas_customer_id', // Adicione este campo
        'profile_photo',
        'phone', 'cpf', 'cep', 'address', 'number', 'city', 'state',
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
            'tokens_next_renewal_at' => 'datetime',
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

    public function tokenTransactions()
    {
        return $this->hasMany(TokenTransaction::class);
    }

    public function tokenPackOrders()
    {
        return $this->hasMany(TokenPackOrder::class);
    }

    public function careerTrailProgress(): HasOne
    {
        return $this->hasOne(UserCareerTrailProgress::class);
    }

    public function userCvs(): HasMany
    {
        return $this->hasMany(UserCv::class);
    }
}
