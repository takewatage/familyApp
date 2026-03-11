<?php

namespace App\Events;

use App\Dtos\Task\TaskCategoryData;
use App\Models\TaskCategory;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CategoryUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public TaskCategory $category,
        public string       $action = 'updated' // 'created', 'updated', 'deleted'
    )
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('family.' . $this->category->family_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'category.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'category' => TaskCategoryData::from($this->category->toArray()),
            'action'   => $this->action,
        ];
    }
}
