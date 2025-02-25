<?php

namespace DrBalcony\NovaCommon\Traits;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

trait NovaModelTrait
{
    /**
     * Cache TTL in seconds (24 hours by default)
     */
    protected int $cacheTTL = 86400;

    /**
     * Enable model caching
     */
    protected bool $enableCache = true;

    /**
     * Cache tags for the model
     */
    protected array $cacheTags = [];

    /**
     * Find model by UUID
     */
    public static function findByUuid(string $uuid)
    {
        $instance = new static;

        if (!static::hasUuidColumn($instance)) {
            throw new RuntimeException('Model does not have UUID column');
        }

        if (!static::useCache()) {
            return static::where('uuid', $uuid)->first();
        }

        $cacheKey = $instance->getCacheKey("uuid:{$uuid}");

        return Cache::tags($instance->getCacheTags())
            ->remember(
                $cacheKey,
                $instance->cacheTTL,
                fn () => static::where('uuid', $uuid)->first()
            );
    }

    /**
     * Find model by UUID or fail
     */
    public static function findByUuidOrFail(string $uuid)
    {
        $model = static::findByUuid($uuid);

        if (!$model) {
            throw (new ModelNotFoundException)->setModel(
                static::class,
                $uuid
            );
        }

        return $model;
    }

    /**
     * Find by multiple UUIDs
     */
    public static function findByUuids(array $uuids)
    {
        $instance = new static;

        if (!static::hasUuidColumn($instance)) {
            throw new RuntimeException('Model does not have UUID column');
        }

        if (!static::useCache()) {
            return static::whereIn('uuid', $uuids)->get();
        }

        $cacheKey = $instance->getCacheKey('uuids:'.md5(implode(',', $uuids)));

        return Cache::tags($instance->getCacheTags())
            ->remember(
                $cacheKey,
                $instance->cacheTTL,
                fn () => static::whereIn('uuid', $uuids)->get()
            );
    }

    /**
     * Override the default find method to include caching
     */
    public static function find($id, $columns = ['*'])
    {
        $instance = new static;

        if (!static::useCache()) {
            return static::query()->find($id, $columns);
        }

        $cacheKey = $instance->getCacheKey("find:{$id}:".implode(',', $columns));

        return Cache::tags($instance->getCacheTags())
            ->remember(
                $cacheKey,
                $instance->cacheTTL,
                fn () => static::query()->find($id, $columns)
            );
    }

    /**
     * Boot the trait
     */
    public static function bootNovaModelTrait(): void
    {
        static::creating(function ($model) {
            if (static::hasUuidColumn($model) && empty($model->uuid)) {
                $model->uuid = (string) Str::orderedUuid();
            }
        });

        if (static::useCache()) {
            static::saved(function ($model) {
                $model->clearModelCache();
            });

            static::deleted(function ($model) {
                $model->clearModelCache();
            });
        }
    }

    /**
     * Check if model uses cache
     */
    protected static function useCache(): bool
    {
        if (App::runningUnitTests()) {
            return false;
        }

        return (new static)->enableCache;
    }

    /**
     * Check if the model's table has a 'uuid' column
     */
    protected static function hasUuidColumn(Model $model): bool
    {
        return Schema::hasColumn($model->getTable(), 'uuid');
    }

    /**
     * Clear model cache
     */
    public function clearModelCache(): void
    {
        try {
            Cache::tags($this->getCacheTags())->flush();
        } catch (Exception $e) {
            report($e);
        }
    }

    /**
     * Scope query to search through multiple columns
     */
    public function scopeSearch(Builder $query, ?string $search, array $columns): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($query) use ($search, $columns) {
            foreach ($columns as $column) {
                $query->orWhere($column, 'LIKE', "%{$search}%");
            }
        });
    }

    /**
     * Paginate results with dynamic per_page value from the request
     */
    public function scopePaginateWithPerPage(Builder $query, int $defaultPerPage = 15): LengthAwarePaginator
    {
        $perPage = request()->integer('per_page', $defaultPerPage);
        $page = request()->integer('page', 1);

        if (!static::useCache()) {
            return $query->paginate($perPage, ['*'], 'page', $page);
        }

        $cacheKey = $this->getCacheKey("pagination:{$page}:{$perPage}:".$query->toSql());

        return Cache::tags($this->getCacheTags())
            ->remember(
                $cacheKey,
                $this->cacheTTL,
                fn () => $query->paginate($perPage, ['*'], 'page', $page)
            );
    }

    /**
     * Scope a query by status
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        if (Schema::hasColumn($this->getTable(), 'status')) {
            return $query->where('status', $status);
        }

        return $query;
    }

    /**
     * Scope active records
     */
    public function scopeActive(Builder $query): Builder
    {
        return $this->scopeByStatus($query, 'active');
    }

    /**
     * Scope inactive records
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $this->scopeByStatus($query, 'inactive');
    }

    /**
     * Scope to get records by account_id
     */
    public function scopeByAccount(Builder $query, int $accountId): Builder
    {
        if (Schema::hasColumn($this->getTable(), 'account_id')) {
            return $query->where('account_id', $accountId);
        }

        return $query;
    }

    /**
     * Scope to get records by account_uuid
     */
    public function scopeByAccountUuid(Builder $query, int $accountUuid): Builder
    {
        if (Schema::hasColumn($this->getTable(), 'account_uuid')) {
            return $query->where('account_uuid', $accountUuid);
        }

        return $query;
    }

    /**
     * Scope to get records by user_id
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        if (Schema::hasColumn($this->getTable(), 'user_id')) {
            return $query->where('user_id', $userId);
        }

        return $query;
    }

    /**
     * Scope to get records by account_id
     */
    public function scopeByUserUuid(Builder $query, int $userUuid): Builder
    {
        if (Schema::hasColumn($this->getTable(), 'user_uuid')) {
            return $query->where('user_uuid', $userUuid);
        }

        return $query;
    }

    /**
     * Get cache key for model
     */
    protected function getCacheKey(string $key): string
    {
        return sprintf(
            '%s:%s:%s',
            $this->getTable(),
            $key,
            $this->getCacheVersion()
        );
    }

    /**
     * Get cache version (useful for cache busting)
     */
    protected function getCacheVersion(): string
    {
        return Cache::tags($this->getCacheTags())
            ->remember(
                $this->getTable().':cache_version',
                $this->cacheTTL,
                fn () => (string) now()->timestamp
            );
    }

    /**
     * Get cache tags for the model
     */
    protected function getCacheTags(): array
    {
        return array_merge(
            [$this->getTable()],
            $this->cacheTags
        );
    }

    /**
     * Get cached attribute
     */
    protected function getCachedAttribute(string $key, $callback)
    {
        if (!static::useCache()) {
            return $callback();
        }

        $cacheKey = $this->getCacheKey("attribute:{$this->id}:{$key}");

        return Cache::tags($this->getCacheTags())
            ->remember(
                $cacheKey,
                $this->cacheTTL,
                $callback
            );
    }
}