<?php

namespace App\Broadcasting;

use App\Models\Chat;
use App\Models\ChatParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ChatChannel
{
    /**
     * Create a new channel instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Authenticate the user's access to the channel.
     */
    public function join(User $user, int $id): array|bool
    {
        $participant = ChatParticipant::where(
            [
                'user_id' => $user->id,
            ],
            [
                'chat_id' => $id,
            ]
        )->first();

        return $participant !== null;
    }
}
