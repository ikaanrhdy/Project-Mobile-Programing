<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;


Route::get('/storage/posts/{filename}', function ($filename) {
    $path = storage_path('app/public/posts/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    $file = file_get_contents($path);
    $type = mime_content_type($path);

    return Response::make($file, 200, [
        'Content-Type' => $type,
        'Access-Control-Allow-Origin' => '*', // <--- penting!
    ]);
});

Route::get('/test-upload', function () {
    $success = Storage::put('public/posts/test.txt', 'Hello Laravel');
    return $success ? 'Upload berhasil' : 'Gagal upload';
});
