<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientDocument extends Model
{
    protected $fillable = [
        'user_id',
        'client_id',
        'name',
        'path',
        'type',
        'documentable_id',  // ID of the related model (polymorphic)
        'documentable_type' // Type of the related model (polymorphic)
    ];

    public function documentable()
    {
        return $this->morphTo();
    }

    public function client() : BelongsTo
    {
        return $this->belongsTo(ClientPortfolio::class);
    }

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
