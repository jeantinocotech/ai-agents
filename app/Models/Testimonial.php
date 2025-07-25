<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $fillable = [
        'user_id',
        'author_name',
        'author_role',
        'content',
        'agent_id',
        'author_image',
        'is_approved',
        'is_featured',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'agent_id');
    }
    
}
