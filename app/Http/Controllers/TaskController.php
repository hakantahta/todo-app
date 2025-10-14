<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $tasks = Task::query()
            ->where('user_id', $user->id)
            ->orderByDesc('is_completed')
            ->orderBy('priority')
            ->orderBy('due_at')
            ->orderBy('sort_order')
            ->latest('id')
            ->get();

        return response()->json($tasks);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:5'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

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

    public function update(Request $request, Task $task): JsonResponse
    {
        $this->authorizeOwnership($task);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_completed' => ['nullable', 'boolean'],
            'due_at' => ['nullable', 'date'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:5'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $task->fill($validated);
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
