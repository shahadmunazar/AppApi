<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

use App\Models\Channel;

Broadcast::channel('chat.{channelId}', function ($user, $channelId) {
    $channel = Channel::find($channelId);
    
    if (!$channel) {
        return false;
    }

    $isAdmin = false;
    if (isset($user->type) && $user->type === 'admin') {
        $isAdmin = true;
    } elseif (isset($user->role) && $user->role === 'admin') {
        $isAdmin = true;
    }

    return $isAdmin || $user->id === $channel->user_id;
});
