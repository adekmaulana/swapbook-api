<?php

namespace App\Repositories;

use App\Events\NewMessageSent;
use App\Facades\ResponseFormatter;
use App\Interfaces\MessageRepositoryInterface;
use App\Models\Chat;
use App\Models\Message;
use App\Models\RequestBook;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MessageRepository implements MessageRepositoryInterface
{
    protected $messaging;

    public function __construct()
    {
    }

    public function editMessage(Request $request, Message $message)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string',
            'request_id' => 'required|exists:requests,id',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(
                422,
                $validator->errors(),
                true
            );
        }

        $status = [
            'pending' => 0,
            'approved' => 1,
            'declined' => 2,
        ];

        // the owner of the button is not authenticated user
        if ($message->user_id === auth('sanctum')->user()->id) {
            return ResponseFormatter::error(
                403,
                'You are not authorized to edit this message.',
                true
            );
        }

        $req = RequestBook::where('id', $request->request_id)->first();
        $req->update(['status' => $status[$request->status]]);
        $req->save();

        $message->update(['content' => $request->content]);
        $message->save();
        $message->load('user', 'chat', 'request', 'request.post');
        $this->sendNotificationToOther($message, $req);
        return ResponseFormatter::success(
            $message,
            'Message updated successfully.'
        );
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
            ->with('request', 'request.post')
            ->latest('created_at')
            ->simplePaginate(
                $pageSize,
                ['*'],
                'page',
                $request->page
            );
        // ->groupBy(function ($message) {
        //     return $message->created_at->format('Y-m-d');
        // });

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

        $message->load('user', 'chat', 'request', 'request.post');
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
            'type' => 'nullable|string',
            'book_id' => 'nullable|exists:posts,id',
            'request_id' => 'nullable|exists:requests,id',
        ]);

        if ($request->type === 'request' && !$request->has('book_id')) {
            return ResponseFormatter::error(
                422,
                'Book id is required for request type message.',
                true
            );
        }

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

        $req = RequestBook::where('id', $request->request_id)->first();
        if ($request->type === 'request') {
            if (!$req) {
                $req = RequestBook::create([
                    'request_by' => auth('sanctum')->user()->id,
                    'post_id' => $request->book_id,
                    'message_id' => $message->id,
                ]);
            } else {
                if ($req->status === 'approved') {
                    $req->update(['status' => 1]);
                } else if ($req->status === 'declined') {
                    $req->update(['status' => 2]);
                } else {
                    $req->update(['status' => 0]);
                }

                $req->update(['message_id' => $message->id]);
                $req->save();
            }
            $message->request = $req;
        }

        $this->sendNotificationToOther($message, $req);
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
            [
                'message_data' =>
                [
                    'sender_name' => $user->name,
                    'chat_id' => $message->chat_id,
                    'message_id' => $message->id,
                    'content' => $message->content,
                    'type' => $message->type,
                    'request' => $message->request,
                ],
            ]
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
