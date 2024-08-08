<?php

namespace App\Interfaces;

use App\Models\Message;
use Illuminate\Http\Request;

interface MessageRepositoryInterface
{
    public function editMessage(Request $request, Message $message);
    public function sendMessage(Request $request);
    public function getMessages(Request $request);
    public function getMessage(Message $message);
    public function sendNotificationToOther(Message $message);
    public function readMessages(Request $request);
}
