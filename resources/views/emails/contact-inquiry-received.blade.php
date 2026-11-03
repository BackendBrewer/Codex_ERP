<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New website inquiry</title>
</head>
<body style="margin:0;padding:32px 12px;background:#f3f6f3;color:#18332d;font-family:Arial,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;margin:0 auto;background:#ffffff;border:1px solid #e0e9e2;border-radius:16px;overflow:hidden;">
        <tr>
            <td style="padding:26px 30px;background:#0b4f40;color:#ffffff;">
                <div style="font-size:12px;letter-spacing:1.5px;text-transform:uppercase;color:#c8e2b6;">MSZ ERP · Website inquiry</div>
                <h1 style="margin:10px 0 5px;font-size:24px;line-height:1.3;">New walkthrough request</h1>
                <div style="font-size:13px;color:#d8e7df;">Request #{{ $contact->id }} · {{ $contact->created_at?->format('M j, Y · g:i A') }}</div>
            </td>
        </tr>
        <tr>
            <td style="padding:28px 30px 10px;">
                <p style="margin:0 0 18px;color:#60736c;font-size:14px;line-height:1.6;">Someone submitted the contact form. Their details are below.</p>
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;font-size:14px;">
                    <tr>
                        <td style="width:145px;padding:12px 0;border-bottom:1px solid #edf1ed;color:#72827b;">Name</td>
                        <td style="padding:12px 0;border-bottom:1px solid #edf1ed;font-weight:bold;">{{ $contact->name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0;border-bottom:1px solid #edf1ed;color:#72827b;">Academy</td>
                        <td style="padding:12px 0;border-bottom:1px solid #edf1ed;font-weight:bold;">{{ $contact->academy_name }}</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0;border-bottom:1px solid #edf1ed;color:#72827b;">Email</td>
                        <td style="padding:12px 0;border-bottom:1px solid #edf1ed;"><a href="mailto:{{ $contact->email }}" style="color:#146b54;">{{ $contact->email }}</a></td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0;border-bottom:1px solid #edf1ed;color:#72827b;">Phone</td>
                        <td style="padding:12px 0;border-bottom:1px solid #edf1ed;">{{ $contact->phone ?: 'Not provided' }}</td>
                    </tr>
                </table>
                <h2 style="margin:24px 0 10px;font-size:15px;">What they need</h2>
                <div style="padding:16px;background:#f5f8f5;border:1px solid #e8eee8;border-radius:10px;font-size:14px;line-height:1.7;white-space:pre-wrap;">{{ $contact->message }}</div>
            </td>
        </tr>
        <tr>
            <td style="padding:22px 30px 26px;color:#819089;font-size:12px;line-height:1.6;">Reply directly to this email to respond to {{ $contact->name }}.</td>
        </tr>
    </table>
</body>
</html>
