<?php

namespace App\Events;

use App\Http\Resources\Chat\ChatResource;
use App\Models\Chat;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ChatMessageDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageId;
    public $chat;
    public $userId;

    public function __construct(int $messageId, Chat $chat, int $userId)
    {
        $this->messageId = $messageId;
        $this->chat = $chat;
        $this->userId = $userId;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('ChatMessageEvent.' . $this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ChatMessageDeleted';
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->messageId,
            'chat' => new ChatResource($this->chat->load([
                'messages' => function ($query) {
                    $query->limit(25)
                        ->orderBy('created_at', 'desc')
                        ->with('creator');
                },
                'members.user',
            ])),
        ];
    }
}
