<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    protected $token;

    public function __construct($user, $token)
    {
        parent::__construct($user);
        $this->token = $token;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'message' => __('api.auth.login_success'),
            'data' => [
                'user' => new UserResource($this->resource),
                'access_token' => $this->token, // ✅ JWT Token
                'token_type' => 'Bearer',
                'expires_in' => config('passport.personal_access_tokens_expire_in', 15552000), // 6 months in seconds
                'scopes' => $this->resource->tokens()->latest()->first()?->scopes ?? [],
            ],
        ];
    }
}
