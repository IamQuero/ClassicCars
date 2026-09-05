<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ListingResource;
use App\Models\Listing;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    public function index(ListingIndexRequest $request)
    {
        $filters = $request->validated();

        $listings = Listing::query()
            ->with(['car', 'photos', 'seller'])
            ->where('status', 'published')
            ->filter($filters)
            ->sorted($filters['sort'] ?? null)
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return ListingResource::collection($listings);
    }

    public function show(Listing $listing)
    {
        $listing->load(['car', 'photos', 'seller']);

        return new ListingResource($listing);
    }
}
