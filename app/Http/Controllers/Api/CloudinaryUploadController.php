<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;

class CloudinaryUploadController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'file' => 'required|file|image|max:10240',
            'folder' => 'nullable|string|max:255',
            'tags' => 'nullable|string|max:500',
        ]);

        $cloudName = config('cloudinary.cloud_name');
        $apiKey = config('cloudinary.api_key');
        $apiSecret = config('cloudinary.api_secret');

        if (!$cloudName || !$apiKey || !$apiSecret) {
            return response()->json([
                'message' => 'Cloudinary is not configured on the server. Set CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, and CLOUDINARY_API_SECRET in .env.',
            ], 422);
        }

        $timestamp = time();
        $params = ['timestamp' => $timestamp];

        if (!empty($data['folder'])) {
            $params['folder'] = $data['folder'];
        }

        if (!empty($data['tags'])) {
            $params['tags'] = $data['tags'];
        }

        ksort($params);
        $signatureBase = collect($params)
            ->map(fn($value, $key) => $key . '=' . $value)
            ->implode('&') . $apiSecret;
        $signature = sha1($signatureBase);

        $client = new Client([
            'timeout' => 60,
            'verify' => filter_var(config('cloudinary.verify', true), FILTER_VALIDATE_BOOLEAN),
        ]);

        $multipart = [
            [
                'name' => 'file',
                'contents' => fopen($request->file('file')->getRealPath(), 'r'),
                'filename' => $request->file('file')->getClientOriginalName(),
            ],
            ['name' => 'api_key', 'contents' => $apiKey],
            ['name' => 'timestamp', 'contents' => (string) $timestamp],
            ['name' => 'signature', 'contents' => $signature],
            ['name' => 'resource_type', 'contents' => 'image'],
        ];

        if (!empty($data['folder'])) {
            $multipart[] = ['name' => 'folder', 'contents' => $data['folder']];
        }

        if (!empty($data['tags'])) {
            $multipart[] = ['name' => 'tags', 'contents' => $data['tags']];
        }

        $response = $client->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/upload", [
            'multipart' => $multipart,
        ]);

        $payload = json_decode((string) $response->getBody(), true);

        return response()->json([
            'url' => $payload['secure_url'] ?? null,
            'public_id' => $payload['public_id'] ?? null,
            'width' => $payload['width'] ?? null,
            'height' => $payload['height'] ?? null,
            'bytes' => $payload['bytes'] ?? null,
            'format' => $payload['format'] ?? null,
        ]);
    }
}
