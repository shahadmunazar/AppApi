<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Message;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChatController extends Controller
{
    public function uploadMedia(Request $request, Channel $channel)
    {
        try {
            // 1. Authorization: Only Admins or the User belonging to this channel can upload.
        // Assuming 'type' or similar column exists for admin check based on your project structure.
        $user = $request->user();
        $isAdmin = false;
        if (isset($user->type) && $user->type === 'admin') {
            $isAdmin = true;
        } elseif (isset($user->role) && $user->role === 'admin') {
            $isAdmin = true;
        }
        
        // If channel has a user_id, check ownership. Otherwise, we assume it's a global/admin channel.
        if (!$isAdmin && isset($channel->user_id) && $channel->user_id !== $user->id) {
            abort(403, 'You do not have permission to upload media to this channel.');
        }

        // 2. Validation
        $request->validate([
            // file prohibits voice_note, max 10MB
            'file' => [
                'prohibits:voice_note',
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,gif,webp,pdf',
                'max:10240'
            ],
            // voice_note prohibits file, max 5MB. 
            // We use mimetypes because MediaRecorder blobs often lack extensions and default to video/webm.
            'voice_note' => [
                'prohibits:file',
                'nullable',
                'file',
                'mimetypes:audio/webm,video/webm,audio/ogg,audio/mp4,audio/mpeg',
                'max:5120'
            ],
            'reply_to_id' => 'nullable|exists:messages,id',
        ]);

        if (!$request->hasFile('file') && !$request->hasFile('voice_note')) {
            throw ValidationException::withMessages([
                'media' => ['You must provide either a file or a voice_note.']
            ]);
        }

        // 3. Storage
        $uploadedFile = $request->hasFile('file') ? $request->file('file') : $request->file('voice_note');
        $messageType = $request->hasFile('file') ? 'file' : 'voice';
        
        // Generate a safe, unique filename
        $extension = $uploadedFile->getClientOriginalExtension() ?: 'webm';
        $safeFilename = Str::uuid() . '.' . $extension;
        
        // Store in the channel-specific directory on the public disk
        $path = $uploadedFile->storeAs(
            "channels/{$channel->id}/media", 
            $safeFilename, 
            'public'
        );

        // 4. Create Message Record
        $message = Message::create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'message_type' => $messageType,
            'content' => null,
            'media_url' => Storage::disk('public')->url($path),
            'reply_to_id' => $request->reply_to_id,
        ]);

        // Load the user relationship for the broadcast payload
        $message->load(['user', 'replyTo.user', 'likes']);

        // 5. Broadcast Event
        broadcast(new MessageSent($message))->toOthers();

        // 6. JSON Response
        Log::info('Media uploaded successfully', [
            'channel_id' => $channel->id,
            'message_id' => $message->id,
            'media_url' => $message->media_url
        ]);

        return response()->json([
            'success' => true,
            'message' => $message
        ], 201);
        } catch (\Exception $e) {
            Log::error('Media upload failed', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    public function index(Request $request, Channel $channel)
    {
        $user = $request->user();
        $isAdmin = false;
        if (isset($user->type) && $user->type === 'admin') {
            $isAdmin = true;
        } elseif (isset($user->role) && $user->role === 'admin') {
            $isAdmin = true;
        }

        if (!$isAdmin && isset($channel->user_id) && $channel->user_id !== $user->id) {
            abort(403, 'You do not have permission to view this channel.');
        }

        $messages = $channel->messages()->with(['user', 'replyTo.user', 'likes'])->orderBy('created_at', 'asc')->get();

        return response()->json([
            'success' => true,
            'messages' => $messages
        ]);
    }

    public function store(Request $request, Channel $channel)
    {
        try {
            $user = $request->user();
        $isAdmin = false;
        if (isset($user->type) && $user->type === 'admin') {
            $isAdmin = true;
        } elseif (isset($user->role) && $user->role === 'admin') {
            $isAdmin = true;
        }

        if (!$isAdmin && isset($channel->user_id) && $channel->user_id !== $user->id) {
            abort(403, 'You do not have permission to send messages to this channel.');
        }

        $request->validate([
            'content' => 'required|string',
            'reply_to_id' => 'nullable|exists:messages,id',
        ]);

        $message = Message::create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
            'message_type' => 'text',
            'content' => $request->content,
            'media_url' => null,
            'reply_to_id' => $request->reply_to_id,
        ]);

        $message->load(['user', 'replyTo.user', 'likes']);

        broadcast(new MessageSent($message))->toOthers();

        // JSON Response
        Log::info('Text message sent successfully', [
            'channel_id' => $channel->id,
            'message_id' => $message->id,
            'content' => $message->content
        ]);

        return response()->json([
            'success' => true,
            'message' => $message
        ], 201);
        } catch (\Exception $e) {
            Log::error('Text message failed', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function likeMessage(Request $request, \App\Models\Message $message)
    {
        $user = $request->user();
        
        // Toggle like
        $like = $message->likes()->where('user_id', $user->id)->first();
        if ($like) {
            $like->delete();
        } else {
            $message->likes()->create([
                'user_id' => $user->id
            ]);
        }
        
        broadcast(new \App\Events\MessageLiked($message, $user->id))->toOthers();
        
        return response()->json([
            'success' => true,
            'message_id' => $message->id,
            'likes_count' => $message->likes()->count(),
            'liked_by_me' => !$like
        ]);
    }
}
