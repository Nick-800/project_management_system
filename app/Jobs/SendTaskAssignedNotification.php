<?php

namespace App\Jobs;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTaskAssignedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $taskId, public int $assigneeId)
    {
    }

    public function handle(): void
    {
        $task = Task::find($this->taskId);

        if (! $task) {
            return;
        }

        Log::info('Task assigned', [
            'task_id' => $this->taskId,
            'assignee_id' => $this->assigneeId,
            'title' => $task->title,
        ]);
    }
}
