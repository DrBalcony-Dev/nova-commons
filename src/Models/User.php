<?php

namespace DrBalcony\NovaCommon\Models;

use DrBalcony\NovaCommon\Utils\Auth\NoRememberTokenAuthenticatable;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Nova User model
 *
 * @property string $uuid
 * @property string $account_uuid
 * @property ?string $email
 * @property ?string $phone
 * @property ?string $username
 * @property ?string $status
 * @property ?string $last_login_at
 * @property ?string $role
 * @property ?string $created_at
 * @property ?string $updated_at
 * @property ?object $profile
 */
class User extends Authenticatable
{
    use NoRememberTokenAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'account_uuid',
        'account_name',
        'email',
        'phone',
        'username',
        'status',
        'last_login_at',
        'title',
        'roles',
        'created_at',
        'updated_at',
        'profile'
    ];

}