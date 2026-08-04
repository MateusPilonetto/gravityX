<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\Follow;
use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        private ProfileService $profileService
    ) {}

    public function show(Request $request, ?string $username = null): JsonResponse
    {
        if ($username === null) {
            $user = $this->loadProfileCounts($request->user());

            return response()->json([
                'message' => 'Profile retrieved successfully',
                'data' => new UserResource($user),
            ], 200);
        }

        $user = $this->loadProfileCounts($this->findUserByUsername($username));

        $isFollowing = $request->user()->id !== $user->id
            && Follow::where('follower_id', $request->user()->id)
                ->where('following_id', $user->id)
                ->exists();

        return response()->json([
            'message' => 'Profile retrieved successfully',
            'user' => new UserResource($user),
            'is_following' => $isFollowing,
        ], 200);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->profileService->update(
            $request->user(),
            $request->validated(),
            $request->file('avatar')
        );
        $user = $this->loadProfileCounts($user);

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => new UserResource($user),
        ], 200);
    }

    public function search(Request $request): JsonResponse
    {
        $searchQuery = trim((string) $request->query('q', ''));

        if ($searchQuery === '') {
            return response()->json([]);
        }

        $users = User::query()
            ->where('username', 'LIKE', "%{$searchQuery}%")
            ->orWhere('name', 'LIKE', "%{$searchQuery}%")
            ->limit(10)
            ->get(['id', 'username', 'name', 'profile_photo_url']);

        return response()->json($users);
    }

    public function follow(Request $request, string $username): JsonResponse
    {
        $currentUser = $request->user();
        $userToFollow = $this->findUserByUsername($username);

        if ($currentUser->is($userToFollow)) {
            return response()->json(['message' => 'You cannot follow yourself.'], 422);
        }

        Follow::firstOrCreate([
            'follower_id' => $currentUser->id,
            'following_id' => $userToFollow->id,
        ]);

        return $this->followResponse($currentUser, $userToFollow, true, 'Following');
    }

    public function unfollow(Request $request, string $username): JsonResponse
    {
        $currentUser = $request->user();
        $userToUnfollow = $this->findUserByUsername($username);

        if ($currentUser->is($userToUnfollow)) {
            return response()->json(['message' => 'You cannot unfollow yourself.'], 422);
        }

        Follow::where('follower_id', $currentUser->id)
            ->where('following_id', $userToUnfollow->id)
            ->delete();

        return $this->followResponse($currentUser, $userToUnfollow, false, 'Unfollowed');
    }

    private function findUserByUsername(string $username): User
    {
        return User::query()
            ->select([
                'id',
                'name',
                'username',
                'email',
                'bio',
                'profile_photo_url',
                'created_at',
                'updated_at',
            ])
            ->where('username', $username)
            ->firstOrFail();
    }

    private function loadProfileCounts(User $user): User
    {
        return $user->loadCount(['posts', 'followers', 'following']);
    }

    private function followResponse(
        User $currentUser,
        User $profileUser,
        bool $isFollowing,
        string $message
    ): JsonResponse {
        $this->loadProfileCounts($profileUser);
        $currentUser->loadCount('following');

        return response()->json([
            'message' => $message,
            'is_following' => $isFollowing,
            'followers_count' => $profileUser->followers_count,
            'viewer_following_count' => $currentUser->following_count,
        ]);
    }
}
