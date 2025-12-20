<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\ManageProjectMemberRequest;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Project::query()
            ->with(['owner'])
            ->withCount(['members', 'tasks'])
            ->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                  ->orWhereHas('members', fn ($m) => $m->where('users.id', $user->id));
            })
            ->when($request->filled('name'), fn ($q) => $q->where('name', 'like', '%'.$request->string('name').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('owner_id'), fn ($q) => $q->where('owner_id', $request->integer('owner_id')))
            ->when($request->filled('starts_at_from'), fn ($q) => $q->whereDate('starts_at', '>=', $request->date('starts_at_from')))
            ->when($request->filled('starts_at_to'), fn ($q) => $q->whereDate('starts_at', '<=', $request->date('starts_at_to')))
            ->when($request->filled('ends_at_from'), fn ($q) => $q->whereDate('ends_at', '>=', $request->date('ends_at_from')))
            ->when($request->filled('ends_at_to'), fn ($q) => $q->whereDate('ends_at', '<=', $request->date('ends_at_to')))
            ->when($request->boolean('with_trashed'), fn ($q) => $q->withTrashed());

        // Cache only when no filters are provided (simple index)
        if (! $request->query()) {
            $data = Cache::remember('projects:index:user:'.$user->id, 60, fn () => $query->latest()->paginate(15));
        } else {
            $data = $query->latest()->paginate(15);
        }

        return response()->json([
            'data' => ProjectResource::collection($data),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'total' => $data->total(),
            ],
        ]);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorizeMutation($request->user());

        $project = Project::create($request->validated());

        Cache::forget('projects:index:user:'.$request->user()->id);

        return response()->json(new ProjectResource($project->load(['owner'])), Response::HTTP_CREATED);
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        $user = $request->user();

        if ($project->owner_id !== $user->id && ! $project->members()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $project->load(['owner'])
            ->loadCount(['members', 'tasks']);

        return response()->json(new ProjectResource($project));
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorizeMutation($request->user());

        $project->update($request->validated());

        Cache::forget('projects:index:user:'.$request->user()->id);

        return response()->json(new ProjectResource($project->load(['owner'])));
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->authorizeMutation($request->user());

        $project->delete();

        Cache::forget('projects:index:user:'.$request->user()->id);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $this->authorizeMutation($request->user());

        $project = Project::withTrashed()->findOrFail($id);
        $project->restore();

        Cache::forget('projects:index:user:'.$request->user()->id);

        return response()->json(new ProjectResource($project->load(['owner'])));
    }

    public function addMember(ManageProjectMemberRequest $request, Project $project): JsonResponse
    {
        $this->authorizeMutation($request->user());

        $project->members()->syncWithoutDetaching([
            $request->integer('user_id') => ['role' => $request->string('role')->toString()],
        ]);

        return response()->json(['message' => 'Member added']);
    }

    public function removeMember(Request $request, Project $project): JsonResponse
    {
        $this->authorizeMutation($request->user());

        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $project->members()->detach($request->integer('user_id'));

        return response()->json(['message' => 'Member removed']);
    }

    protected function authorizeMutation(User $user): void
    {
        if (! $user->can('manage_projects')) {
            abort(Response::HTTP_FORBIDDEN, 'Insufficient permissions');
        }
    }
}
