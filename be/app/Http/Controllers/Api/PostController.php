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
    // GET /api/posts
    public function index()
    {
        $posts = Post::latest()->paginate(5);

        // Ubah field 'image' jadi full URL
        $posts->getCollection()->transform(function ($post) {
            $post->image = asset('storage/posts/' . $post->image);
            return $post;
        });

        return new PostResource(true, 'List Data Posts', $posts);
    }

    // POST /api/posts
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image'   => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'title'   => 'required|string',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Simpan file ke storage/app/public/posts
        $image = $request->file('image');
        $image->storeAs('public/posts', $image->hashName());

        // Simpan ke database
        $post = Post::create([
            'image'   => $image->hashName(),
            'title'   => $request->title,
            'content' => $request->content,
        ]);

        // Tambahkan full URL ke response
        $post->image = asset('storage/posts/' . $post->image);

        return new PostResource(true, 'Data Post Berhasil Ditambahkan!', $post);
    }

    // GET /api/posts/{id}
    public function show($id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $post->image = asset('storage/posts/' . $post->image);

        return new PostResource(true, 'Detail Data Post!', $post);
    }

    // PUT /api/posts/{id}
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title'   => 'required|string',
            'content' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $post = Post::find($id);
        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        if ($request->hasFile('image')) {
            // Upload dan simpan gambar baru
            $image = $request->file('image');
            $image->storeAs('public/posts', $image->hashName());

            // Hapus gambar lama
            Storage::delete('public/posts/' . $post->image);

            // Update post dengan gambar baru
            $post->update([
                'image'   => $image->hashName(),
                'title'   => $request->title,
                'content' => $request->content,
            ]);
        } else {
            // Update tanpa gambar
            $post->update([
                'title'   => $request->title,
                'content' => $request->content,
            ]);
        }

        $post->image = asset('storage/posts/' . $post->image);

        return new PostResource(true, 'Data Post Berhasil Diubah!', $post);
    }

    // DELETE /api/posts/{id}
    public function destroy($id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        // Hapus gambar dari storage
        Storage::delete('public/posts/' . $post->image);

        // Hapus data dari DB
        $post->delete();

        return new PostResource(true, 'Data Post Berhasil Dihapus!', null);
    }
}
