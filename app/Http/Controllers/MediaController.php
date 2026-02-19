<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadMediaRequest;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    /**
     * Upload a media file.
     */
    public function store(UploadMediaRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $user = $request->user();

        $path = $file->storeAs(
            'uploads/'.$user->id.'/'.now()->format('Y/m'),
            Str::uuid().'.'.$file->extension(),
            'public'
        );

        $media = Media::create([
            'user_id' => $user->id,
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);

        return response()->json([
            'id' => $media->id,
            'url' => Storage::disk('public')->url($path),
            'original_name' => $media->original_name,
            'mime_type' => $media->mime_type,
        ], 201);
    }
}
