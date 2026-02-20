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

    /*
    * Sends a notification email to the administrator when the calculation of shipping costs for bank transfer orders fails.
    * It includes the order ID and error message in the email content, allowing the admin to quickly identify and address the issue.
    * This job is dispatched from the CalculateBankTransferShippingJob when an exception occurs during shipping cost calculation,
    * ensuring that critical issues are promptly communicated to the admin for resolution.
    * The email is sent to the address specified in the configuration (defaulting to 'admin@freshwater.bg').   
    */
    public function handle(): void
    {
        Mail::to(config('mail.admin_address', 'admin@freshwater.bg'))
            ->send(new AdminBankTransferShippingFailedMail($this->orderId, $this->errorMessage));
    }
}
