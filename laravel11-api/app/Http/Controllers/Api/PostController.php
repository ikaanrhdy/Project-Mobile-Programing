<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::latest()->paginate(5);
        return new PostResource(true, 'List Data Posts', $posts);
    }

    public function store(Request $request)
{
    \Log::info('Store method triggered');

    $validator = Validator::make($request->all(), [
        'image'   => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'title'   => 'required|string|max:255',
        'content' => 'required|string',
    ]);

    if ($validator->fails()) {
        \Log::error('Validation failed', $validator->errors()->toArray());
        return response()->json($validator->errors(), 422);
    }

    if (!$request->hasFile('image')) {
        \Log::error('No image uploaded');
        return response()->json(['message' => 'No image uploaded'], 400);
    }

    $image = $request->file('image');
    \Log::info('Uploaded file: '.$image->getClientOriginalName());

    $image->storeAs('public/posts', $image->hashName());

    $post = Post::create([
        'image'   => $image->hashName(),
        'title'   => $request->title,
        'content' => $request->content,
    ]);

    \Log::info('Post created: ' . $post->id);

    return new PostResource(true, 'Data Post Berhasil Ditambahkan!', $post);
}


    public function show($id)
    {
        $post = Post::find($id);
        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        return new PostResource(true, 'Detail Data Post!', $post);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
            'image'   => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $post = Post::find($id);
        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $image = $request->file('image');
            $image->storeAs('public/posts', $image->hashName());

            // Hapus gambar lama jika ada
            if ($post->image && Storage::disk('public')->exists('posts/' . basename($post->image))) {
                Storage::disk('public')->delete('posts/' . basename($post->image));
            }

            $post->image = $image->hashName();
        }

        $post->title = $request->title;
        $post->content = $request->content;
        $post->save();

        return new PostResource(true, 'Data Post Berhasil Diubah!', $post);
    }

    public function destroy($id)
    {
        $post = Post::find($id);
        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        if ($post->image && Storage::disk('public')->exists('posts/' . basename($post->image))) {
            Storage::disk('public')->delete('posts/' . basename($post->image));
        }

        $post->delete();
        return new PostResource(true, 'Data Post Berhasil Dihapus!', null);
    }
}
