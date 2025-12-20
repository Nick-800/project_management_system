<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CommentTest extends TestCase
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

    public function test_create_update_delete_comments(): void
    {
        $token = $this->auth();
        $project = Project::first();
        $task = Task::first();
        $admin = User::where('email', 'admin@example.com')->first();

        // Create comment on project
        $create = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/comments', ['on' => 'project', 'id' => $project->id, 'body' => 'Hello']);
        $create->assertCreated();
        $commentId = $create->json('id');

        // Update comment
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/comments/'.$commentId, ['body' => 'Updated'])
            ->assertOk();

        // Create comment on task
        $createTask = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/comments', ['on' => 'task', 'id' => $task->id, 'body' => 'Note']);
        $createTask->assertCreated();
        $taskCommentId = $createTask->json('id');

        // Delete comment (manager/admin allowed)
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/comments/'.$taskCommentId)
            ->assertNoContent();
    }
}
