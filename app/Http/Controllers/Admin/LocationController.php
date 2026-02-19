<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLocationRequest;
use App\Http\Requests\Admin\UpdateLocationRequest;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LocationController extends Controller
{
    /**
     * Display a listing of locations.
     */
    public function index(): Response
    {
        return Inertia::render('admin/locations/index', [
            'locations' => Location::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new location.
     */
    public function create(): Response
    {
        $nextSortOrder = (int) Location::query()->max('sort_order') + 1;

        return Inertia::render('admin/locations/create', [
            'nextSortOrder' => $nextSortOrder,
        ]);
    }

    /**
     * Store a newly created location.
     */
    public function store(StoreLocationRequest $request): RedirectResponse
    {
        Location::create($request->validated());

        return to_route('admin.locations.index');
    }

    /**
     * Show the form for editing the specified location.
     */
    public function edit(Location $location): Response
    {
        return Inertia::render('admin/locations/edit', [
            'location' => $location,
        ]);
    }

    /**
     * Update the specified location.
     */
    public function update(UpdateLocationRequest $request, Location $location): RedirectResponse
    {
        $location->update($request->validated());

        return to_route('admin.locations.index');
    }

    /**
     * Remove the specified location.
     */
    public function destroy(Location $location): RedirectResponse
    {
        $location->delete();

        return to_route('admin.locations.index');
    }
}
