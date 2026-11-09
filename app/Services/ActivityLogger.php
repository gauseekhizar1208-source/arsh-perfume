<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Audit trail for security-relevant administrative actions (admin_activity_logs). */
class ActivityLogger
{
    public static function log(string $action, ?Model $subject, string $description, array $properties = []): AdminActivityLog
    {
        $request = request();

        return AdminActivityLog::query()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? strtolower(class_basename($subject)) : null,
            'subject_id' => $subject?->getKey(),
            'description' => Str::limit($description, 250, ''),
            'properties' => $properties ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? Str::limit((string) $request->userAgent(), 250, '') : null,
        ]);
    }
}
