<?php

use App\Broadcasting\ChatChannel;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('chat.{chat_id}', ChatChannel::class);
