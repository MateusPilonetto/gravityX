<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'GravityX API',
    version: '1.0.0',
    description: 'REST API used by the GravityX web application. Protected endpoints require a Laravel Sanctum bearer token.'
)]
#[OA\Server(
    url: '/api',
    description: 'Current GravityX API host'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'API token',
    description: 'Paste the token returned by the login or register endpoint.'
)]
#[OA\Tag(name: 'Authentication', description: 'Registration and session token management.')]
#[OA\Tag(name: 'Profiles', description: 'Current user profiles, discovery, and follows.')]
#[OA\Tag(name: 'Posts', description: 'Feed, posts, likes, and comments.')]
#[OA\Tag(name: 'Stories', description: 'Stories that expire after 24 hours.')]
#[OA\Schema(
    schema: 'User',
    type: 'object',
    required: ['id', 'name', 'username', 'bio', 'posts_count', 'followers_count', 'following_count'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Ada Lovelace'),
        new OA\Property(property: 'username', type: 'string', example: 'ada'),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'ada@example.test'),
        new OA\Property(property: 'bio', type: 'string', nullable: true, example: 'Computer programmer.'),
        new OA\Property(property: 'profile_photo_url', type: 'string', nullable: true, example: '/media/avatars/1'),
        new OA\Property(property: 'posts_count', type: 'integer', example: 12),
        new OA\Property(property: 'followers_count', type: 'integer', example: 48),
        new OA\Property(property: 'following_count', type: 'integer', example: 20),
        new OA\Property(property: 'mutual_connections_count', type: 'integer', nullable: true, example: 3),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Post',
    type: 'object',
    required: ['id', 'likes_count', 'comments_count', 'is_liked', 'can_delete'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'caption', type: 'string', nullable: true, example: 'Hello, GravityX!'),
        new OA\Property(property: 'body', type: 'string', nullable: true, example: 'A longer post body.'),
        new OA\Property(property: 'image_url', type: 'string', nullable: true, example: '/media/posts/1'),
        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
        new OA\Property(property: 'likes_count', type: 'integer', example: 7),
        new OA\Property(property: 'comments_count', type: 'integer', example: 2),
        new OA\Property(property: 'is_liked', type: 'boolean', example: false),
        new OA\Property(property: 'can_delete', type: 'boolean', example: false),
        new OA\Property(
            property: 'comments',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Comment')
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Comment',
    type: 'object',
    required: ['id', 'post_id', 'body'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'post_id', type: 'integer', example: 1),
        new OA\Property(property: 'body', type: 'string', example: 'Nice post!'),
        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Story',
    type: 'object',
    required: ['id', 'user_id', 'media_url', 'media_type', 'expires_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'media_url', type: 'string', example: '/media/stories/1?expires=...&signature=...'),
        new OA\Property(property: 'media_type', type: 'string', enum: ['image', 'video']),
        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Pagination',
    type: 'object',
    required: ['current_page', 'last_page', 'per_page', 'total', 'has_more_pages'],
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 3),
        new OA\Property(property: 'per_page', type: 'integer', example: 20),
        new OA\Property(property: 'total', type: 'integer', example: 42),
        new OA\Property(property: 'has_more_pages', type: 'boolean', example: true),
    ]
)]
#[OA\Schema(
    schema: 'ApiMessage',
    type: 'object',
    required: ['message'],
    properties: [new OA\Property(property: 'message', type: 'string')]
)]
#[OA\Schema(
    schema: 'ValidationError',
    type: 'object',
    required: ['message', 'errors'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(property: 'errors', type: 'object', additionalProperties: true),
    ]
)]
final class GravityXApi
{
    #[OA\Post(
        path: '/register',
        operationId: 'register',
        summary: 'Register a user',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'username', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 255),
                    new OA\Property(property: 'username', type: 'string', maxLength: 255, description: 'Any non-empty name except a slash.'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'User created and token issued.'),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function register(): void {}

    #[OA\Post(
        path: '/login',
        operationId: 'login',
        summary: 'Log in and create an API token',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Authenticated user and bearer token.'),
            new OA\Response(response: 422, description: 'Invalid credentials or validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
            new OA\Response(response: 429, description: 'Too many attempts.'),
        ]
    )]
    public function login(): void {}

    #[OA\Post(
        path: '/logout',
        operationId: 'logout',
        summary: 'Revoke the current token',
        security: [['sanctum' => []]],
        tags: ['Authentication'],
        responses: [
            new OA\Response(response: 200, description: 'Logged out.', content: new OA\JsonContent(ref: '#/components/schemas/ApiMessage')),
            new OA\Response(response: 401, description: 'Missing or invalid token.'),
        ]
    )]
    public function logout(): void {}

    #[OA\Get(
        path: '/me',
        operationId: 'getCurrentProfile',
        summary: 'Get the current profile',
        security: [['sanctum' => []]],
        tags: ['Profiles'],
        responses: [
            new OA\Response(response: 200, description: 'Current profile.'),
            new OA\Response(response: 401, description: 'Missing or invalid token.'),
        ]
    )]
    public function currentProfile(): void {}

    #[OA\Put(
        path: '/me',
        operationId: 'updateCurrentProfile',
        summary: 'Update the current profile',
        security: [['sanctum' => []]],
        tags: ['Profiles'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['name', 'username'],
                    properties: [
                        new OA\Property(property: 'name', type: 'string', maxLength: 255),
                        new OA\Property(property: 'username', type: 'string', maxLength: 255),
                        new OA\Property(property: 'bio', type: 'string', nullable: true, maxLength: 1000),
                        new OA\Property(property: 'avatar', type: 'string', format: 'binary', description: 'JPEG, PNG, or WebP up to 5 MB.'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Profile updated.'),
            new OA\Response(response: 401, description: 'Missing or invalid token.'),
            new OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function updateCurrentProfile(): void {}

    #[OA\Get(
        path: '/search',
        operationId: 'searchProfiles',
        summary: 'Search profiles by name or username',
        security: [['sanctum' => []]],
        tags: ['Profiles'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: false, description: 'Search term.', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'At most ten matching profiles.', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/User'))),
            new OA\Response(response: 401, description: 'Missing or invalid token.'),
        ]
    )]
    public function searchProfiles(): void {}

    #[OA\Get(
        path: '/suggestions',
        operationId: 'getProfileSuggestions',
        summary: 'Get follow suggestions',
        security: [['sanctum' => []]],
        tags: ['Profiles'],
        responses: [
            new OA\Response(response: 200, description: 'Suggested profiles.'),
            new OA\Response(response: 401, description: 'Missing or invalid token.'),
        ]
    )]
    public function profileSuggestions(): void {}

    #[OA\Get(
        path: '/users/{username}',
        operationId: 'getProfileByUsername',
        summary: 'Get a profile by username',
        security: [['sanctum' => []]],
        tags: ['Profiles'],
        parameters: [new OA\Parameter(name: 'username', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Profile and follow status.'),
            new OA\Response(response: 401, description: 'Missing or invalid token.'),
            new OA\Response(response: 404, description: 'Profile not found.'),
        ]
    )]
    public function profileByUsername(): void {}

    #[OA\Post(
        path: '/users/{username}/follow',
        operationId: 'followUser',
        summary: 'Follow a profile',
        security: [['sanctum' => []]],
        tags: ['Profiles'],
        parameters: [new OA\Parameter(name: 'username', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Follow state updated.'),
            new OA\Response(response: 401, description: 'Missing or invalid token.'),
            new OA\Response(response: 404, description: 'Profile not found.'),
            new OA\Response(response: 422, description: 'Cannot follow yourself.'),
        ]
    )]
    public function followUser(): void {}

    #[OA\Delete(
        path: '/users/{username}/follow',
        operationId: 'unfollowUser',
        summary: 'Unfollow a profile',
        security: [['sanctum' => []]],
        tags: ['Profiles'],
        parameters: [new OA\Parameter(name: 'username', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Follow state updated.'),
            new OA\Response(response: 401, description: 'Missing or invalid token.'),
            new OA\Response(response: 404, description: 'Profile not found.'),
        ]
    )]
    public function unfollowUser(): void {}

    #[OA\Get(
        path: '/posts',
        operationId: 'getFeed',
        summary: 'Get the personalized feed and active stories',
        security: [['sanctum' => []]],
        tags: ['Posts'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'From 1 to 50; defaults to 20.', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 50)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Feed page, pagination metadata, and stories.'),
            new OA\Response(response: 401, description: 'Missing or invalid token.'),
        ]
    )]
    public function feed(): void {}

    #[OA\Post(
        path: '/posts',
        operationId: 'createPost',
        summary: 'Create a post',
        security: [['sanctum' => []]],
        tags: ['Posts'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'caption', type: 'string', nullable: true, maxLength: 255),
                        new OA\Property(property: 'body', type: 'string', nullable: true, maxLength: 5000),
                        new OA\Property(property: 'image', type: 'string', format: 'binary', description: 'JPEG, PNG, or WebP up to 5 MB.'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Post created.'),
            new OA\Response(response: 401, description: 'Missing or invalid token.'),
            new OA\Response(response: 422, description: 'Provide caption, body, or image; validation failed.'),
        ]
    )]
    public function createPost(): void {}

    #[OA\Get(
        path: '/posts/{post}',
        operationId: 'getPost',
        summary: 'Get a post and its first page of comments',
        security: [['sanctum' => []]],
        tags: ['Posts'],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 50)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Post and comments page.'),
            new OA\Response(response: 401, description: 'Missing or invalid token.'),
            new OA\Response(response: 404, description: 'Post not found.'),
        ]
    )]
    public function getPost(): void {}

    #[OA\Delete(
        path: '/posts/{post}',
        operationId: 'deletePost',
        summary: 'Delete one of the current user posts',
        security: [['sanctum' => []]],
        tags: ['Posts'],
        parameters: [new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Post deleted.', content: new OA\JsonContent(ref: '#/components/schemas/ApiMessage')),
            new OA\Response(response: 401, description: 'Missing or invalid token.'),
            new OA\Response(response: 403, description: 'Post belongs to another user.'),
            new OA\Response(response: 404, description: 'Post not found.'),
        ]
    )]
    public function deletePost(): void {}

    #[OA\Post(
        path: '/posts/{post}/likes',
        operationId: 'likePost',
        summary: 'Like a post',
        security: [['sanctum' => []]],
        tags: ['Posts'],
        parameters: [new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Updated post with like state.'),
            new OA\Response(response: 401, description: 'Missing or invalid token.'),
            new OA\Response(response: 404, description: 'Post not found.'),
        ]
    )]
    public function likePost(): void {}

    #[OA\Delete(
        path: '/posts/{post}/likes',
        operationId: 'unlikePost',
        summary: 'Remove a like from a post',
        security: [['sanctum' => []]],
        tags: ['Posts'],
        parameters: [new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Updated post with like state.'),
            new OA\Response(response: 401, description: 'Missing or invalid token.'),
            new OA\Response(response: 404, description: 'Post not found.'),
        ]
    )]
    public function unlikePost(): void {}

    #[OA\Get(
        path: '/posts/{post}/comments',
        operationId: 'getPostComments',
        summary: 'Get a page of post comments',
        security: [['sanctum' => []]],
        tags: ['Posts'],
        parameters: [
            new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 50)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Comments page.'),
            new OA\Response(response: 401, description: 'Missing or invalid token.'),
            new OA\Response(response: 404, description: 'Post not found.'),
        ]
    )]
    public function getPostComments(): void {}

    #[OA\Post(
        path: '/posts/{post}/comments',
        operationId: 'createPostComment',
        summary: 'Create a comment',
        security: [['sanctum' => []]],
        tags: ['Posts'],
        parameters: [new OA\Parameter(name: 'post', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['body'],
                properties: [new OA\Property(property: 'body', type: 'string', maxLength: 3000)]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Comment created.'),
            new OA\Response(response: 401, description: 'Missing or invalid token.'),
            new OA\Response(response: 404, description: 'Post not found.'),
            new OA\Response(response: 422, description: 'Validation failed.'),
        ]
    )]
    public function createPostComment(): void {}

    #[OA\Get(
        path: '/users/{username}/posts',
        operationId: 'getUserPosts',
        summary: 'Get a page of posts by username',
        security: [['sanctum' => []]],
        tags: ['Posts'],
        parameters: [
            new OA\Parameter(name: 'username', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 50)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Posts page.'),
            new OA\Response(response: 401, description: 'Missing or invalid token.'),
            new OA\Response(response: 404, description: 'Profile not found.'),
        ]
    )]
    public function userPosts(): void {}

    #[OA\Post(
        path: '/stories',
        operationId: 'createStory',
        summary: 'Create a story that expires after 24 hours',
        security: [['sanctum' => []]],
        tags: ['Stories'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['media'],
                    properties: [
                        new OA\Property(property: 'media', type: 'string', format: 'binary', description: 'JPEG, PNG, WebP, or MP4 up to 10 MB.'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Story created.'),
            new OA\Response(response: 401, description: 'Missing or invalid token.'),
            new OA\Response(response: 422, description: 'Validation failed.'),
        ]
    )]
    public function createStory(): void {}

    #[OA\Delete(
        path: '/stories/{story}',
        operationId: 'deleteStory',
        summary: 'Delete one of the current user stories',
        security: [['sanctum' => []]],
        tags: ['Stories'],
        parameters: [new OA\Parameter(name: 'story', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Story deleted.', content: new OA\JsonContent(ref: '#/components/schemas/ApiMessage')),
            new OA\Response(response: 401, description: 'Missing or invalid token.'),
            new OA\Response(response: 403, description: 'Story belongs to another user.'),
            new OA\Response(response: 404, description: 'Story not found.'),
        ]
    )]
    public function deleteStory(): void {}
}
