<?php

namespace App\Services;

class BackendUrlService
{
    public function baseUrl(): string
    {
        return rtrim((string) config('app.backend_url', 'http://192.168.1.101:8000'), '/');
    }

    public function adminOrderEditUrl(int $orderId): string
    {
        return $this->baseUrl().'/admin/orders/'.$orderId.'/edit';
    }
}
