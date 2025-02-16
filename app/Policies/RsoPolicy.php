<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Rso;
use Illuminate\Auth\Access\HandlesAuthorization;

class RsoPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_rso');
    }

    /**
     * Determine whether the user can view own models.
     */
    public function viewOwn(User $user, Rso $rso): bool
    {
        return $user->can('view_own_rso') && $user->id === $rso->user_id;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Rso $rso): bool
    {
        return $user->can('view_rso');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_rso');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Rso $rso): bool
    {
        return $user->can('update_rso');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Rso $rso): bool
    {
        return $user->can('delete_rso');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_rso');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Rso $rso): bool
    {
        return $user->can('force_delete_rso');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_rso');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Rso $rso): bool
    {
        return $user->can('restore_rso');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_rso');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Rso $rso): bool
    {
        return $user->can('replicate_rso');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_rso');
    }
}
