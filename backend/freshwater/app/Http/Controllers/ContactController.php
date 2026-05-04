<?php

namespace App\Http\Controllers;

use App\Mail\AdminContactMessageMail;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'required|email|max:255',
            'message' => 'required|string',
        ]);

        $contact = Contact::create($validated);

        Mail::to(config('mail.admin_address'))->send(new AdminContactMessageMail($contact));

        return response()->json(['message' => 'Your message has been received. We will get back to you shortly.'], 201);
    }
}
