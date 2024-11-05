<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientPortfolio extends Model
{
    public function client() : BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function insurances() : HasMany
    {
        return $this->hasMany(Insurance::class);
    }

    public function accoutings() : HasMany
    {
        return $this->hasMany(Accounting::class);
    }
    public function taxes() : HasMany
    {
        return $this->hasMany(Tax::class);
    }
}
