<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Purchase extends Model
{

    use HasFactory;

    protected $fillable = [
        'user_id',
        'agent_id',
        'paused',
        'paused_at',
        'active',
        'asaas_subscription_id'
    ];
    
    protected $casts = [
        'paused_at' => 'datetime',
        'paused' => 'boolean',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
    
    public function events()
    {
        return $this->hasMany(PurchaseEvent::class);
    }

}

