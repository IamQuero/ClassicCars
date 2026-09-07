<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ListingResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MyListingController extends Controller
{
    /** Los anuncios del vendedor autenticado, borradores incluidos. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $listings = $request->user()->listings()
            ->with(['car', 'photos', 'seller'])
            ->when(
                $request->query('status'),
                fn ($q, $status) => $q->where('status', $status)
            )
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return ListingResource::collection($listings);
    }
}
