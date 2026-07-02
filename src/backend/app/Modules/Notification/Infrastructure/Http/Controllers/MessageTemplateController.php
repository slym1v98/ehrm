<?php

namespace App\Modules\Notification\Infrastructure\Http\Controllers;

use App\Modules\Notification\Domain\Aggregates\MessageTemplate\MessageTemplate;
use App\Modules\Notification\Domain\Aggregates\MessageTemplate\MessageTemplateId;
use App\Modules\Notification\Domain\Repositories\MessageTemplateRepositoryInterface;
use App\Modules\Notification\Domain\ValueObjects\Channel;
use App\Modules\Notification\Infrastructure\Http\Resources\MessageTemplateResource;
use App\Modules\Shared\Http\Resources\PaginatedCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageTemplateController
{
    public function __construct(
        private MessageTemplateRepositoryInterface $templates,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $templates = $this->templates->list(
            $request->input('channel'),
            $request->boolean('active', null),
        );

        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 20);
        $offset = ($page - 1) * $perPage;
        $paginated = array_slice($templates, $offset, $perPage);

        return response()->json([
            'data' => array_map(fn($t) => new MessageTemplateResource($t), $paginated),
            'meta' => ['current_page' => $page, 'per_page' => $perPage, 'total' => count($templates)],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'channel' => 'required|string|in:in_app,email,sms',
            'subject' => 'nullable|string|max:500',
            'body' => 'required|string',
            'variables' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $template = MessageTemplate::create(
            MessageTemplateId::generate(),
            $data['code'],
            $data['name'],
            Channel::from($data['channel']),
            $data['subject'] ?? '',
            $data['body'],
            $data['variables'] ?? [],
            $data['is_active'] ?? true,
        );
        $this->templates->save($template);

        return response()->json(['data' => new MessageTemplateResource($template)], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $template = $this->templates->findById(new MessageTemplateId($id));
        if ($template === null) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'subject' => 'nullable|string|max:500',
            'body' => 'sometimes|string',
            'variables' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $template->update(
            $data['name'] ?? $template->getName(),
            $data['subject'] ?? $template->getSubject(),
            $data['body'] ?? $template->getBody(),
            $data['variables'] ?? $template->getVariables(),
            $data['is_active'] ?? $template->isActive(),
        );
        $this->templates->save($template);

        return response()->json(['data' => new MessageTemplateResource($template)]);
    }

    public function activate(string $id): JsonResponse
    {
        $template = $this->templates->findById(new MessageTemplateId($id));
        if ($template === null) return response()->json(['error' => 'Not found'], 404);
        $template->activate();
        $this->templates->save($template);
        return response()->json(['data' => new MessageTemplateResource($template)]);
    }

    public function deactivate(string $id): JsonResponse
    {
        $template = $this->templates->findById(new MessageTemplateId($id));
        if ($template === null) return response()->json(['error' => 'Not found'], 404);
        $template->deactivate();
        $this->templates->save($template);
        return response()->json(['data' => new MessageTemplateResource($template)]);
    }
}
