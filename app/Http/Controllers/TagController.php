<?php

namespace App\Http\Controllers;

use App\Http\Requests\Tag\StoreTagRequest;
use App\Http\Requests\Tag\UpdateTagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TagController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Tag::query()
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%'));

        $data = $query->latest()->paginate(20);

        return response()->json([
            'data' => TagResource::collection($data),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'total' => $data->total(),
            ],
        ]);
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        $this->authorizeTagMutation($request->user());

        $payload = $request->validated();
        $payload['slug'] = $payload['slug'] ?? Str::slug($payload['name']);

        $tag = Tag::create($payload);

        return response()->json(new TagResource($tag), Response::HTTP_CREATED);
    }

    public function show(Tag $tag): JsonResponse
    {
        return response()->json(new TagResource($tag));
    }

    public function update(UpdateTagRequest $request, Tag $tag): JsonResponse
    {
        $this->authorizeTagMutation($request->user());

        $payload = $request->validated();
        if (! empty($payload['name']) && empty($payload['slug'])) {
            $payload['slug'] = Str::slug($payload['name']);
        }

        $tag->update($payload);

        return response()->json(new TagResource($tag));
    }

    public function destroy(Request $request, Tag $tag): JsonResponse
    {
        $this->authorizeTagMutation($request->user());

        $tag->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function assignToTask(Request $request, Task $task, Tag $tag): JsonResponse
    {
        $this->authorizeTaskTagMutation($request->user());

        $task->tags()->syncWithoutDetaching([$tag->id]);

        return response()->json(['message' => 'Tag assigned']);
    }

    public function unassignFromTask(Request $request, Task $task, Tag $tag): JsonResponse
    {
        $this->authorizeTaskTagMutation($request->user());

        $task->tags()->detach($tag->id);

        return response()->json(['message' => 'Tag unassigned']);
    }

    protected function authorizeTagMutation($user): void
    {
        if (! $user->can('manage_tags')) {
            abort(Response::HTTP_FORBIDDEN, 'Insufficient permissions');
        }
    }

    protected function authorizeTaskTagMutation($user): void
    {
        // Allow either task managers or tag managers to assign/unassign
        if (! $user->can('manage_tasks') && ! $user->can('manage_tags')) {
            abort(Response::HTTP_FORBIDDEN, 'Insufficient permissions');
        }
    }
}
