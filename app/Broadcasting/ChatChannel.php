<?php

namespace App\Broadcasting;

use App\Models\Chat;
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
    public function join(User $user, int $chat_id): array|bool
    {
        Log::info('Joining chat channel', ['user' => $user->id, 'chat' => $chat_id]);
        $chat = Chat::where('id', $chat_id)
            ->with(['participants' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->first();

        if ($chat === null) {
            return false;
        }

        return true;
    }
}
