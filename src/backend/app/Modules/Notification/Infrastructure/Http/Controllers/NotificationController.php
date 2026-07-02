<?php

namespace App\Modules\Notification\Infrastructure\Http\Controllers;

use App\Modules\Notification\Application\CommandHandlers\MarkAllReadHandler;
use App\Modules\Notification\Application\CommandHandlers\MarkMessageReadHandler;
use App\Modules\Notification\Application\CommandHandlers\ProcessOutboxHandler;
use App\Modules\Notification\Application\Commands\ProcessOutboxCommand;
use App\Modules\Notification\Application\Commands\MarkAllReadCommand;
use App\Modules\Notification\Application\Commands\MarkMessageReadCommand;
use App\Modules\Notification\Domain\Repositories\NotificationMessageRepositoryInterface;
use App\Modules\Shared\Http\Resources\PaginatedCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController
{
    public function __construct(
        private NotificationMessageRepositoryInterface $messages,
        private MarkMessageReadHandler $markReadHandler,
        private MarkAllReadHandler $markAllReadHandler,
        private ProcessOutboxHandler $processOutboxHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->messages->listForUser(
            $request->user()->id,
            $request->input('status'),
            (int) $request->input('per_page', 20),
        );
        return response()->json(new PaginatedCollection($paginator));
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->messages->countUnreadByChannel($request->user()->id, 'in_app');
        return response()->json(['data' => ['count' => $count]]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $this->markReadHandler->handle(new MarkMessageReadCommand($id, $request->user()->id));
        return response()->json(['data' => ['message' => 'OK']]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $this->markAllReadHandler->handle(new MarkAllReadCommand($request->user()->id));
        return response()->json(['data' => ['message' => 'OK']]);
    }

    public function processOutbox(Request $request): JsonResponse
    {
        $result = $this->processOutboxHandler->handle(new ProcessOutboxCommand(
            (int) $request->input('limit', 50),
            'http-' . $request->user()->id,
        ));

        return response()->json(['data' => $result]);
    }
}

