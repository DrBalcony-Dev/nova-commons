<?php

namespace DrBalcony\NovaCommon\Commands;

use Illuminate\Console\Command;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RedisCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'redis:cache 
                           {action : The action to perform (flush|key|tag)}
                           {identifier? : The key or tag identifier to remove}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage Redis cache by removing specific keys or tags';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $action = $this->argument('action');
        $identifier = $this->argument('identifier');

        return match ($action) {
            'flush' => $this->flushCache(),
            'key'   => $this->removeByKey($identifier),
            'tag'   => $this->removeByTag($identifier),
            default => $this->invalidAction()
        };
    }

    /**
     * Flush the entire Redis cache.
     *
     * @return int
     */
    private function flushCache(): int
    {
        if (!$this->confirmAction('Are you sure you want to flush the entire Redis cache?')) {
            return self::SUCCESS;
        }

        try {
            Redis::flushdb();
            $this->info('Redis cache flushed successfully.');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->handleException($e, 'flush Redis cache');

            return self::FAILURE;
        }
    }

    /**
     * Remove cache by key.
     *
     * @param  string|null  $key
     * @return int
     */
    private function removeByKey(?string $key): int
    {
        if (!$key) {
            $this->error('Key identifier is required.');
            return self::FAILURE;
        }

        try {
            $deleted = Redis::del($key);

            if ($deleted) {
                $this->info("Cache key '{$key}' removed successfully.");
            } else {
                $this->warn("Cache key '{$key}' not found.");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->handleException($e, 'remove cache key');

            return self::FAILURE;
        }
    }

    /**
     * Remove cache by tag.
     *
     * @param  string|null  $tag
     * @return int
     */
    private function removeByTag(?string $tag): int
    {
        if (!$tag) {
            $this->error('Tag identifier is required.');
            return self::FAILURE;
        }

        try {
            $tagPattern = "cache:tags:{$tag}:*";
            $keys = $this->getKeysByPattern($tagPattern);

            if (empty($keys)) {
                $this->warn("No cache entries found with tag '{$tag}'.");
                return self::SUCCESS;
            }

            $deletedCount = Redis::del(...$keys);

            $this->info("Removed {$deletedCount} cache entries with tag '{$tag}'.");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->handleException($e, 'remove cache by tag');

            return self::FAILURE;
        }
    }

    /**
     * Get keys by pattern.
     *
     * @param  string  $pattern
     * @return array
     */
    private function getKeysByPattern(string $pattern): array
    {
        return Redis::keys($pattern);
    }

    /**
     * Handle invalid action.
     *
     * @return int
     */
    private function invalidAction(): int
    {
        $this->error("Invalid action. Use 'flush', 'key', or 'tag'.");
        $this->line('');
        $this->line('Examples:');
        $this->line('  <info>php artisan redis:cache flush</info>');
        $this->line('  <info>php artisan redis:cache key cache-key-name</info>');
        $this->line('  <info>php artisan redis:cache tag cache-tag-name</info>');

        return self::FAILURE;
    }

    /**
     * Confirm an action with the user.
     *
     * @param  string  $message
     * @return bool
     */
    private function confirmAction(string $message): bool
    {
        return $this->confirm($message);
    }

    /**
     * Handle exceptions.
     *
     * @param  \Exception  $exception
     * @param  string  $action
     * @return void
     */
    private function handleException(\Exception $exception, string $action): void
    {
        $this->error("Failed to {$action}: {$exception->getMessage()}");

        if ($this->getOutput()->isVerbose()) {
            $this->line('');
            $this->line("<comment>Exception:</comment> {$exception->getTraceAsString()}");
        }
    }

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure(): void
    {
        parent::configure();

        $this->setHelp($this->generateHelpText());
    }

    /**
     * Generate help text for the command.
     *
     * @return string
     */
    private function generateHelpText(): string
    {
        return <<<'HELP'
The <info>redis:cache</info> command allows you to manage Redis cache entries:

  <info>php artisan redis:cache flush</info>                 Flush entire Redis cache
  <info>php artisan redis:cache key {key-name}</info>        Remove a specific cache key
  <info>php artisan redis:cache tag {tag-name}</info>        Remove all cache entries with a specific tag

This command provides a simple interface for removing Redis cache entries
by key or tag without affecting other cached data.
HELP;
    }
}