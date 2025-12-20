<?php

namespace App\Http\Controllers;

use App\Http\Requests\Task\BulkUpdateTaskStatusRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Jobs\SendTaskAssignedNotification;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Task::query()
            ->with(['assignee', 'tags'])
            ->where(function ($q) use ($user) {
                $q->whereHas('project', function ($p) use ($user) {
                    $p->where('owner_id', $user->id)
                      ->orWhereHas('members', fn ($m) => $m->where('users.id', $user->id));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->string('priority')))
            ->when($request->filled('due_date_from'), fn ($q) => $q->whereDate('due_date', '>=', $request->date('due_date_from')))
            ->when($request->filled('due_date_to'), fn ($q) => $q->whereDate('due_date', '<=', $request->date('due_date_to')))
            ->when($request->filled('assignee_id'), fn ($q) => $q->where('assignee_id', $request->integer('assignee_id')))
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->integer('project_id')))
            ->when($request->filled('tag'), fn ($q) => $q->whereHas('tags', fn ($t) => $t->where('slug', $request->string('tag'))))
            ->when($request->boolean('with_trashed'), fn ($q) => $q->withTrashed());

        $data = $query->latest()->paginate(15);

        return response()->json([
            'data' => TaskResource::collection($data),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'total' => $data->total(),
            ],
        ]);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $this->authorizeMutation($request->user());

        $task = Task::create($request->validated());

        if ($task->assignee_id) {
            SendTaskAssignedNotification::dispatch($task->id, $task->assignee_id);
        }

        return response()->json(new TaskResource($task->load(['assignee', 'tags'])), Response::HTTP_CREATED);
    }

    public function show(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();

        if (! $this->canViewTask($user, $task)) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        return response()->json(new TaskResource($task->load(['assignee', 'tags'])));
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorizeMutation($request->user());

        $previousAssignee = $task->assignee_id;

        $task->update($request->validated());

        if ($task->assignee_id && $task->assignee_id !== $previousAssignee) {
            SendTaskAssignedNotification::dispatch($task->id, $task->assignee_id);
        }

        return response()->json(new TaskResource($task->load(['assignee', 'tags'])));
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        $this->authorizeMutation($request->user());

        $task->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $this->authorizeMutation($request->user());

        $task = Task::withTrashed()->findOrFail($id);
        $task->restore();

        return response()->json(new TaskResource($task->load(['assignee', 'tags'])));
    }

    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:todo,in_progress,done,blocked'],
        ]);

        $user = $request->user();

        if (! $user->can('manage_tasks') && $task->assignee_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $task->status = $request->string('status');
        $task->completed_at = $task->status === 'done' ? now() : null;
        $task->save();

        return response()->json(new TaskResource($task->load(['assignee', 'tags'])));
    }

    public function bulkUpdateStatus(BulkUpdateTaskStatusRequest $request): JsonResponse
    {
        $user = $request->user();
        $updates = $request->validated()['updates'];

        DB::transaction(function () use ($updates, $user) {
            foreach ($updates as $update) {
                $task = Task::find($update['id']);
                if (! $task) {
                    continue;
                }

                if (! $user->can('manage_tasks') && $task->assignee_id !== $user->id) {
                    continue;
                }

                $task->status = $update['status'];
                $task->completed_at = $task->status === 'done' ? now() : null;
                $task->save();
            }
        });

        return response()->json(['message' => 'Statuses updated']);
    }

    protected function authorizeMutation(User $user): void
    {
        if (! $user->can('manage_tasks')) {
            abort(Response::HTTP_FORBIDDEN, 'Insufficient permissions');
        }
    }

    protected function canViewTask(User $user, Task $task): bool
    {
        return $task->project->owner_id === $user->id
            || $task->project->members()->where('users.id', $user->id)->exists();
    }
}
