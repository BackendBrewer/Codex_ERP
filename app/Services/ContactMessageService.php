<?php

namespace App\Services;

use App\Mail\ContactInquiryReceived;
use App\Models\ContactMessage;
use App\Repositories\ContactMessageRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactMessageService
{
    public function __construct(private readonly ContactMessageRepository $messages) {}

    /** @param array<string, mixed> $data */
    public function store(array $data, ?string $ipAddress): ?ContactMessage
    {
        $score = $this->verifyRecaptcha((string) $data['recaptcha_token'], $ipAddress);
        if ($score === null) {
            return null;
        }

        $contact = $this->messages->create([
            'name' => $data['name'],
            'academy_name' => $data['academy_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'],
            'recaptcha_score' => $score,
        ]);
        $this->notifyRecipient($contact);

        return $contact;
    }

    private function verifyRecaptcha(string $token, ?string $ipAddress): ?float
    {
        $secret = config('services.recaptcha.secret_key');
        if (! is_string($secret) || $secret === '') {
            return null;
        }

        try {
            $response = Http::asForm()->acceptJson()->timeout(8)->post('https://www.google.com/recaptcha/api/siteverify', array_filter([
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ipAddress,
            ]))->throw()->json();
        } catch (Throwable) {
            return null;
        }

        $score = is_array($response) && is_numeric($response['score'] ?? null) ? (float) $response['score'] : null;
        $minimumScore = (float) config('services.recaptcha.minimum_score', 0.5);
        $expectedHostname = config('services.recaptcha.hostname');
        $hostnameMatches = ! is_string($expectedHostname) || $expectedHostname === '' || hash_equals($expectedHostname, (string) ($response['hostname'] ?? ''));

        if (($response['success'] ?? false) !== true || ($response['action'] ?? '') !== 'contact_submit' || $score === null || $score < $minimumScore || ! $hostnameMatches) {
            return null;
        }

        return $score;
    }

    private function notifyRecipient(ContactMessage $contact): void
    {
        $recipient = config('services.contact_form.recipient');
        if (! is_string($recipient) || $recipient === '') {
            Log::warning('Contact form inquiry saved without email notification because no recipient is configured.', ['contact_message_id' => $contact->id]);

            return;
        }

        try {
            Mail::to($recipient)->send((new ContactInquiryReceived($contact))->replyTo($contact->email, $contact->name));
        } catch (Throwable $exception) {
            Log::warning('Contact form email delivery failed; inquiry remains saved.', [
                'contact_message_id' => $contact->id,
                'exception' => $exception::class,
            ]);
        }
    }
}
