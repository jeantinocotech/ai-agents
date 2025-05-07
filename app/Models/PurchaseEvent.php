<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseEvent extends Model
{
    protected $fillable = ['purchase_id', 'event_type', 'event_time', 'note'];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}
