<?php

namespace App\Projects\Quickreads\Api\V2;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Responses
{
    public static function json(Request $request, array $payload, bool $public = true)
    {
        $response = response()->json($payload, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($public && in_array($request->method(), ['GET', 'HEAD'])) {
            $response->headers->set('Cache-Control', 'public, max-age=60, must-revalidate');
            $response->setEtag(hash('sha256', $response->getContent()));
            $response->isNotModified($request);
        } else {
            $response->headers->set('Cache-Control', 'private, no-store');
        }
        return $response;
    }

    public static function error(Throwable $exception)
    {
        $status = $exception instanceof ValidationException ? 422
            : ($exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500);
        $codes = [400 => 'malformed_request', 401 => 'unauthenticated', 403 => 'forbidden',
            404 => 'not_found', 405 => 'method_not_allowed', 409 => 'conflict',
            413 => 'payload_too_large', 415 => 'unsupported_media_type', 422 => 'validation_failed',
            429 => 'rate_limited', 503 => 'unavailable'];
        $messages = [400 => 'The request is malformed.', 401 => 'Authentication failed. Sign in again with valid credentials.',
            403 => 'This operation is not permitted.', 404 => 'The resource was not found.',
            405 => 'This HTTP method is not supported.', 409 => 'The identity is ambiguous or the request conflicts with current data.',
            413 => 'The request is too large.', 415 => 'Use application/json for request bodies.',
            422 => 'The request failed validation.', 429 => 'Too many requests. Retry later.',
            503 => 'The requested content is unavailable.'];
        $headers = $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [];
        $headers['Cache-Control'] = 'private, no-store';
        $error = [
            'code' => $codes[$status] ?? 'internal_error',
            'message' => $exception instanceof ApiException ? $exception->getMessage() : ($messages[$status] ?? 'An internal error occurred.'),
            'fields' => $exception instanceof ValidationException ? (object) $exception->errors() : (object) [],
        ];
        if ($exception instanceof ApiException && $exception->reason !== null) $error['reason'] = $exception->reason;
        return response()->json(['error' => $error], $status, $headers);
    }
}
