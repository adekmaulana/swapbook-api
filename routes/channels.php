<?php

use App\Models\ChatParticipant;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('chat.{id}', function ($user, $id) {
    $participant = ChatParticipant::where(
        [
            'user_id' => $user->id,
        ],
        [
            'chat_id' => $id,
        ]
    )->first();

    return $participant !== null;
});
