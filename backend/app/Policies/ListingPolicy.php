<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;

class ListingPolicy
{
    /** Publicar anuncios es cosa de vendedores, no de compradores. */
    public function create(User $user): bool
    {
        return in_array($user->role, ['seller', 'professional'], true);
    }

    public function update(User $user, Listing $listing): bool
    {
        return $user->id === $listing->seller_id;
    }

    public function delete(User $user, Listing $listing): bool
    {
        return $user->id === $listing->seller_id;
    }
}
