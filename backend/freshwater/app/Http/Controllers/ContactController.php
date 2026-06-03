<?php

namespace App\Http\Controllers;

use App\Services\ContactService;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, ContactService $contactService)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'required|email|max:255',
            'message' => 'required|string',
        ]);

        $contactService->submit($validated);

        return response()->json(['message' => 'Съобщението ви беше получено. Ще се свържем с вас възможно най-скоро.'], 201);
    }
}
