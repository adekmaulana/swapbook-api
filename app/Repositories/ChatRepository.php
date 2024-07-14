<?php

namespace App\Repositories;

use App\Facades\ResponseFormatter;
use App\Interfaces\ChatRepositoryInterface;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChatRepository implements ChatRepositoryInterface
{
    public function createChat(Request $request)
    {
        $userModel = get_class(new User());
        $validator = Validator::make(
            $request->all(),
            [
                'user_id' => 'required|exists:' . $userModel . ',id',
                'is_private' => 'nullable|boolean',
            ]
        );

        if ($validator->fails()) {
            return ResponseFormatter::error(
                422,
                $validator->errors(),
                true
            );
        }

        $data = $this->prepareCreateChat($request);
        if ($data['user_id'] === $data['other_user_id']) {
            return ResponseFormatter::error(
                422,
                'You cannot create a chat with yourself.',
                true
            );
        }

        $chat = $this->getPreviousChat($data['other_user_id']);
        if ($chat === null) {
            $chat = Chat::create($data['data']);
            $chat->participants()->createMany([
                ['user_id' => $data['user_id']],
                ['user_id' => $data['other_user_id']],
            ]);

            $chat->refresh()->load('lastMessage.user', 'participants.user');
            return ResponseFormatter::success(
                $chat,
                'Chat created successfully.'
            );
        }

        return ResponseFormatter::success(
            $chat->load('lastMessage.user', 'participants.user'),
            'Chat retrieved successfully.'
        );
    }

    private function getPreviousChat(int $otherUserId)
    {
        $userId = auth()->user()->id;
        return Chat::where('is_private', 1)
            ->whereHas('participants', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->whereHas('participants', function ($query) use ($otherUserId) {
                $query->where('user_id', $otherUserId);
            })
            ->first();
    }

    private function prepareCreateChat(Request $request)
    {
        $otherUserId = $request->user_id;
        unset($request['user_id']);
        $request['created_by'] = auth()->user()->id;

        return [
            'other_user_id' => $otherUserId,
            'user_id' => auth()->user()->id,
            'data' => $request->all(),
        ];
    }

    public function getChat(Chat $chat)
    {
        $chat->load('lastMessage.user', 'participants.user');
        return ResponseFormatter::success(
            $chat,
            'Chat retrieved successfully.'
        );
    }

    public function getChats(Request $request)
    {
        $isPrivate = 1;
        if ($request->has('is_private')) {
            $isPrivate = (int) $request->is_private;
        }

        $chats = Chat::where('is_private', $isPrivate)
            ->hasParticipant(auth()->user()->id)
            ->whereHas('messages')
            ->with('lastMessage.user', 'participants.user')
            ->latest('updated_at')
            ->get();

        if ($chats->isEmpty()) {
            return ResponseFormatter::success(
                messages: 'No chats found.'
            );
        }

        return ResponseFormatter::success(
            $chats,
            'Chats retrieved successfully.'
        );
    }

    public function deleteChat(Request $request)
    {
    }
}
