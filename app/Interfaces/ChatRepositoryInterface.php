<?php

namespace App\Interfaces;

use App\Models\Chat;
use Illuminate\Http\Request;

interface ChatRepositoryInterface
{
    public function createChat(Request $request);

    public function getChat(Chat $chat);

    public function getChats(Request $request);

    public function deleteChat(Chat $chat);
}
