<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'billing/stripe/webhook',
        'billing/paypal/webhook',
        'secure/auth/login',
        'secure/auth/register',
        'secure/auth/logout',
    ];
}
