<?php

namespace App\Repositories;

use App\Facades\ResponseFormatter;
use App\Interfaces\BookRepositoryInterface;
use App\Models\Bookmark;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BookRepository implements BookRepositoryInterface
{
    public function getBook(Post $post)
    {
        $post->load('user', 'user.location');

        // check if the post is bookmarked by the user
        $user = auth('sanctum')->user();
        $isBookmarked = $post->bookmarks->contains('user_id', $user->id);
        $post->is_bookmarked = $isBookmarked;

        // Unset the bookmarks relationship to avoid infinite recursion
        $post->unsetRelation('bookmarks');
        return ResponseFormatter::success(
            $post,
            'Book retrieved successfully.'
        );
    }
    public function getBooks(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'me' => 'nullable|boolean',
            'bookmarks' => 'nullable|boolean',
            'newest' => 'nullable|boolean',
            'random' => 'nullable|boolean',
            'nearby' => 'nullable|boolean',
            'q' => 'nullable|string',
        ]);

        if ($request->newest && $request->random) {
            return ResponseFormatter::error(
                422,
                'You can only use one of the parameters: newest or random.',
                true
            );
        }

        if ($validator->fails()) {
            return ResponseFormatter::error(
                422,
                $validator->errors(),
                true
            );
        }

        $user = $request->user();

        if ($request->q) {
            $param = $request->q;
            $posts = Post::query()
                ->where('title', 'ilike', '%' . $param . '%')
                ->orWhere('author', 'ilike', '%' . $param . '%')
                ->orWhere('genre', 'ilike', '%' . $param . '%')
                ->orWhere('synopsis', 'ilike', '%' . $param . '%')
                ->get()
                ->load('user', 'user.location');
            return ResponseFormatter::success(
                $posts,
                'Books retrieved successfully.'
            );
        }

        if ($request->me) {
            $user = $request->user();
            $posts = $user->posts;
            return ResponseFormatter::success(
                $posts,
                'Books retrieved successfully.'
            );
        }

        if ($request->bookmarks) {
            $bookmarks = $user->bookmarks->pluck('post_id');
            $posts = Post::whereIn('id', $bookmarks)->get();
            $posts->load('user', 'user.location');
            return ResponseFormatter::success(
                $posts,
                'Bookmarks retrieved successfully.'
            );
        }

        if ($request->random) {
            return ResponseFormatter::success(
                Post::inRandomOrder()->get(),
                'Books retrieved successfully.'
            );
        }

        if ($request->newest) {
            return ResponseFormatter::success(
                Post::latest()->get(),
                'Books retrieved successfully.'
            );
        }

        if ($request->nearby) {
            $user = $request->user();
            $location = $user->location;
            if (isset($location) && $location !== null) {
                // $posts = Post::query()
                //     ->join('users', 'posts.user_id', '=', 'users.id')
                //     ->join('locations', 'users.id', '=', 'locations.user_id')
                //     ->select(
                //         'posts.*',
                //         DB::raw("(6371 * acos (cos ( radians($location->latitude) ) * cos( radians( locations.latitude ) ) * cos( radians( locations.longitude ) - radians($location->longitude) ) + sin ( radians($location->latitude) ) * sin( radians( locations.latitude ) ) ) ) AS distance")
                //     )
                //     ->where('posts.user_id', '!=', $user->id)
                //     ->whereNull('posts.deleted_at')
                //     ->orderBy('distance')
                //     ->get();
                $posts = Post::query()
                    ->join('users', 'posts.user_id', '=', 'users.id')
                    ->join('locations', 'users.id', '=', 'locations.user_id')
                    ->selectRaw("posts.*, 
                ( 6371 * acos( cos( radians(?) ) *
                cos( radians( locations.latitude ) )
                * cos( radians( locations.longitude ) - radians(?)
                ) + sin( radians(?) ) *
                sin( radians( locations.latitude ) ) )
                ) AS distance", [$location->latitude, $location->longitude, $location->latitude])
                    // ->having('distance', '<', 10)
                    ->where('locations.user_id', '!=', $user->id)
                    ->orderBy('distance')
                    ->get();
                if ($posts->count() === 0) {
                    return ResponseFormatter::success(
                        null,
                        'No books found nearby.',
                    );
                }

                return ResponseFormatter::success(
                    $posts,
                    'Books retrieved successfully.'
                );
            }
        }

        return ResponseFormatter::success(
            Post::all(),
            'Books retrieved successfully.'
        );
    }
    public function createBook(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'book_api_id' => 'required|string',
            'api_link' => 'required|string',
            'title' => 'required|string',
            'author' => 'required|array|min:1',
            'author.*' => 'required|string|distinct|min:1',
            'genre' => 'required|array',
            'genre.*' => 'required|string|distinct|min:1',
            'synopsis' => 'required|string',
            'average_rating' => 'nullable|numeric',
            'rating_count' => 'nullable|numeric',
            'rating' => 'nullable|numeric',
            'image' => 'nullable|image|mimes:jpg,jpeg,png',
            'image_link' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(
                422,
                $validator->errors(),
                true
            );
        }
        $user = $request->user();
        $request->merge([
            'user_id' => $user->id,
        ]);

        $post = Post::create($request->all());
        $post->load('user');
        if ($request->hasFile('image')) {
            $folder = 'images/post/' . $user->id;
            $photo_url = $request->file('image')->store($folder, 'public');
            $photo_url = asset('storage/' . $photo_url);
            $post->image = $photo_url;
        }

        return ResponseFormatter::success(
            $post,
            'Post created successfully.'
        );
    }
    public function updateBook(Request $request, Post $post)
    {
    }
    public function deleteBook(Post $post)
    {
    }
    public function searchBooks(Request $request)
    {
    }
    public function getBookmarks(Request $request)
    {
    }
    public function bookmark(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:posts,id',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(
                422,
                $validator->errors(),
                true
            );
        }

        $post = Post::find($request->post_id);
        $user = $request->user();

        $query = Bookmark::where(
            [
                ['post_id', $post->id],
                ['user_id', $user->id],
            ]
        );

        $isBookmarked = $query->exists();
        if ($isBookmarked) {
            $query->delete();
            return ResponseFormatter::success(
                false,
                'Post unbookmarked successfully.'
            );
        }

        Bookmark::create(
            [
                'post_id' => $post->id,
                'user_id' => $user->id,
            ]
        );
        return ResponseFormatter::success(
            true,
            'Post bookmarked successfully.'
        );
    }
}
