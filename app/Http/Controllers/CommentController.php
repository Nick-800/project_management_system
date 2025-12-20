<?php

namespace App\Http\Controllers;

use App\Http\Requests\Comment\StoreCommentRequest;
use App\Http\Requests\Comment\UpdateCommentRequest;
use App\Models\Comment;
use App\Http\Resources\CommentResource;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request): JsonResponse
    {
        [$commentable, $project] = $this->resolveCommentable(
            $request->string('on')->toString(),
            (int) $request->input('id')
        );

        $user = $request->user();
        if (! $this->isProjectMember($user, $project)) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $comment = $commentable->comments()->create([
            'user_id' => $user->id,
            'body' => $request->string('body')->toString(),
        ]);

        return response()->json(new CommentResource($comment->load('user')), Response::HTTP_CREATED);
    }

    public function update(UpdateCommentRequest $request, Comment $comment): JsonResponse
    {
        [$project] = $this->resolveProjectFromComment($comment);

        $user = $request->user();
        $isManager = $this->isProjectManagerOrOwner($user, $project) || $user->can('manage_comments');
        $isAuthor = $comment->user_id === $user->id;

        if (! $this->isProjectMember($user, $project) || (! $isManager && ! $isAuthor)) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $comment->update($request->validated());

        return response()->json(new CommentResource($comment->load('user')));
    }

    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        [$project] = $this->resolveProjectFromComment($comment);

        $user = $request->user();

        // Deletion restricted to project owner/manager/admin
        if (! ($this->isProjectManagerOrOwner($user, $project) || $user->hasRole('admin') || $user->can('manage_comments'))) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $comment->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @return array{0: Model, 1: Project}
     */
    protected function resolveCommentable(string $on, int $id): array
    {
        if ($on === 'project') {
            $project = Project::findOrFail($id);
            return [$project, $project];
        }

        if ($on === 'task') {
            $task = Task::with('project')->findOrFail($id);
            return [$task, $task->project];
        }

        abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Invalid comment target');
    }

    /**
     * @return array{0: Project}
     */
    protected function resolveProjectFromComment(Comment $comment): array
    {
        if ($comment->commentable_type === Project::class) {
            return [Project::findOrFail($comment->commentable_id)];
        }

        if ($comment->commentable_type === Task::class) {
            $task = Task::with('project')->findOrFail($comment->commentable_id);
            return [$task->project];
        }

        abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Invalid commentable');
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
