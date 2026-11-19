<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SocialHighlight;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SocialHighlightPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SocialHighlight');
    }

    public function view(AuthUser $authUser, SocialHighlight $socialHighlight): bool
    {
        return $authUser->can('View:SocialHighlight');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SocialHighlight');
    }

    public function update(AuthUser $authUser, SocialHighlight $socialHighlight): bool
    {
        return $authUser->can('Update:SocialHighlight');
    }

    public function delete(AuthUser $authUser, SocialHighlight $socialHighlight): bool
    {
        return $authUser->can('Delete:SocialHighlight');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SocialHighlight');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SocialHighlight');
    }
}
