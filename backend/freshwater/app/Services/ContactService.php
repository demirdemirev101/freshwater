<?php

namespace App\Services;

use App\Mail\AdminContactMessageMail;
use App\Models\Contact;
use Illuminate\Support\Facades\Mail;

class ContactService
{
    public function submit(array $data): Contact
    {
        $contact = Contact::create($data);

        Mail::to(config('mail.admin_address'))->send(new AdminContactMessageMail($contact));

        return $contact;
    }
}
