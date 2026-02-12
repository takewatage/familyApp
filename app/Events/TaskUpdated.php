<?php

namespace App\Events;

use App\Dtos\Dok\TaskData;
use App\Models\Task;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Task   $task,
        public string $action = 'updated' // 'created', 'updated', 'deleted'
    )
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('family.' . $this->task->family_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'task.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'task' => TaskData::from($this->task->toArray()),
            'action' => $this->action,
        ];
    }
}
