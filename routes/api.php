<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\VacationController;
use App\Http\Controllers\API\ClientController;
use App\Http\Controllers\API\SystemController;
use App\Http\Controllers\API\ContractController;
use App\Http\Controllers\API\FilesController;
use App\Http\Controllers\API\ProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('notify-me', [SystemController::class, 'notifyMe']);
Route::post('unsubscribe', [SystemController::class, 'unsubscribe']);
Route::get('subscribed-users', [SystemController::class, 'getSubscribedUsers']);


Route::post('auth/login', [AuthController::class, 'login'])->name('login');
Route::post('auth/set-password', [AuthController::class, 'setPassword']);
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/logout', [AuthController::class, 'logout']);
Route::post('refresh', [AuthController::class, 'refresh']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('users/me', [AuthController::class, 'me']);
    Route::get('users', [UserController::class, 'index']);
    // Route::get('users/employees', [UserController::class, 'employeesWithoutContract']);
    Route::get('users/{userId}', [UserController::class, 'show']);
    Route::post('users', [UserController::class, 'store']);
    Route::put('users/{userId}', [UserController::class, 'update']);
    Route::delete('users/{userId}', [UserController::class, 'delete']);

    Route::get('vacations', [VacationController::class, 'index']);
    Route::get('vacations/my-vacations', [VacationController::class, 'myVacations']);
    Route::post('vacations', [VacationController::class, 'store']);
    Route::put('vacations/{vacationId}', [VacationController::class, 'update']);
    Route::put('vacations/{vacationId}/status', [VacationController::class, 'updateStatus']);
    Route::delete('vacations/{vacationId}', [VacationController::class, 'destroy']);

    Route::get('customers', [ClientController::class, 'index']);
    Route::post('customers/create', [ClientController::class, 'store']);
    Route::get('customers/{customerId}', [ClientController::class, 'show']);
    Route::put('customers/{customerId}', [ClientController::class, 'update']);
    Route::delete('customers/{customerId}', [ClientController::class, 'destroy']);
    Route::get('customers/{customerId}/taxes/{taxId}', [ClientController::class, 'deleteClientTax']);
    
    Route::get('contracts', [ContractController::class, 'index']);
    Route::post('contracts', [ContractController::class, 'store']);
    Route::get('contracts/{contractId}', [ContractController::class, 'show']);
    Route::post('contracts/{contractId}', [ContractController::class, 'update']);
    Route::delete('contracts/{contractId}', [ContractController::class, 'destroy']);
    

    // Route::get('notifications', [AuthController::class, 'notifications']);
    // Route::put('notifications/{notificationId}', [AuthController::class, 'markNotificationAsRead']);

    Route::get('projects', [ProjectController::class, 'index']);
    Route::get('projects/{projectId}', [ProjectController::class, 'show']);
    Route::post('projects/create', [ProjectController::class, 'store']);
    Route::put('projects/{projectId}', [ProjectController::class, 'update']);
    Route::delete('projects/{projectId}', [ProjectController::class, 'destroy']);

    // project tasks
    Route::get('projects/{projectId}/tasks', [ProjectController::class, 'getProjectTasks']);
    Route::post('projects/{projectId}/tasks', [ProjectController::class, 'storeTask']);
    Route::put('projects/{projectId}/tasks/{taskId}', [ProjectController::class, 'updateTask']);
    Route::delete('projects/{projectId}/tasks/{taskId}', [ProjectController::class, 'destroyTask']);

    // Task comments
    Route::get('projects/{projectId}/tasks/{taskId}/comments', [ProjectController::class, 'getTaskComments']);
    Route::post('projects/{projectId}/tasks/{taskId}/comments', [ProjectController::class, 'storeTaskComment']);
    Route::put('projects/{projectId}/tasks/{taskId}/comments/{commentId}', [ProjectController::class, 'updateTaskComment']);
    Route::delete('projects/{projectId}/tasks/{taskId}/comments/{commentId}', [ProjectController::class, 'destroyTaskComment']);

    // project comments
    Route::get('projects/{projectId}/comments', [ProjectController::class, 'getProjectComments']);
    Route::post('projects/{projectId}/comments', [ProjectController::class, 'storeProjectComment']);
    Route::put('projects/{projectId}/comments/{commentId}', [ProjectController::class, 'updateProjectComment']);
    Route::delete('projects/{projectId}/comments/{commentId}', [ProjectController::class, 'destroyProjectComment']);

    // Task Statuses
    Route::get('task-statuses', [ProjectController::class, 'getTaskStatuses']);
    Route::post('task-statuses', [ProjectController::class, 'storeTaskStatus']);
    Route::put('task-statuses/{statusId}', [ProjectController::class, 'updateTaskStatus']);
    Route::delete('task-statuses/{statusId}', [ProjectController::class, 'destroyTaskStatus']);

    // Task Priorities
    Route::get('task-priorities', [ProjectController::class, 'getTaskPriorities']);
    Route::post('task-priorities', [ProjectController::class, 'storeTaskPriority']);
    Route::put('task-priorities/{priorityId}', [ProjectController::class, 'updateTaskPriority']);
    Route::delete('task-priorities/{priorityId}', [ProjectController::class, 'destroyTaskPriority']);

    Route::get('projects/{projectId}/members', [ProjectController::class, 'getProjectMembers']);
    Route::post('projects/{projectId}/members', [ProjectController::class, 'addProjectMember']);
    Route::put('projects/{projectId}/members/{userId}', [ProjectController::class, 'updateProjectMember']);
    Route::delete('projects/{projectId}/members/{userId}', [ProjectController::class, 'removeProjectMember']);

    Route::get('files', [FilesController::class, 'index']);
    Route::get('files/me', [FilesController::class, 'getAuthenicatedUserDocuments']);
    Route::get('files/{userId}', [FilesController::class, 'getUserDocuments']);
    Route::get('files/{customerId}', [FilesController::class, 'getCustomerDocuments']);
    Route::get('files/{projectId}', [FilesController::class, 'getProjectDocuments']);
    
    Route::get('files/{customerId}/{documentName}', [FilesController::class, 'downloadCustomerDocuments']);
    Route::get('files/{userId}/{category}/{documentName}', [FilesController::class, 'downloadUserFile']);
    Route::get('files/{documentName}', [FilesController::class, 'downloadProjectFile']);
});