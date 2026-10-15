<?php

namespace App\Projects\Quickreads\Api\V2;

use Symfony\Component\HttpKernel\Exception\HttpException;

// Only static, public messages belong here. Never attach a provider exception.
class ApiException extends HttpException
{
    public ?string $reason;

    public function __construct(int $status, string $message, ?string $reason = null, array $headers = [])
    {
        parent::__construct($status, $message, null, $headers);
        $this->reason = $reason;
    }
}
