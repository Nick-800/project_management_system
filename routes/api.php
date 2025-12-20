<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\AttachmentController;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    // Projects CRUD & filters
    Route::get('projects', [ProjectController::class, 'index']);
    Route::post('projects', [ProjectController::class, 'store'])->middleware('permission:manage_projects');
    Route::get('projects/{project}', [ProjectController::class, 'show']);
    Route::put('projects/{project}', [ProjectController::class, 'update'])->middleware('permission:manage_projects');
    Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->middleware('permission:manage_projects');
    Route::post('projects/{id}/restore', [ProjectController::class, 'restore'])->middleware('permission:manage_projects');

    // Member management
    Route::post('projects/{project}/members', [ProjectController::class, 'addMember'])->middleware('permission:manage_projects');
    Route::delete('projects/{project}/members', [ProjectController::class, 'removeMember'])->middleware('permission:manage_projects');

    // Tasks CRUD & filters
    Route::get('tasks', [TaskController::class, 'index']);
    Route::post('tasks', [TaskController::class, 'store'])->middleware('permission:manage_tasks');
    Route::get('tasks/{task}', [TaskController::class, 'show']);
    Route::put('tasks/{task}', [TaskController::class, 'update'])->middleware('permission:manage_tasks');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->middleware('permission:manage_tasks');
    Route::post('tasks/{id}/restore', [TaskController::class, 'restore'])->middleware('permission:manage_tasks');

    // Status updates
    Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus']);
    Route::post('tasks/bulk-status', [TaskController::class, 'bulkUpdateStatus']);

    // Comments (polymorphic: project/task)
    Route::post('comments', [CommentController::class, 'store']);
    Route::put('comments/{comment}', [CommentController::class, 'update']);
    Route::delete('comments/{comment}', [CommentController::class, 'destroy']);

    // Tags CRUD
    Route::get('tags', [TagController::class, 'index']);
    Route::post('tags', [TagController::class, 'store'])->middleware('permission:manage_tags');
    Route::get('tags/{tag}', [TagController::class, 'show']);
    Route::put('tags/{tag}', [TagController::class, 'update'])->middleware('permission:manage_tags');
    Route::delete('tags/{tag}', [TagController::class, 'destroy'])->middleware('permission:manage_tags');

    // Assign / unassign tags to tasks
    Route::post('tasks/{task}/tags/{tag}', [TagController::class, 'assignToTask'])->middleware('permission:manage_tasks|manage_tags');
    Route::delete('tasks/{task}/tags/{tag}', [TagController::class, 'unassignFromTask'])->middleware('permission:manage_tasks|manage_tags');

    // Attachments upload/delete (private storage)
    Route::post('attachments', [AttachmentController::class, 'store']);
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy']);
});
