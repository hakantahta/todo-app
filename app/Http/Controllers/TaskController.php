<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = Task::query()->where('user_id', $user->id);

        // Backend filtreleri: q (title), status (active|completed|all), priority (0-5)
        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where('title', 'like', "%{$search}%");
        }

        $status = (string) $request->query('status', 'all');
        if ($status === 'active') {
            $query->where('is_completed', false);
        } elseif ($status === 'completed') {
            $query->where('is_completed', true);
        }

        if ($request->filled('priority')) {
            $priority = (int) $request->query('priority');
            $query->where('priority', $priority);
        }

        $tasks = $query
            ->orderByDesc('is_completed')
            ->orderBy('priority')
            ->orderBy('due_at')
            ->orderBy('sort_order')
            ->latest('id')
            ->get();

        return response()->json($tasks);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $task = Task::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'due_at' => $validated['due_at'] ?? null,
            'priority' => $validated['priority'] ?? 0,
            'sort_order' => $validated['sort_order'] ?? null,
        ]);

        return response()->json($task, 201);
    }

    public function show(Task $task): JsonResponse
    {
        $this->authorizeOwnership($task);
        return response()->json($task);
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        $this->authorizeOwnership($task);

        $task->fill($request->validated());
        $task->save();

        return response()->json($task);
    }

    public function destroy(Task $task): JsonResponse
    {
        $this->authorizeOwnership($task);
        $task->delete();
        return response()->json(['deleted' => true]);
    }

    public function toggle(Task $task): JsonResponse
    {
        $this->authorizeOwnership($task);
        $task->is_completed = ! $task->is_completed;
        $task->save();
        return response()->json($task);
    }

    private function authorizeOwnership(Task $task): void
    {
        if ($task->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
