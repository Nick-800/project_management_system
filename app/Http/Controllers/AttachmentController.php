<?php

namespace App\Http\Controllers;

use App\Http\Requests\Attachment\StoreAttachmentRequest;
use App\Models\Attachment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AttachmentController extends Controller
{
    public function store(StoreAttachmentRequest $request): JsonResponse
    {
        [$attachable, $project, $type, $id] = $this->resolveAttachable(
            $request->string('on')->toString(),
            (int) $request->input('id')
        );

        $user = $request->user();
        if (! $this->isProjectMember($user, $project)) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $file = $request->file('file');
        $original = $file->getClientOriginalName();
        $safeName = Str::uuid()->toString().'_'.Str::slug(pathinfo($original, PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();
        $dir = "attachments/{$type}/{$id}";
        $storedPath = Storage::disk('local')->putFileAs($dir, $file, $safeName);

        $attachment = $attachable->attachments()->create([
            'user_id' => $user->id,
            'path' => $storedPath,
            'original_name' => $original,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return response()->json($attachment->load('user'), Response::HTTP_CREATED);
    }

    public function destroy(Request $request, Attachment $attachment): JsonResponse
    {
        [$project] = $this->resolveProjectFromAttachment($attachment);

        $user = $request->user();
        // Deletion restricted to project owner/manager/admin
        if (! ($this->isProjectManagerOrOwner($user, $project) || $user->hasRole('admin') || $user->can('manage_projects'))) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        if ($attachment->path) {
            Storage::disk('local')->delete($attachment->path);
        }

        $attachment->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @return array{0: object, 1: Project, 2: string, 3: int}
     */
    protected function resolveAttachable(string $on, int $id): array
    {
        if ($on === 'project') {
            $project = Project::findOrFail($id);
            return [$project, $project, 'project', $id];
        }

        if ($on === 'task') {
            $task = Task::with('project')->findOrFail($id);
            return [$task, $task->project, 'task', $id];
        }

        abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Invalid attachment target');
    }

    /**
     * @return array{0: Project}
     */
    protected function resolveProjectFromAttachment(Attachment $attachment): array
    {
        if ($attachment->attachable_type === Project::class) {
            return [Project::findOrFail($attachment->attachable_id)];
        }

        if ($attachment->attachable_type === Task::class) {
            $task = Task::with('project')->findOrFail($attachment->attachable_id);
            return [$task->project];
        }

        abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Invalid attachable');
    }

    protected function isProjectMember(User $user, Project $project): bool
    {
        if ($project->owner_id === $user->id) {
            return true;
        }

        return $project->members()->where('users.id', $user->id)->exists();
    }

    protected function isProjectManagerOrOwner(User $user, Project $project): bool
    {
        if ($project->owner_id === $user->id) {
            return true;
        }

        return $project->members()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'project_manager')
            ->exists();
    }
}
