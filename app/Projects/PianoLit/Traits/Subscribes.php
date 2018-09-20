<?php

namespace App\Projects\PianoLit\Traits;

trait Subscribes
{
    public function subscribe($receipt)
    {
        $method = $this->subscription()->exists() ? 'update' : 'create';

        return $this->subscription()->$method([
            "version_external_identifier" => $receipt['version_external_identifier'],
            "request_date" => $receipt['request_date'],
            "request_date_pst" => $receipt['request_date_pst'],
            "request_date_ms" => $receipt['request_date_ms'],
            "receipt_type" => $receipt['receipt_type'],
            "bundle_id" => $receipt['bundle_id'],
            "receipt_creation_date" => $receipt['receipt_creation_date'],
            "receipt_creation_date_pst" => $receipt['receipt_creation_date_pst'],
            "receipt_creation_date_ms" => $receipt['receipt_creation_date_ms'],
            "download_id" => $receipt['download_id'],
            "adam_id" => $receipt['adam_id'],
            "app_item_id" => $receipt['app_item_id'],
            "application_version" => $receipt['application_version'],
            "original_purchase_date" => $receipt['original_purchase_date'],
            "original_purchase_date_pst" => $receipt['original_purchase_date_pst'],
            "original_purchase_date_ms" => $receipt['original_purchase_date_ms'],
            "original_application_version" => $receipt['original_application_version'],
            "cancellation_date" => $receipt['cancellation_date'] ?? null,
            "cancellation_reason" => $receipt['cancellation_reason'] ?? null,
            "in_app" => json_encode($receipt['in_app'])
        ]);
    }
}
