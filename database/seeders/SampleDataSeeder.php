<?php

namespace Database\Seeders;

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => 'password']
        );

        // Ensure admin has role
        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        // Tags
        $bug = Tag::firstOrCreate(['slug' => 'bug'], ['name' => 'Bug']);
        $feature = Tag::firstOrCreate(['slug' => 'feature'], ['name' => 'Feature']);

        // Project
        $project = Project::firstOrCreate([
            'name' => 'Demo Project',
            'owner_id' => $admin->id,
        ], [
            'description' => 'Sample project for API demo',
            'status' => 'active',
        ]);

        // Add admin as a member (manager)
        $project->members()->syncWithoutDetaching([
            $admin->id => ['role' => 'project_manager'],
        ]);

        // Tasks
        $task1 = Task::firstOrCreate([
            'project_id' => $project->id,
            'title' => 'Set up repository',
        ], [
            'assignee_id' => $admin->id,
            'status' => 'in_progress',
            'priority' => 'high',
        ]);

        $task2 = Task::firstOrCreate([
            'project_id' => $project->id,
            'title' => 'Implement authentication',
        ], [
            'assignee_id' => $admin->id,
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        // Tag tasks
        $task1->tags()->syncWithoutDetaching([$bug->id, $feature->id]);
        $task2->tags()->syncWithoutDetaching([$feature->id]);

        // Comments
        Comment::firstOrCreate([
            'commentable_type' => Project::class,
            'commentable_id' => $project->id,
            'user_id' => $admin->id,
            'body' => 'Initial project setup complete.',
        ]);

        Comment::firstOrCreate([
            'commentable_type' => Task::class,
            'commentable_id' => $task2->id,
            'user_id' => $admin->id,
            'body' => 'Auth via Sanctum planned.',
        ]);

        // Attachments
        Attachment::firstOrCreate([
            'attachable_type' => Project::class,
            'attachable_id' => $project->id,
            'user_id' => $admin->id,
            'path' => 'attachments/demo.txt',
        ], [
            'original_name' => 'demo.txt',
            'mime_type' => 'text/plain',
            'size' => 12,
        ]);
    }
}
