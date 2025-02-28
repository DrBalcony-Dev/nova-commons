<?php

namespace DrBalcony\NovaCommon\Utils\Auth;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait NoRememberTokenAuthenticatable
 *
 * @mixin Model
 */
trait NoRememberTokenAuthenticatable
{
    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'uuid';
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->uuid;
    }

    /**
     * Get the password for the user.
     *
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getAuthPassword()
    {
        throw new \BadMethodCallException('Unexpected method call');
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getRememberToken()
    {
        throw new \BadMethodCallException('Unexpected method call');
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param string $value
     *
     * @codingStandardsIgnoreStart
     */
    public function setRememberToken($value)
    {
        throw new \BadMethodCallException('Unexpected method call');
    }
    // @codingStandardsIgnoreEnd

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     *
     * @codeCoverageIgnore
     */
    public function getRememberTokenName()
    {
        throw new \BadMethodCallException('Unexpected method call');
    }
}
