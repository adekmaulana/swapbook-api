<?php

namespace App\Interfaces;

use App\Models\Message;
use Illuminate\Http\Request;

interface MessageRepositoryInterface
{
    public function sendMessage(Request $request);
    public function getMessages(Request $request);
    public function getMessage(Message $message);
    public function sendNotificationToOther(Message $message);
}
