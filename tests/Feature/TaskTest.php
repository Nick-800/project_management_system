<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class TaskTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh');
        Artisan::call('db:seed');
    }

    protected function auth(string $email = 'admin@example.com'): string
    {
        $login = $this->postJson('/api/auth/login', ['email' => $email, 'password' => 'password']);
        return $login->json('token');
    }

    public function test_filters_and_index(): void
    {
        $token = $this->auth();
        $project = Project::first();
        $admin = User::where('email', 'admin@example.com')->first();

        // Create sample tasks
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tasks', [
                'project_id' => $project->id,
                'title' => 'Task A',
                'status' => 'todo',
                'priority' => 'high',
                'assignee_id' => $admin->id,
            ])->assertCreated();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tasks', [
                'project_id' => $project->id,
                'title' => 'Task B',
                'status' => 'in_progress',
                'priority' => 'low',
            ])->assertCreated();

        // Filter by status and project
        $resp = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/tasks?status=todo&project_id='.$project->id);
        $resp->assertOk();

        // Filter by assignee
        $resp = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/tasks?assignee_id='.$admin->id);
        $resp->assertOk();
    }

    public function test_status_update_and_bulk(): void
    {
        $token = $this->auth();
        $task = Task::first();
        $admin = User::where('email', 'admin@example.com')->first();

        // Manager update
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/tasks/'.$task->id.'/status', ['status' => 'done'])
            ->assertOk();

        // Bulk
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tasks/bulk-status', [
                'updates' => [
                    ['id' => $task->id, 'status' => 'in_progress'],
                ],
            ])->assertOk();
    }

    public function test_restore_and_delete(): void
    {
        $token = $this->auth();
        $task = Task::first();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/tasks/'.$task->id)
            ->assertNoContent();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/tasks/'.$task->id.'/restore')
            ->assertOk();
    }
}
