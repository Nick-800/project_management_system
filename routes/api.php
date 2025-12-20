<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;

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
});
