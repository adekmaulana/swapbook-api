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

        // Group messages by date
        // $groupedMessages = $messages->getCollection()->groupBy(function ($message) {
        //     return $message->created_at->format('Y-m-d');
        // });

        // Format the response
        // $response = $groupedMessages->map(function ($messages, $date) {
        //     return [
        //         'date' => $date,
        //         'messages' => $messages
        //     ];
        // })->values();

        // return ResponseFormatter::success(
        //     $response,
        //     'Messages retrieved successfully.'
        // );

        return ResponseFormatter::success(
            $messages->getCollection(),
            'Messages retrieved successfully.'
        );
    }

    public function getMessage(Message $message)
    {
        // check if the owner of this message is the authenticated user
        if ($message->user_id !== auth('sanctum')->user()->id) {
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
            'user_id' => (int)auth('sanctum')->user()->id,
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
        broadcast(new NewMessageSent($message))->toOthers();

        $user = auth('sanctum')->user();
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
                'chat_id' => $message->chat_id,
                'message_id' => $message->id,
                'content' => $message->content,
                'type' => $message->type,
            ]]
        );
    }

    public function readMessages(Request $request)
    {
        $chatModel = get_class(new Chat());
        $validator = Validator::make($request->all(), [
            'chat_id' => 'required|exists:' . $chatModel . ',id',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(
                422,
                $validator->errors(),
                true
            );
        }

        $chat = Chat::find($request->chat_id);
        $chat->messages()
            ->where('user_id', '!=', auth('sanctum')->user()->id)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);

        return ResponseFormatter::success(
            messages: 'Messages read successfully.'
        );
    }
}
