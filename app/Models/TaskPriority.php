<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskPriority extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'description',
        'color'
    ];
    
    public function tasks() : HasMany
    {
        return $this->hasMany(Task::class);
    }
    
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_task_priorities', 'task_priority_id', 'project_id');
    }
}
