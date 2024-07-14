<?php

namespace App\Repositories;

use App\Events\NewMessageSent;
use App\Facades\ResponseFormatter;
use App\Interfaces\MessageRepositoryInterface;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MessageRepository implements MessageRepositoryInterface
{
    protected $messaging;

    public function __construct()
    {
    }

    public function getMessages(Request $request)
    {
        $chatModel = get_class(new Chat());
        $validator = Validator::make($request->all(), [
            'chat_id' => 'required|exists:' . $chatModel . ',id',
            'page_size' => 'nullable|numeric',
            'page' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(
                422,
                $validator->errors(),
                true
            );
        }

        $pageSize = $request->page_size ?? 10;
        $messages = Message::where('chat_id', $request->chat_id)
            ->with('user')
            ->with('chat')
            ->latest('created_at')
            ->simplePaginate(
                $pageSize,
                ['*'],
                'page',
                $request->page
            );

        return ResponseFormatter::success(
            $messages->getCollection(),
            'Messages retrieved successfully.'
        );
    }

    public function getMessage(Message $message)
    {
        // check if the owner of this message is the authenticated user
        if ($message->user_id !== auth()->user()->id) {
            return ResponseFormatter::error(
                403,
                'You are not authorized to view this message.',
                true
            );
        }

        $message->load('user', 'chat');
        return ResponseFormatter::success(
            $message,
            'Message retrieved successfully.'
        );
    }

    public function sendMessage(Request $request)
    {
        $chatModel = get_class(new Chat());
        $validator = Validator::make($request->all(), [
            'chat_id' => 'required|exists:' . $chatModel . ',id',
            'content' => 'required|string',
            'type' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(
                422,
                $validator->errors(),
                true
            );
        }

        $message = Message::create([
            'chat_id' => (int)$request->chat_id,
            'user_id' => (int)auth()->user()->id,
            'content' => $request->content,
            'type' => $request->type ?? 'text',
        ]);
        $message->load('user');

        // TODO: load book data if message type is 'request'

        // TODO: send broadcast event to pusher and send notification to onesignal services
        $this->sendNotificationToOther($message);

        return ResponseFormatter::success(
            $message,
            'Message sent successfully.'
        );
    }

    public function sendNotificationToOther(Message $message)
    {
        NewMessageSent::broadcast($message)->toOthers();

        $user = auth()->user();
        $chat = Chat::where('id', $message->chat_id)
            ->with(['participants' => function ($query) use ($user) {
                $query->where('user_id', '!=', $user->id);
            }])
            ->first();

        if (count($chat->participants) === 0) {
            return;
        }

        $recipients_id = $chat->participants->first()->user_id;
        $recipients = User::where('id', $recipients_id)->first();
        $recipients->sendMessageNotification(
            ['message_data' =>
            [
                'sender_name' => $user->name,
                'message' => $message->content,
                'chat_id' => $message->chat_id,
            ]]
        );
    }
}
