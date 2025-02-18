<?php

namespace DrBalcony\NovaCommon\DTO;

/**
 * Data Transfer Object for permission verification parameters
 */
class PermissionVerificationDto
{
    public function __construct(
        public readonly string $permission,
        public readonly ?array $additional = []
    ) {}

    /**
     * Convert DTO to array for API request
     */
    public function toArray(): array
    {
        return array_merge(
            ['permission' => $this->permission],
            $this->additional
        );
    }
}