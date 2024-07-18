<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use Illuminate\Http\Request;
use App\Repositories\ChatRepository;

class ChatController extends Controller
{
    protected $chatRepository;

    public function __construct(ChatRepository $authRepository)
    {
        $this->chatRepository = $authRepository;
    }

    public function getChat(Chat $chat)
    {
        return $this->chatRepository->getChat($chat);
    }

    public function getChats(Request $request)
    {
        return $this->chatRepository->getChats($request);
    }

    public function createChat(Request $request)
    {
        return $this->chatRepository->createChat($request);
    }

    public function deleteChat(Chat $chat)
    {
        return $this->chatRepository->deleteChat($chat);
    }
}
