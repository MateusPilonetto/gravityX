<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StorePostRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(
        private PostService $postService
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Posts retrieved successfully.',
            'posts' => PostResource::collection(
                $this->postService->listFor($request->user())
            ),
        ]);
    }

    public function userPosts(Request $request, string $username): JsonResponse
    {
        $profileUser = User::query()
            ->where('username', $username)
            ->firstOrFail();

        return response()->json([
            'message' => 'Profile posts retrieved successfully.',
            'posts' => PostResource::collection(
                $this->postService->listForUser($request->user(), $profileUser)
            ),
        ]);
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $post = $this->postService->create(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'Post created successfully.',
            'post' => new PostResource($post),
        ], 201);
    }

    public function show(Request $request, Post $post): JsonResponse
    {
        return response()->json([
            'message' => 'Post retrieved successfully.',
            'post' => new PostResource(
                $this->postService->findFor($request->user(), $post)
            ),
        ]);
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        if (! $this->isPostAuthor($request->user(), $post)) {
            return response()->json([
                'message' => 'You are not allowed to delete this post.',
            ], 403);
        }

        $this->postService->delete($post);

        return response()->json([
            'message' => 'Post deleted successfully.',
        ]);
    }

    public function like(Request $request, Post $post): JsonResponse
    {
        return response()->json([
            'message' => 'Post liked successfully.',
            'post' => new PostResource(
                $this->postService->like($request->user(), $post)
            ),
        ]);
    }

    public function unlike(Request $request, Post $post): JsonResponse
    {
        return response()->json([
            'message' => 'Post unliked successfully.',
            'post' => new PostResource(
                $this->postService->unlike($request->user(), $post)
            ),
        ]);
    }

    public function comments(Request $request, Post $post): JsonResponse
    {
        return response()->json([
            'message' => 'Comments retrieved successfully.',
            'comments' => CommentResource::collection(
                $this->postService->commentsFor($post)
            ),
        ]);
    }

    public function storeComment(StoreCommentRequest $request, Post $post): JsonResponse
    {
        $comment = $this->postService->addComment(
            $request->user(),
            $post,
            $request->validated()
        );

        return response()->json([
            'message' => 'Comment created successfully.',
            'comment' => new CommentResource($comment),
            'comments_count' => $post->comments()->count(),
        ], 201);
    }

    private function isPostAuthor(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
}
