<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

Broadcast::channel('group.{groupId}', function (User $user, $groupId) {
    // Only allow members of the group to listen to its channel
    return $user->groups()->where('groups.id', $groupId)->exists();
});
