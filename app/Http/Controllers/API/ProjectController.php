<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Comment;
use App\Models\Task;
use App\Models\TaskPriority;
use App\Models\TaskStatus;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $projects = Project::all()->load('members');

        return response()->json([
            'data' => ['projects' => $projects],
            'status' => ['code' => 200, 'message' => 'Projects retrieved successfully.']
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->all();
        $data['allowChanges'] =  ((boolean)$data['allowChanges']);
        
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'projectImage' => ['nullable', 'image', 'mimes:png,jpg,jpeg'],
            'description' => ['required', 'string'],
            'endDate' => ['required', 'date'],
            'budget' => ['required', 'numeric', 'min:0'],
            'budgetUsage' => ['required', 'string'],
            'budgetDescription' => ['required', 'string'],
            'allowChanges' => ['required', 'boolean'],
            'selectedType' => ['required', 'string' , 'max:255'],
            // Add more fields as needed...

            'team' => ['required', 'array'],
            // 'members.*' => ['required', 'string', 'exists:users,id'], // TODO: FIXME
            'firstTaskTitle' => ['required', 'string', 'min:3', 'max:255'],
            'firstTaskDescription' => ['required', 'string', 'max:255'],
            'firstTaskDueDate' => ['required', 'string', 'date'],
            'firstTaskAssignedTo' => ['required', 'string' ],
            // 'firstTaskAssignedTo' => ['required', 'string', 'exists:users,id'], //TODO: FIXME
            'firstTaskTags' => ['required', 'array'],
            'firstTaskTags.*' => ['required', 'string'],

        ]);

        if($validator->fails()){
            return response()->json([
                'status' => ['code' => 422, 'message' => 'Validation failed'],
                'errors' => $validator->errors()
            ]);
        }

        try{
            DB::beginTransaction();
            // Process project image
            if ($request->hasFile('projectImage') && $request->file('projectImage')->isValid()) {
                $projectImage = $request->file('projectImage');
                $imageName = time(). '.'. $projectImage->getClientOriginalExtension();
                $projectImage->move(public_path('images'), $imageName);
                $projectImagePath = asset('images/' . $imageName);
            } else {
                $projectImagePath = null;
            }
             
    
            // Create project

            $project = Project::create([
                'logo' => $projectImagePath,
                'type' => $data['selectedType'],
                'name' => $data['name'],
                'budget' => $data['budget'],
                'budget_usage' => $data['budgetUsage'],
                'description' => $data['description'],
                'due_date' => $data['endDate'],
                'allow_changes' => $data['allowChanges'],
                'budget_description' => $data['budgetDescription'],
            ]);
    
            // Create team members
            $teamMembers = $data['team'];
            foreach($teamMembers as $member){
                $member = User::find($member['id']); 
                $project->users()->attach($member->id);
            }
    
            // Create first task
            $firstTask = [
                'title' => $data['firstTaskTitle'],
                'description' => $data['firstTaskDescription'],
                'dueDate' => $data['firstTaskDueDate'],
                'assignedTo' => $data['firstTaskAssignedTo'],
                'tags' => $data['firstTaskTags'],
            ];
            $project->tasks()->create($firstTask);
            
    
            DB::commit();
            return response()->json(['status' => ['code' => 201, 'message' => 'Project created successfully.'], 'data' => ['project' => $project]]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => ['code' => 500, 'message' => 'Failed to create project.', 'error' => $e->getMessage()]]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(String $projectId): JsonResponse
    {
        $project = Project::with('users', 'tasks', 'client', 'users', 'tasks', 'comments')->find($projectId);
        if (!$project) {
            return response()->json(['status' => ['code' => 404, 'message' => 'Project not found.']]);
        }
        return response()->json([
            'data' => ['project' => $project],
           'status' => ['code' => 200, 'message' => 'Project retrieved successfully.']
        ]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $projectId)
    {
        $project = Project::find($projectId);
        if (!$project) {
            return response()->json(['status' => ['code' => 404, 'message' => 'Project not found.']]);
        }
        $data = $request->all();
        $validator = Validator::make($data, [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'budget' => 'nullable|numeric',
            'due_date' => 'nullable|date',
            'client_id' => 'nullable|exists:clients,id',
            'status_id' => 'sometimes|exists:project_statuses,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
               'status' => ['code' => 422, 'message' => 'Validation failed'],
                'errors' => $validator->errors()
            ]);
        }

        $project->update($data);

        return response()->json([
            'data' => ['project' => $project],
           'status' => ['code' => 200, 'message' => 'Project updated successfully.']
        ]);

    }
    public function destroy($projectId)
    {
        $project = Project::find($projectId);
        if (!$project) {
            return response()->json(['status' => ['code' => 404, 'message' => 'Project not found.']]);
        }
        $project->delete();
        return response()->json([
            'data' => null, 
            'status' => ['code' => 204, 'message' => 'Project deleted successfully.']
        ]);
    }

    public function getProjectTasks($projectId)
    {
        $tasks = Project::find($projectId)->tasks;
        return response()->json([
            'data' => ['tasks' => $tasks],
           'status' => ['code' => 200, 'message' => 'Tasks retrieved successfully.']
        ]);
    }
    public function storeTask(Request $request, String $projectId)
    {

        $data = $request->all();
        $project = Project::find($projectId);
        if (!$project) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Project not found.']
            ]);
        }
        $validator = Validator::make($data, [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'assigned_to' => 'nullable|exists:users,id',
            'status_id' => 'required|exists:task_statuses,id',
            'priority_id' => 'nullable|exists:task_priorities,id',
            'tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
               'status' => ['code' => 422, 'message' => 'Validation failed'],
                'errors' => $validator->errors()
            ]);
        }

        $tags = $data['tags'] ? json_encode($data['tags']) : null;

        $task = Task::create([
            'project_id' => $project->id,
            'created_by' => auth()->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'start_date' => $data['start_date'],
            'due_date' => $data['due_date'],
            'assigned_to' => $data['assigned_to'],
            'status' => $data['status_id'],
            'priority' => $data['priority_id'],
            'tags' => $tags,
        ]);

        return response()->json([
            'data' => ['task' => $task],
            'status' => ['code' => 201, 'message' => 'Task created successfully.']
        ]);
    }
    public function updateTask(Request $request, String $projectId, $taskId)
    {
        $data = $request->all();
        $project = Project::find($projectId);
        if (!$project) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Project not found.']
            ]);
        }
        $task = Task::find($taskId);
        if (!$task) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Task not found.']
            ]);
        }
        $validator = Validator::make($data, [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'assigned_to' => 'nullable|exists:users,id',
            'status_id' => 'required|exists:task_statuses,id',
            'priority_id' => 'nullable|exists:task_priorities,id',
            'tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
               'status' => ['code' => 422, 'message' => 'Validation failed'],
                'errors' => $validator->errors()
            ]);
        }

        $tags = $data['tags'] ? json_encode($data['tags']) : null;

        $task->update([
            'title' => $data['title'],
            'description' => $data['description'],
            'start_date' => $data['start_date'],
            'due_date' => $data['due_date'],
            'assigned_to' => $data['assigned_to'],
            'status' => $data['status_id'],
            'priority' => $data['priority_id'],
            'tags' => $tags,
        ]);

        return response()->json([
            'data' => ['task' => $task],
            'status' => ['code' => 200, 'message' => 'Task updated successfully.']
        ]);
    }
    public function destroyTask($projectId, $taskId)
    {
        $project = Project::find($projectId);
        if (!$project) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Project not found.']
            ]);
        }
        $task = Task::find($taskId);
        if (!$task) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Task not found.']
            ]);
        }
        $task->delete();
        return response()->json([
            'data' => null, 
            'status' => ['code' => 204, 'message' => 'Task deleted successfully.']
        ]);
    }
    public function getProjectComments($projectId)
    {
        $project = Project::find($projectId);
        if (!$project) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Project not found.']
            ]);
        }
        $comments = Comment::where('project_id', $project->id)
            ->with(['user'])
            ->get();

        return response()->json([
            'data' => ['comments' => $comments],
            'status' => ['code' => 200, 'message' => 'Comments retrieved successfully.']
        ]);
    }
    public function storeProjectComment(Request $request, String $projectId)
    {
        $data = $request->all();

        $project = Project::find($projectId);

        if (!$project) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Project not found.']
            ]);
        }
        $validator = Validator::make($data, [
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
               'status' => ['code' => 422, 'message' => 'Validation failed'],
                'errors' => $validator->errors()
            ]);
        }
        $comment = Comment::create([
            'comment' => $data['comment'],
            'user_id' => auth()->id,
            'project_id' => $project->id,
        ]);

        return response()->json([
            'data' => ['comment' => $comment],
            'status' => ['code' => 201, 'message' => 'Comment created successfully.']
        ]);
    }
    public function updateProjectComment(Request $request, String $projectId, $commentId)
    {
        $data = $request->all();
        $project = Project::find($projectId);
        if (!$project) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Project not found.']
            ]);
        }
        $comment = Comment::find($commentId);
        if (!$comment) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Comment not found.']
            ]);
        }
        $validator = Validator::make($data, [
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
               'status' => ['code' => 422, 'message' => 'Validation failed'],
                'errors' => $validator->errors()
            ]);
        }
        $comment->update([
            'comment' => $data['comment'],
        ]);

        return response()->json([
            'data' => ['comment' => $comment],
            'status' => ['code' => 200, 'message' => 'Comment updated successfully.']
        ]);
    }

    public function destroyProjectComment($projectId, $commentId)
    {
        $project = Project::find($projectId);
        if (!$project) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Project not found.']
            ]);
        }
        $comment = Comment::find($commentId);
        if (!$comment) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Comment not found.']
            ]);
        }
        $comment->delete();
        return response()->json([
            'data' => null, 
            'status' => ['code' => 204, 'message' => 'Comment deleted successfully.']
        ]);
    }

    public function getTaskStatuses()
    {
        $taskStatuses = TaskStatus::all();
        return response()->json([
            'data' => ['task_statuses' => $taskStatuses],
            'status' => ['code' => 200, 'message' => 'Task statuses retrieved successfully.']
        ]);
    }

    public function storeTaskStatus(Request $request)
    {
        $data = $request->all();

        $validator = Validator::make($data, [
            'name' =>'required|string|unique:task_statuses',
        ]);

        if ($validator->fails()) {
            return response()->json([
               'status' => ['code' => 400, 'message' => 'Validation failed'],
                'errors' => $validator->errors()
            ]);
        }
        $status = TaskStatus::create([
            'name' => $data['name'],
        ]);
        return response()->json([
            'data' => ['task_status' => $status],
            'status' => ['code' => 201, 'message' => 'Task status created successfully.']
        ]);
    }
    public function updateTaskStatus(Request $request, $statusId)
    {
        $data = $request->all();
        $status = TaskStatus::find($statusId);
        if (!$status) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Task status not found.']
            ]);
        }
        $validator = Validator::make($data, [
            'name' =>'required|string|unique:task_statuses,name,'.$statusId,
        ]);

        if ($validator->fails()) {
            return response()->json([
               'status' => ['code' => 422, 'message' => 'Validation failed'],
                'errors' => $validator->errors()
            ]);
        }
        $status->update([
            'name' => $data['name'],
        ]);
        return response()->json([
            'data' => ['task_status' => $status],
            'status' => ['code' => 200, 'message' => 'Task status updated successfully.']
        ]);
    }
    public function destroyTaskStatus($statusId)
    {
        $status = TaskStatus::find($statusId);
        if (!$status) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Task status not found.']
            ]);
        }
        $status->delete();
        return response()->json([
            'data' => null, 
            'status' => ['code' => 204, 'message' => 'Task status deleted successfully.']
        ]);
    }
    public function getTaskPriorities()
    {
        $taskPriorities = TaskPriority::all();
        return response()->json([
            'data' => ['task_priorities' => $taskPriorities],
            'status' => ['code' => 200, 'message' => 'Task priorities retrieved successfully.']
        ]);
    }

    public function storeTaskPriority(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'name' =>'required|string|unique:task_priorities,name',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:50',
        ]);
        if ($validator->fails()) {
            return response()->json([
               'status' => ['code' => 400, 'message' => 'Validation failed'],
                'errors' => $validator->errors()
            ]);
        }
        $priority = TaskPriority::create([
            'name' => $data['name'],
            'description' => $data['description']?? null,
            'color' => $data['color']?? null,
        ]);
        return response()->json([
            'data' => ['task_priority' => $priority],
            'status' => ['code' => 201, 'message' => 'Task priority created successfully.']
        ]);
    }
    public function updateTaskPriority(Request $request, $priorityId)
    {
        $data = $request->all();
        $priority = TaskPriority::find($priorityId);
        if (!$priority) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Task priority not found.']
            ]);
        }
        $validator = Validator::make($data, [
            'name' =>'required|string|unique:task_priorities,name,'.$priorityId,
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:50',
        ]);
        if ($validator->fails()) {
            return response()->json([
               'status' => ['code' => 422, 'message' => 'Validation failed'],
                'errors' => $validator->errors()
            ]);
        }
        $priority->update([
            'name' => $data['name'],
            'description' => $data['description']?? null,
            'color' => $data['color']?? null,
        ]);
        return response()->json([
            'data' => ['task_priority' => $priority],
            'status' => ['code' => 200, 'message' => 'Task priority updated successfully.']
        ]);
    }
    public function destroyTaskPriority($priorityId)
    {
        $priority = TaskPriority::find($priorityId);
        if (!$priority) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Task priority not found.']
            ]);
        }
        $priority->delete();
        return response()->json([
            'data' => null, 
            'status' => ['code' => 204, 'message' => 'Task priority deleted successfully.']
        ]);
    }

    public function getTaskComments($projectId, $taskId)
    {
        $project = Project::find($projectId);
        if (!$project) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Project not found.']
            ]);
        }
        $task = Task::find($taskId);
        if (!$task) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Task not found.']
            ]);
        }
        $comments = Comment::where('task_id', $task->id)
            ->where('project_id', $project->id)
            ->with(['user'])
            ->get();
        return response()->json([
            'data' => ['comments' => $comments],
            'status' => ['code' => 200, 'message' => 'Task comments retrieved successfully.']
        ]);
    }

    public function storeTaskComment(Request $request, $projectId, $taskId)
    {
        $data = $request->all();

        $project = Project::find($projectId);
        if (!$project) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Project not found.']
            ]);
        }
        $task = Task::find($taskId);
        if (!$task) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Task not found.']
            ]);
        }
        $validator = Validator::make($data, [
            'content' =>'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
               'status' => ['code' => 400, 'message' => 'Validation failed'],
                'errors' => $validator->errors()
            ]);
        }
        $comment = Comment::create([
            'task_id' => $task->id, 
            'project_id' => $project->id,
            'user_id' => auth()->id,
            'content' => $data['content'],
        ]);

        return response()->json([
            'data' => ['comment' => $comment],
            'status' => ['code' => 201, 'message' => 'Task comment created successfully.']
        ]);
    }
    public function updateTaskComment(Request $request, $projectId, $taskId, $commentId)
    {
        $data = $request->all();
        $project = Project::find($projectId);
        if (!$project) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Project not found.']
            ]);
        }
        $task = Task::find($taskId);
        if (!$task) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Task not found.']
            ]);
        }
        $comment = Comment::find($commentId);
        if (!$comment) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Comment not found.']
            ]);
        }
        $validator = Validator::make($data, [
            'content' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
               'status' => ['code' => 422, 'message' => 'Validation failed'],
                'errors' => $validator->errors()
            ]);
        }
        $comment->update([
            'content' => $data['content'],
        ]);
        return response()->json([
            'data' => ['comment' => $comment],
            'status' => ['code' => 200, 'message' => 'Task comment updated successfully.']
        ]);
    }
    public function destroyTaskComment($projectId, $taskId, $commentId)
    {
        $project = Project::find($projectId);
        if (!$project) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Project not found.']
            ]);
        }
        $task = Task::find($taskId);
        if (!$task) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Task not found.']
            ]);
        }
        $comment = Comment::find($commentId);
        if (!$comment) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Comment not found.']
            ]);
        }
        $comment->delete();
        return response()->json([
            'data' => null,
            'status' => ['code' => 204, 'message' => 'Task comment deleted successfully.']
        ]);
    }

    public function getProjectMembers($projectId)
    {
        $project = Project::with('members')->find($projectId);
        if (!$project) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Project not found.']
            ]);
        }
        return response()->json([
            'data' => ['members' => $project->members],
            'status' => ['code' => 200, 'message' => 'Project members retrieved successfully.']
        ]);
    }

    public function addProjectMember(Request $request, $projectId)
    {
        $data = $request->all();
        $project = Project::find($projectId);
        if (!$project) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Project not found.']
            ]);
        }
        $validator = Validator::make($data, [
            'user_id' =>'required|integer|exists:users,id',
            'role' =>'required|string|in:owner,member',
        ]);
        if ($validator->fails()) {
            return response()->json([
               'status' => ['code' => 400, 'message' => 'Validation failed'],
                'errors' => $validator->errors()
            ]);
        }
        $project->members()->attach($data['user_id'], ['role' => $data['role']]);

        return response()->json([
            'data' => null,
            'status' => ['code' => 201, 'message' => 'Project member added successfully.']
        ]);
    }

    public function updateProjectMember(Request $request, $projectId, $userId)
    {
        $data = $request->all();
        $project = Project::find($projectId);
        if (!$project) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Project not found.']
            ]);
        }
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'User not found.']
            ]);
        }
        $validator = Validator::make($data, [
            'role' =>'required|string|in:owner,member',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => ['code' => 400, 'message' => 'Validation failed'],
                'errors' => $validator->errors()
            ]);
        }
        $project->members()->updateExistingPivot($user->id, ['role' => $data['role']]);

        return response()->json([
            'data' => null,
            'status' => ['code' => 200, 'message' => 'Project member updated successfully.']
        ]);
        
    }
    public function removeProjectMember($projectId, $userId)
    {
        $project = Project::find($projectId);
        if (!$project) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'Project not found.']
            ]);
        }
        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'data' => null,
                'status' => ['code' => 404, 'message' => 'User not found.']
            ]);
        }
        $project->members()->detach($user->id);

        return response()->json([
            'data' => null,
            'status' => ['code' => 204, 'message' => 'Project member removed successfully.']
        ]);
    }
}
