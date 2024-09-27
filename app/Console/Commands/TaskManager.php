<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class TaskManager extends Command
{
    protected $signature = 'taskmanager {action} {--id=} {--description=} {--status=}';
    protected $description = 'Manage tasks from the command line';

    private $filePath = 'tasks.json';

    public function __construct()
    {
        parent::__construct();
        $this->createTasksFileIfNotExists();
    }

    public function handle()
    {
        $action = $this->argument('action');
        $id = $this->option('id');
        $description = $this->option('description');
        $status = $this->option('status');

        switch ($action) {
            case 'add':
                $this->addTask($description);
                break;
            case 'update':
                $this->updateTask($id, $description, $status);
                break;
            case 'delete':
                $this->deleteTask($id);
                break;
            case 'list':
                $this->listTasks();
                break;
            case 'list-completed':
                $this->listCompletedTasks();
                break;
            case 'list-not-completed':
                $this->listNotCompletedTasks();
                break;
            case 'list-in-progress':
                $this->listInProgressTasks();
                break;
            default:
                $this->error('Invalid action.');
                break;
        }
    }

    private function createTasksFileIfNotExists()
    {
        if (!File::exists($this->filePath)) {
            File::put($this->filePath, json_encode([]));
        }
    }

    private function getTasks()
    {
        return json_decode(File::get($this->filePath), true);
    }

    private function saveTasks($tasks)
    {
        File::put($this->filePath, json_encode($tasks, JSON_PRETTY_PRINT));
    }

    private function addTask($description)
    {
        $tasks = $this->getTasks();
        $id = count($tasks) + 1;
        $tasks[] = [
            'id' => $id,
            'description' => $description,
            'status' => 'todo',
            'createdAt' => Carbon::now()->toDateTimeString(),
            'updatedAt' => Carbon::now()->toDateTimeString()
        ];
        $this->saveTasks($tasks);
        $this->info('Task added.');
    }

    private function updateTask($id, $description, $status)
    {
        $tasks = $this->getTasks();
        foreach ($tasks as &$task) {
            if ($task['id'] == $id) {
                if ($description) $task['description'] = $description;
                if ($status) $task['status'] = $status;
                $task['updatedAt'] = Carbon::now()->toDateTimeString();
                $this->saveTasks($tasks);
                $this->info('Task updated.');
                return;
            }
        }
        $this->error('Task not found.');
    }

    private function deleteTask($id)
    {
        $tasks = $this->getTasks();
        $tasks = array_filter($tasks, function($task) use ($id) {
            return $task['id'] != $id;
        });
        $this->saveTasks(array_values($tasks));
        $this->info('Task deleted.');
    }

    private function listTasks()
    {
        $tasks = $this->getTasks();
        foreach ($tasks as $task) {
            $this->info("ID: {$task['id']}, Description: {$task['description']}, Status: {$task['status']}, Created At: {$task['createdAt']}, Updated At: {$task['updatedAt']}");
        }
    }

    private function listCompletedTasks()
    {
        $tasks = $this->getTasks();
        $completedTasks = array_filter($tasks, function($task) {
            return $task['status'] === 'done';
        });
        foreach ($completedTasks as $task) {
            $this->info("ID: {$task['id']}, Description: {$task['description']}, Status: {$task['status']}, Created At: {$task['createdAt']}, Updated At: {$task['updatedAt']}");
        }
    }

    private function listNotCompletedTasks()
    {
        $tasks = $this->getTasks();
        $notCompletedTasks = array_filter($tasks, function($task) {
            return $task['status'] === 'todo';
        });
        foreach ($notCompletedTasks as $task) {
            $this->info("ID: {$task['id']}, Description: {$task['description']}, Status: {$task['status']}, Created At: {$task['createdAt']}, Updated At: {$task['updatedAt']}");
        }
    }

    private function listInProgressTasks()
    {
        $tasks = $this->getTasks();
        $inProgressTasks = array_filter($tasks, function($task) {
            return $task['status'] === 'in-progress';
        });
        foreach ($inProgressTasks as $task) {
            $this->info("ID: {$task['id']}, Description: {$task['description']}, Status: {$task['status']}, Created At: {$task['createdAt']}, Updated At: {$task['updatedAt']}");
        }
    }
}
