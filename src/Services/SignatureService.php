<?php

namespace Hans\Alicia\Services;

class SignatureService
{
    /**
     * Store secret key value.
     *
     * @var string
     */
    protected string $secret;

    public function __construct(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * Create a signature.
     *
     * @return string
     */
    public function create(): string
    {
        return hash_hmac('ripemd160', $this->key(), $this->secret);
    }

    /**
     * Create a combination of unique attributes of current request as key.
     *
     * @return string
     */
    public function key(): string
    {
        return request()->ip().request()->userAgent();
    }

    /**
     * Validate given signature.
     *
     * @param string $signature
     *
     * @return bool
     */
    public function isValid(string $signature): bool
    {
        return $this->create() == $signature;
    }

    /**
     * Determine given signature is not valid.
     *
     * @param string $signature
     *
     * @return bool
     */
    public function isNotValid(string $signature): bool
    {
        return !$this->isValid($signature);
    }
}
