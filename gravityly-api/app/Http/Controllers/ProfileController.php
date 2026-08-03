<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\Follow;
use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function __construct(
        private ProfileService $profileService
    ) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Profile retrieved successfully',
            'data'    => new UserResource($request->user()),
        ], 200);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->profileService->update(
            $request->user(),
            $request->validated(),
            $request->file('avatar')
        );

        return response()->json([
            'message' => 'Profile updated successfully',
            'data'    => new UserResource($user),
        ], 200);
    }

    public function search(Request $request)
    {
        $query = $request->query('q');
        
        if (!$query) {
            return response()->json([]);
        }

        $users = User::where('username', 'LIKE', "%{$query}%")
                     ->orWhere('name', 'LIKE', "%{$query}%")
                     ->limit(10)
                     ->get(['id', 'username', 'name', 'profile_photo_url']);

        return response()->json($users);
    }

    public function showUser($username)
    {
        $user = User::withCount(['posts', 'followers', 'following'])
                    ->where('username', $username)
                    ->firstOrFail();

        $isFollowing = false;
        
        if (Auth::check()) {
            $isFollowing = Follow::where('follower_id', Auth::id())
                                 ->where('following_id', $user->id)
                                 ->exists();
        }

        return response()->json([
            'user' => $user,
            'is_following' => $isFollowing
        ]);
    }

    public function toggleFollow($username)
    {
        $userToFollow = User::where('username', $username)->firstOrFail();
        $currentUser = Auth::user();

        if ($currentUser->id === $userToFollow->id) {
            return response()->json(['message' => 'Você não pode seguir a si mesmo'], 400);
        }

        $follow = Follow::where('follower_id', $currentUser->id)
                        ->where('following_id', $userToFollow->id)
                        ->first();

        if ($follow) {
            $follow->delete();
            return response()->json(['message' => 'Deixou de seguir', 'is_following' => false]);
        } else {
            Follow::create([
                'follower_id' => $currentUser->id,
                'following_id' => $userToFollow->id
            ]);
            return response()->json(['message' => 'Seguindo', 'is_following' => true]);
        }
    }
}
