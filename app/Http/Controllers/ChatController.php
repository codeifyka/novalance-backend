<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\Message;
use App\Models\JobPost;
use App\Models\Service;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth('api')->user()->id;
        $account = auth('api')->user()->account_type;
        $relations = ['lastMessage', 'chattable', 'jobPost', 'service'];

        $query = Chat::query();

        if ($account === 'client') {
            $query->where('client_id', $userId)
                ->with(array_merge(['freelancer'], $relations));
        } else {
            $query->where('freelancer_id', $userId)
                ->with(array_merge(['client'], $relations));
        }

        // Automatically remove discussion from chat list if temporary_removed is enabled and end_date has passed
        if (!$request->boolean('include_expired')) {
            $query->where(function ($q) {
                $q->where('temporary_removed', false)
                  ->orWhereNull('temporary_removed')
                  ->orWhereNull('end_date')
                  ->orWhere('end_date', '>=', now());
            });
        }

        $chats = $query->latest('updated_at')->get();

        return response()->json(["data" => $chats]);
    }

    public function store(Request $request)
    {
        $chattableType = $request->chattable_type;
        $chattableId = $request->chattable_id;
        $jobPostId = $request->job_post_id;
        $serviceId = $request->service_id;
        $tag = $request->tag;

        if ($chattableType) {
            if (str_contains($chattableType, 'JobPost') || $chattableType === 'job_post') {
                $chattableType = JobPost::class;
            } elseif (str_contains($chattableType, 'Service') || $chattableType === 'service') {
                $chattableType = Service::class;
            }
        }

        if ($jobPostId && !$chattableId) {
            $chattableType = JobPost::class;
            $chattableId = $jobPostId;
            $tag = $tag ?: 'job_post';
        } elseif ($serviceId && !$chattableId) {
            $chattableType = Service::class;
            $chattableId = $serviceId;
            $tag = $tag ?: 'service';
        }

        // Enforce uniqueness by polymorphic chattable or job_post_id/service_id
        if ($chattableType && $chattableId) {
            $existing = Chat::where('chattable_type', $chattableType)
                ->where('chattable_id', $chattableId)
                ->first();
            if ($existing) {
                return response()->json($existing);
            }
        } elseif ($jobPostId) {
            $existing = Chat::where('job_post_id', $jobPostId)->first();
            if ($existing) {
                return response()->json($existing);
            }
        } elseif ($serviceId) {
            $existing = Chat::where('service_id', $serviceId)->first();
            if ($existing) {
                return response()->json($existing);
            }
        }

        $chat = Chat::create([
            'freelancer_id' => $request->freelancer_id,
            'client_id' => $request->client_id,
            'job_post_id' => $jobPostId,
            'service_id' => $serviceId,
            'chattable_type' => $chattableType,
            'chattable_id' => $chattableId,
            'tag' => $tag,
            'start_date' => $request->start_date ?? $request->started_at ?? now(),
        ]);

        return response()->json($chat);
    }

    public function storeMessage(Request $request)
    {
        $chat = Chat::find($request->chat_id);
        if(auth('api')->user()->account_type == 'freelancer'){
            $message = Message::create([
                'sender' => auth('api')->user()->id,
                'reciever' => $chat->client_id,
                'message' => $request->message,
                'chat_id' => $chat->id
            ]);
        }else{
            $message = Message::create([
                'sender' => auth('api')->user()->id,
                'reciever' => $chat->freelancer_id,
                'message' => $request->message,
                'chat_id' => $chat->id
            ]);
        }
        if($message) {
            return response()->json(["data" => $message]);
        }else{
            return response()->json(["error" => "Something went wrong"]);
        }
    }

    public function getAllMessages(Request $request)
    {
        $messages = Message::where('chat_id', $request->chat_id)->get();

        if($messages) {
            return response()->json(["data" => $messages]);
        }else{
            return response()->json(["error" => "no messages"]);
        }
    }
    public function setExpiryDate(Request $request)
    {
        $request->validate([
            'end_date' => 'required|date',
            'chat_id' => 'required',
            'temporary_removed' => 'nullable|boolean',
        ]);
        $chat = Chat::where('id', $request->chat_id)->first();
    
        if ($chat) {
            $chat->end_date = $request->end_date;
            if ($request->has('temporary_removed')) {
                $chat->temporary_removed = $request->boolean('temporary_removed');
            }
            if ($chat->save()) {
                if ($chat->job_post_id) {
                    \App\Models\Proposal::where('job_post_id', $chat->job_post_id)
                        ->where('freelancer_id', $chat->freelancer_id)
                        ->where('status', 'active')
                        ->update(['ends_at' => $request->end_date]);
                }
                return response()->json(["data" => $chat]);
            } else {
                return response()->json(["error" => "Failed to save end date."], 500);
            }
        } else {
            return response()->json(["error" => "No chat found with the given ID"], 404);
        }
    }

    public function getOrCreate(Request $request)
    {
        $user = auth('api')->user() ?: $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'client_id' => 'nullable|exists:users,id',
            'freelancer_id' => 'nullable|exists:users,id',
            'job_post_id' => 'nullable|exists:job_posts,id',
            'service_id' => 'nullable|exists:services,id',
            'type' => 'nullable|string',
        ]);

        $jobPostId = $request->job_post_id ? (int)$request->job_post_id : null;
        $serviceId = $request->service_id ? (int)$request->service_id : null;

        if ($request->has('user_id')) {
            $otherUserId = (int)$request->user_id;
            if ($user->account_type === 'client') {
                $clientId = $user->id;
                $freelancerId = $otherUserId;
            } else {
                $clientId = $otherUserId;
                $freelancerId = $user->id;
            }
        } else {
            $clientId = (int)$request->client_id;
            $freelancerId = (int)$request->freelancer_id;
        }

        if (!$clientId || !$freelancerId) {
            return response()->json(['error' => 'User ID is required'], 422);
        }

        $chattableType = null;
        $chattableId = null;
        $tag = null;

        if ($jobPostId) {
            $chattableType = JobPost::class;
            $chattableId = $jobPostId;
            $tag = 'job_post';
        } elseif ($serviceId) {
            $chattableType = Service::class;
            $chattableId = $serviceId;
            $tag = 'service';
        }

        $chat = null;

        // 1. Check by polymorphic relation first if specified
        if ($chattableType && $chattableId) {
            $chat = Chat::where('chattable_type', $chattableType)
                ->where('chattable_id', $chattableId)
                ->first();
        }

        // 2. Fallback check by legacy columns if not found
        if (!$chat && $jobPostId) {
            $chat = Chat::where('job_post_id', $jobPostId)->first();
        }
        if (!$chat && $serviceId) {
            $chat = Chat::where('service_id', $serviceId)->first();
        }

        // 3. If neither job nor service was specified, find general chat between them
        if (!$chat && !$jobPostId && !$serviceId) {
            $chat = Chat::where('client_id', $clientId)
                ->where('freelancer_id', $freelancerId)
                ->first();
        }

        // 4. Create new chat if not found
        if (!$chat) {
            $chat = Chat::create([
                'client_id' => $clientId,
                'freelancer_id' => $freelancerId,
                'job_post_id' => $jobPostId,
                'service_id' => $serviceId,
                'chattable_type' => $chattableType,
                'chattable_id' => $chattableId,
                'tag' => $tag,
                'start_date' => now(),
            ]);
        } else {
            // Update missing polymorphic fields on existing chat if needed
            $updated = false;
            if ($chattableType && !$chat->chattable_type) {
                $chat->chattable_type = $chattableType;
                $chat->chattable_id = $chattableId;
                $chat->tag = $tag;
                $updated = true;
            }
            if ($jobPostId && !$chat->job_post_id) {
                $chat->job_post_id = $jobPostId;
                $updated = true;
            }
            if ($serviceId && !$chat->service_id) {
                $chat->service_id = $serviceId;
                $updated = true;
            }
            if ($updated) {
                $chat->save();
            }
        }

        $chat->load(['client', 'freelancer', 'lastMessage', 'chattable', 'jobPost', 'service']);

        return response()->json(['data' => $chat]);
    }
}
