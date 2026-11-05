<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Services\ContactMessageService;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    public function __construct(private readonly ContactMessageService $contactMessages) {}

    public function store(StoreContactMessageRequest $request): RedirectResponse
    {
        $destination = route('home').'#contact';
        if ($request->filled('website')) {
            return redirect()->to($destination)->with('contact_success', 'Thanks for contacting MSZ ERP. Our team will be in touch.');
        }

        $contact = $this->contactMessages->store($request->validated(), $request->ip());
        if ($contact === null) {
            return redirect()->to($destination)
                ->withErrors(['recaptcha_token' => 'We could not verify this request. Please try again.'])
                ->withInput($request->except(['recaptcha_token', 'website']));
        }

        return redirect()->to($destination)->with('contact_success', 'Thanks for reaching out. Your message has been received.');
    }
}
