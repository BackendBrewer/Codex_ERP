<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        if ($request->filled('website')) {
            return redirect()->to(route('home').'#contact')->with('contact_success', 'Thanks for contacting MSZ ERP. Our team will be in touch.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'academy_name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email:rfc', 'max:254'],
            'phone' => ['nullable', 'string', 'max:30'],
            'message' => ['required', 'string', 'min:15', 'max:5000'],
            'recaptcha_token' => ['required', 'string', 'max:4096'],
        ]);

        $score = $this->verifyRecaptcha($data['recaptcha_token'], $request);
        if ($score === null) {
            return redirect()->to(route('home').'#contact')
                ->withErrors(['recaptcha_token' => 'We could not verify this request. Please try again.'])
                ->withInput($request->except(['recaptcha_token', 'website']));
        }

        $contact = ContactMessage::query()->create([
            'name' => $data['name'],
            'academy_name' => $data['academy_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'],
            'recaptcha_score' => $score,
        ]);

        $recipient = config('services.contact_form.recipient');
        if (is_string($recipient) && $recipient !== '') {
            try {
                Mail::raw(
                    "Contact request #{$contact->id}\n\nAcademy: {$contact->academy_name}\nName: {$contact->name}\nEmail: {$contact->email}\nPhone: ".($contact->phone ?: '—')."\n\n{$contact->message}",
                    function ($mail) use ($recipient, $contact): void {
                        $mail->to($recipient)
                            ->replyTo($contact->email, $contact->name)
                            ->subject('MSZ ERP website inquiry · '.$contact->academy_name);
                    },
                );
            } catch (Throwable $exception) {
                Log::warning('Contact form email delivery failed; inquiry remains saved.', [
                    'contact_message_id' => $contact->id,
                    'exception' => $exception::class,
                ]);
            }
        }

        return redirect()->to(route('home').'#contact')->with('contact_success', 'Thanks for reaching out. Your message has been received.');
    }

    private function verifyRecaptcha(string $token, Request $request): ?float
    {
        $secret = config('services.recaptcha.secret_key');
        if (! is_string($secret) || $secret === '') {
            return null;
        }

        try {
            $response = Http::asForm()->acceptJson()->timeout(8)->post('https://www.google.com/recaptcha/api/siteverify', array_filter([
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]))->throw()->json();
        } catch (Throwable) {
            return null;
        }

        $score = is_array($response) && is_numeric($response['score'] ?? null) ? (float) $response['score'] : null;
        $minimumScore = (float) config('services.recaptcha.minimum_score', 0.5);
        $expectedHostname = config('services.recaptcha.hostname');
        $hostnameMatches = ! is_string($expectedHostname) || $expectedHostname === '' || hash_equals($expectedHostname, (string) ($response['hostname'] ?? ''));

        if (($response['success'] ?? false) !== true
            || ($response['action'] ?? '') !== 'contact_submit'
            || $score === null
            || $score < $minimumScore
            || ! $hostnameMatches) {
            return null;
        }

        return $score;
    }
}
