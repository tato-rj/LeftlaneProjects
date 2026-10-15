<?php

namespace App\Projects\Quickreads\Api\V2;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class StrictInput
{
    public static function read(Request $request, array $rules): array
    {
        // Use the original JSON so trim/empty-string middleware cannot change
        // credential bytes, Boolean types or an idempotency fingerprint.
        $input = json_decode($request->getContent(), true) ?? [];
        foreach (array_diff(array_keys($input), array_keys($rules)) as $key) {
            throw ValidationException::withMessages([$key => ['This field is not supported.']]);
        }
        return Validator::make($input, $rules)->validate();
    }
}
