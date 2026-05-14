<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Auth')]
final class AuthController extends Controller
{
    #[OA\Post(
        path: '/auth/login',
        summary: 'Obtain a Bearer token',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret'),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Login successful', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                new OA\Property(property: 'access_token', type: 'string'),
                new OA\Property(property: 'user', ref: '#/components/schemas/User'),
            ])),
            new OA\Response(response: 422, description: 'Invalid credentials', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email'))->first();

        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        if (! $user->is_active) {
            abort(403, 'Account is inactive.');
        }

        $user->updateLastLogin();

        return response()->json([
            'token_type'   => 'Bearer',
            'access_token' => $user->createToken('api')->plainTextToken,
            'user'         => new UserResource($user->load('roles', 'branch', 'warehouse')),
        ]);
    }

    #[OA\Get(
        path: '/me',
        summary: 'Get the authenticated user',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Current user', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/User')]
            )),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load('roles', 'branch', 'warehouse'));
    }

    #[OA\Post(
        path: '/auth/logout',
        summary: 'Revoke the current token',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 200, description: 'Logged out', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'message', type: 'string', example: 'Logged out.')]
            )),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    #[OA\Patch(
        path: '/me/profile',
        summary: 'Update name/email/password of the authenticated user',
        security: [['sanctum' => []]],
        tags: ['Auth'],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'email', type: 'string', format: 'email'),
            new OA\Property(property: 'password', type: 'string', format: 'password'),
        ])),
        responses: [
            new OA\Response(response: 200, description: 'Profile updated', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/User')]
            )),
        ]
    )]
    public function updateProfile(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();
        $data = $request->validated();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return new UserResource($user->fresh()->load('roles', 'branch', 'warehouse'));
    }
}
