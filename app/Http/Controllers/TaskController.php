<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class TaskController extends Controller
{
    protected $filePath;

    public function __construct()
    {
        $this->filePath = storage_path('app/tasks.json');
    }

    public function index()
    {
        $tasks = $this->readTasks();
        return view('tasks.index', ['tasks' => $tasks]);
    }

    public function store(Request $request)
    {
        $tasks = $this->readTasks();
        $task = [
            'id' => count($tasks) + 1,
            'description' => $request->input('description'),
            'status' => 'todo',
            'createdAt' => now()->toIso8601String(),
            'updatedAt' => now()->toIso8601String(),
        ];
        $tasks[] = $task;
        $this->writeTasks($tasks);

        return redirect()->route('tasks.index');
    }

    public function update(Request $request, $id)
    {
        $tasks = $this->readTasks();
        foreach ($tasks as &$task) {
            if ($task['id'] == $id) {
                $task['description'] = $request->input('description');
                $task['status'] = $request->input('status');
                $task['updatedAt'] = now()->toIso8601String();
            }
        }
        $this->writeTasks($tasks);

        return redirect()->route('tasks.index');
    }

    public function destroy($id)
    {
        $tasks = $this->readTasks();
        $tasks = array_filter($tasks, function ($task) use ($id) {
            return $task['id'] != $id;
        });
        $this->writeTasks($tasks);

        return redirect()->route('tasks.index');
    }

    protected function readTasks()
    {
        if (!File::exists($this->filePath)) {
            File::put($this->filePath, json_encode([]));
        }
        return json_decode(File::get($this->filePath), true);
    }

    protected function writeTasks(array $tasks)
    {
        File::put($this->filePath, json_encode($tasks, JSON_PRETTY_PRINT));
    }
}
