<?php

namespace App\Modules\Notification\Infrastructure\Http\Controllers;

use App\Modules\Notification\Domain\Aggregates\UserNotificationPreference\UserNotificationPreference;
use App\Modules\Notification\Domain\Aggregates\UserNotificationPreference\UserNotificationPreferenceId;
use App\Modules\Notification\Domain\Repositories\UserNotificationPreferenceRepositoryInterface;
use App\Modules\Notification\Domain\ValueObjects\Channel;
use App\Modules\Notification\Infrastructure\Http\Resources\UserNotificationPreferenceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController
{
    public function __construct(
        private UserNotificationPreferenceRepositoryInterface $preferences,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $prefs = $this->preferences->listByUser($request->user()->id);
        return response()->json(['data' => array_map(fn($p) => new UserNotificationPreferenceResource($p), $prefs)]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'channel' => 'required|string|in:in_app,email,sms',
            'template_code' => 'nullable|string',
            'enabled' => 'required|boolean',
        ]);

        $pref = UserNotificationPreference::set(
            UserNotificationPreferenceId::generate(),
            $request->user()->id,
            Channel::from($data['channel']),
            $data['template_code'] ?? null,
            $data['enabled'],
        );
        $this->preferences->save($pref);

        return response()->json(['data' => new UserNotificationPreferenceResource($pref)], 201);
    }
}
