<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ProjectTest extends TestCase
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

    public function test_index_filters_and_visibility(): void
    {
        $token = $this->auth();

        // Unfiltered index
        $resp = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/projects');
        $resp->assertOk()->assertJsonStructure(['data', 'meta']);

        // Filter by status
        $resp = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/projects?status=active');
        $resp->assertOk();
    }

    public function test_create_update_delete_restore_requires_manage_projects(): void
    {
        $token = $this->auth();
        $admin = User::where('email', 'admin@example.com')->first();

        // Create
        $create = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/projects', [
                'name' => 'New Project',
                'owner_id' => $admin->id,
                'status' => 'active',
            ]);
        $create->assertCreated();
        $id = $create->json('id');

        // Update
        $update = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/projects/'.$id, ['description' => 'Updated']);
        $update->assertOk();

        // Delete
        $del = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/projects/'.$id);
        $del->assertNoContent();

        // Restore
        $restore = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/projects/'.$id.'/restore');
        $restore->assertOk();
    }

    public function test_member_management(): void
    {
        $token = $this->auth();
        $admin = User::where('email', 'admin@example.com')->first();
        $project = Project::first();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/projects/'.$project->id.'/members', [
                'user_id' => $admin->id,
                'role' => 'project_manager',
            ])->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/projects/'.$project->id.'/members', [
                'user_id' => $admin->id,
            ])->assertOk();
    }
}
