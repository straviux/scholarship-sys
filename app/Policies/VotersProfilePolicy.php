<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VoterProfile;
use Illuminate\Auth\Access\Response;

class VotersProfilePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        //
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, VoterProfile $voterProfile): bool
    {
        //
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['admin', 'moderator', 'encoder', 'user']) ? true : false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, VoterProfile $voterProfile): bool
    {
        return $user->hasRole(['admin', 'moderator', 'encoder']) ? true : false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, VoterProfile $voterProfile): bool
    {
        return $user->hasRole(['admin', 'moderator']) ? true : false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, VoterProfile $voterProfile): bool
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, VoterProfile $voterProfile): bool
    {
        //
    }
}
