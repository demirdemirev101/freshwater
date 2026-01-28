<?php

namespace App\Jobs;

use App\Mail\AdminBankTransferShippingFailedMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class NotifyAdminBankTransferShippingFailedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $orderId,
        public string $errorMessage
    ) {}

    public function handle(): void
    {
        Mail::to(config('mail.admin_address', 'admin@freshwater.bg'))
            ->send(new AdminBankTransferShippingFailedMail($this->orderId, $this->errorMessage));
    }
}
