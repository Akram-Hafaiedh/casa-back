<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{

    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'logo',
        'due_date',
        'is_private',
        'created_by',
        'budget',
        'type',
        'allow_changes',
        'budget_usage',
        'budget_description',
        'status',
        'client_id',
    ];

    public function projectStatus(): BelongsTo
    {
        return $this->belongsTo(ProjectStatus::class, 'status');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_users')
            ->withPivot('role') // Include the role in the pivot table
            ->withTimestamps();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function taskStatuses(): BelongsToMany
    {
        return $this->belongsToMany(TaskStatus::class, 'project_tasks_statuses', 'project_id', 'task_status_id');
    }

    public function comments() : HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function taskPriorities(): BelongsToMany
    {
        return $this->belongsToMany(TaskPriority::class, 'project_tasks_priorities', 'project_id', 'task_priority_id');
    }

    protected static function booted()
    {
        static::created(function (Project $project) {

            $defaultStatusIds = TaskStatus::whereIn('name', [
                'Pending',
                'In Progress',
                'Completed',
                'Archived'
            ])->pluck('id')->toArray();
            
            $project->taskStatuses()->sync($defaultStatusIds);

            $defaultPriorityIds = TaskPriority::whereIn('name', [
                'Urgent',
                'High',
                'Normal',
                'Low',
                'Very Low'
            ])->pluck('id')->toArray();

            $project->taskPriorities()->sync($defaultPriorityIds);
        });

        static::updated(function (Project $project) {
            if (!$project->taskStatuses->count()) {
                $defaultStatusIds = TaskStatus::whereIn('name', [
                    'Pending',
                    'In Progress',
                    'Completed',
                    'Archived'
                ])->pluck('id')->toArray();

                $project->taskStatuses()->sync($defaultStatusIds);
            }

            if (!$project->taskPriorities->count()) {
                $defaultPriorityIds = TaskPriority::whereIn('name', [
                    'Urgent',
                    'High',
                    'Normal',
                    'Low',
                    'Very Low'
                ])->pluck('id')->toArray();

                $project->taskPriorities()->sync($defaultPriorityIds);
            }
        });

    }
    
}
