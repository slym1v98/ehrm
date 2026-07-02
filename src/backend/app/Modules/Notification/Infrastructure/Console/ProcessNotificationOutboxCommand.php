<?php

namespace App\Modules\Notification\Infrastructure\Console;

use App\Modules\Notification\Application\CommandHandlers\ProcessOutboxHandler;
use App\Modules\Notification\Application\Commands\ProcessOutboxCommand;
use Illuminate\Console\Command;

class ProcessNotificationOutboxCommand extends Command
{
    protected $signature = 'notifications:process-outbox {--limit=50}';
    protected $description = 'Process pending notification outbox rows';

    public function __construct(private ProcessOutboxHandler $handler)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->handler->handle(new ProcessOutboxCommand(
            (int) $this->option('limit'),
            'cli-' . gethostname() . '-' . getmypid(),
        ));

        $this->info("Processed: {$result['processed']}, Sent: {$result['sent']}, Failed: {$result['failed']}");

        return Command::SUCCESS;
    }
}
