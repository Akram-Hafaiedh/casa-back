<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'task_id',
        'content',
        'project_id', // If comment is related to a project, this field is required.
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project() : BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // public function replies()
    // {
    //     return $this->hasMany(Reply::class);
    // }
    // public function notifications()
    // {
    //     return $this->morphMany(Notification::class, 'notifiable');
    // }
}
