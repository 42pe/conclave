<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PrivacyUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PrivacyController extends Controller
{
    /**
     * Show the user's privacy settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/privacy');
    }

    /**
     * Update the user's privacy settings.
     */
    public function update(PrivacyUpdateRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return to_route('privacy.edit');
    }
}
