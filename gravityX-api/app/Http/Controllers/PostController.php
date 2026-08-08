<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\StorePostRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\StoryAuthorResource;
use App\Models\Post;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(
        private PostService $postService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $posts = $this->postService->paginateFor($user, $this->perPage($request));

        $storyAuthorIds = $user->following()
            ->pluck('following_id')
            ->prepend($user->id)
            ->unique()
            ->values();

        $usersWithStories = User::whereIn('id', $storyAuthorIds)
            ->whereHas('activeStories')
            ->with(['activeStories' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }])
            ->get();

        return response()->json([
            'message' => 'Feed recuperado com sucesso.',
            'posts' => PostResource::collection($posts->getCollection())->resolve($request),
            'pagination' => $this->paginationPayload($posts),
            'stories' => StoryAuthorResource::collection($usersWithStories)->resolve($request),
        ]);
    }

    public function userPosts(Request $request, string $username): JsonResponse
    {
        $profileUser = User::query()
            ->where('username', $username)
            ->firstOrFail();
        $posts = $this->postService->paginateForUser(
            $request->user(),
            $profileUser,
            $this->perPage($request)
        );

        return response()->json([
            'message' => 'Profile posts retrieved successfully.',
            'posts' => PostResource::collection($posts->getCollection())->resolve($request),
            'pagination' => $this->paginationPayload($posts),
        ]);
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $attributes = $request->validated();
        unset($attributes['image']);

        $post = $this->postService->create(
            $request->user(),
            $attributes,
            $request->file('image')
        );

        return response()->json([
            'message' => 'Post created successfully.',
            'post' => new PostResource($post),
        ], 201);
    }

    public function show(Request $request, Post $post): JsonResponse
    {
        $post = $this->postService->findFor($request->user(), $post);
        $comments = $this->postService->paginateCommentsFor($post, $this->perPage($request));
        $post->setRelation('comments', $comments->getCollection());

        return response()->json([
            'message' => 'Post retrieved successfully.',
            'post' => new PostResource($post),
            'comments_pagination' => $this->paginationPayload($comments),
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
        $comments = $this->postService->paginateCommentsFor($post, $this->perPage($request));

        return response()->json([
            'message' => 'Comments retrieved successfully.',
            'comments' => CommentResource::collection($comments->getCollection())->resolve($request),
            'pagination' => $this->paginationPayload($comments),
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

    private function perPage(Request $request): int
    {
        return min(max((int) $request->query('per_page', 20), 1), 50);
    }

    /**
     * @return array{current_page: int, last_page: int, per_page: int, total: int, has_more_pages: bool}
     */
    private function paginationPayload(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'has_more_pages' => $paginator->hasMorePages(),
        ];
    }
}
