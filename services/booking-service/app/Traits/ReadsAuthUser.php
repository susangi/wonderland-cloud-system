<?php

namespace App\Traits;

// Reads the logged in user that AuthenticateJwt saved on the request.
// The user is an array: ['sub' => ..., 'email' => ..., 'groups' => [...]].
trait ReadsAuthUser
{
    // The current user, or null when nobody is logged in (public routes).
    protected function currentUser(): ?array
    {
        return request()->attributes->get('user');
    }

    // Cognito "sub" of the current user. This is the only user id we store in our databases.
    protected function currentUserId(): ?string
    {
        return $this->currentUser()['sub'] ?? null;
    }

    // Admins are users in the Cognito "admins" group.
    protected function currentUserIsAdmin(): bool
    {
        $groups = $this->currentUser()['groups'] ?? [];

        return in_array('admins', $groups, true);
    }
}
