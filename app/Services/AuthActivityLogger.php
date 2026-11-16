<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;

class AuthActivityLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        ?User $user,
        string $category,
        string $action,
        ?string $ipAddress,
        array $metadata = [],
    ): void {
        ActivityLog::create([
            'user_id' => $user?->getKey(),
            'category' => $category,
            'action' => $action,
            'ip_address' => $ipAddress,
            'metadata' => $metadata,
        ]);
    }
}
