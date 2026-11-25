<?php

namespace App\Actions\Fortify;

use App\Actions\Auth\RegisterListenerAction;
use App\Models\User;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    public function __construct(private readonly RegisterListenerAction $register) {}

    /**
     * Validate and create a newly registered user.
     *
     * Fortify fires the Registered event itself, so the action must not.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        return $this->register->handle($input, dispatchRegistered: false, requireDetails: true);
    }
}
