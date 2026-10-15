<?php

namespace App\Http\Middleware\Api\V2;

use App\Projects\Quickreads\Api\V2\ApiException;
use Closure;
use Illuminate\Http\Request;

class SensitiveRequests
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->isSecure() && !app()->environment('local', 'testing')) {
            throw new ApiException(400, 'Use HTTPS for this operation.');
        }
        abort_if(strlen($request->getContent()) > 32768, 413);
        return $next($request);
    }
}
