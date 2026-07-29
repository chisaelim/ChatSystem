<?php

namespace App\Events;

use App\Http\Resources\Chat\ChatResource;
use App\Models\Chat;
use App\Models\ChatMessage;
use Illuminate\Queue\SerializesModels;
use App\Http\Resources\Chat\ChatMessageResource;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ChatMessageUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $chat;
    public $userId;

    public function __construct(ChatMessage $message, Chat $chat, int $userId)
    {
        $this->message = $message;
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
        return 'ChatMessageUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => new ChatMessageResource($this->message->load('creator')),
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
