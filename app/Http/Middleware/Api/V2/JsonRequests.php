<?php

namespace App\Http\Middleware\Api\V2;

use Closure;
use Illuminate\Http\Request;

class JsonRequests
{
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');
        if (!in_array($request->method(), ['GET', 'HEAD']) && $request->request->count() > 0) {
            abort_unless($request->isJson(), 415);
        }
        if (!in_array($request->method(), ['GET', 'HEAD']) && strlen($request->getContent()) > 0) {
            abort_unless($request->isJson(), 415);
            try {
                $decoded = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                abort(400);
            }
            abort_unless(is_object($decoded), 400);
        }
        return $next($request);
    }
}
