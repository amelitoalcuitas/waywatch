<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $file = $request->file('image');
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
        $path = 'markers/'.Str::uuid().'.'.$ext;

        $disk = config('filesystems.default');
        $uploadDisk = ($disk === 's3') ? 's3' : 'public';

        Storage::disk($uploadDisk)->put($path, $file->get(), 'public');
        $url = ($uploadDisk === 'public')
            ? '/storage/'.$path
            : Storage::disk($uploadDisk)->url($path);

        return response()->json([
            'path' => $path,
            'url' => $url,
        ]);
    }
}
