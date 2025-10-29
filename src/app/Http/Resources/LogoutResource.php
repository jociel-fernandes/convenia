<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogoutResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'message' => __('api.auth.logout_success'),
            'data' => [
                'status' => 'success',
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ],
        ];
    }
}
