<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $casts = [
        'tags' => 'array',
    ];

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'start_date',
        'due_date',
        'tags',
        'assigned_to',
        'created_by',
        'project_id',
    ];


    public function isUnassigned(): bool
    {
        return is_null($this->assigned_to);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignedTo(): ?BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }


    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TaskStatus::class, 'status');
    }

    public function priority() : ?BelongsTo
    {
        return $this->belongsTo(TaskPriority::class, 'priority');
    }
}

