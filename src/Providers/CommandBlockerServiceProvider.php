<?php

namespace DrBalcony\NovaCommon\Providers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class CommandBlockerServiceProvider extends ServiceProvider
{

    protected array $blockedCommands = [
//         'db:seed',
//         'redis:cache flush',
         'migrate:fresh',
         'migrate:refresh',
         'migrate:reset',
    ];


    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {

    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {

        if (!App::isLocal()) {
            Event::listen(CommandStarting::class, function (CommandStarting $event) {
                $this->blockCommand($event);
            });
        }

    }

    protected function blockCommand(CommandStarting $event): void
    {
        $commandName = $event->command;
        $blockedCommands = config('blocked_commands', $this->blockedCommands);

        if (in_array($commandName, $blockedCommands)) {
            echo "Command '{$commandName}' is blocked by the Command Blocker.";
            exit(1);
        }
    }
}