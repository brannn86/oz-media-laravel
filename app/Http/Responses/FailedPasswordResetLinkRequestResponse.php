<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Http\Responses\FailedPasswordResetLinkRequestResponse as FortifyResponse;
use Laravel\Fortify\Http\Responses\SuccessfulPasswordResetLinkRequestResponse;

/**
 * An unknown email gets the same "check your inbox" answer as a real one,
 * so the reset form can't be used to find out who has an account.
 * Real failures (e.g. asking again too soon) still show their error.
 */
class FailedPasswordResetLinkRequestResponse extends FortifyResponse
{
    public function toResponse($request)
    {
        if ($this->status === Password::INVALID_USER) {
            return (new SuccessfulPasswordResetLinkRequestResponse(Password::RESET_LINK_SENT))->toResponse($request);
        }

        return parent::toResponse($request);
    }
}
