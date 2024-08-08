<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Repositories\MessageRepository;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    protected $messageRepository;

    public function __construct(MessageRepository $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    public function editMessage(Request $request, Message $message)
    {
        return $this->messageRepository->editMessage($request, $message);
    }

    public function getMessages(Request $request)
    {
        return $this->messageRepository->getMessages($request);
    }

    public function getMessage(Message $message)
    {
        return $this->messageRepository->getMessage($message);
    }

    public function sendMessage(Request $request)
    {
        return $this->messageRepository->sendMessage($request);
    }

    public function readMessages(Request $request)
    {
        return $this->messageRepository->readMessages($request);
    }
}
