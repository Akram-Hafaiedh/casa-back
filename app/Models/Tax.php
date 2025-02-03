<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tax extends Model
{
    protected $table = 'client_taxes';
    protected $guarded = [];

    protected $fillable = [
        'client_id',
        'name',
        'percentage',
        'type',
    ];


    public function client() : BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    // public function documents()
    // {
    //     return $this->morphMany(ClientDocument::class, 'documentable');
    // }
}
