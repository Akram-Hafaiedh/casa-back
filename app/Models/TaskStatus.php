<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskStatus extends Model
{
    protected $table = 'task_statuses';

    protected $fillable = [
        'name'
    ];
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'status');
    }
    
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_task_statuses', 'task_status_id', 'project_id');
    }
    
}
