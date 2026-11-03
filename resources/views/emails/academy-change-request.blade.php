<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Academy change request</title></head>
<body style="margin:0;padding:30px 12px;background:#f3f6f3;color:#18332d;font-family:Arial,sans-serif">
<main style="max-width:620px;margin:0 auto;padding:30px;background:#fff;border:1px solid #e0e9e2;border-radius:16px">
    <p style="margin:0;color:#287452;font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase">MSZ ERP · Change request</p>
    <h1 style="margin:12px 0;font-size:23px">{{ $changeRequest->subject }}</h1>
    <p style="color:#60736c;line-height:1.65">{{ $changeRequest->academy->name }} · Requested by {{ $changeRequest->requester->name }}</p>
    <p style="font-size:14px;line-height:1.7;white-space:pre-wrap">{{ $changeRequest->details }}</p>
    <p style="color:#819089;font-size:12px">Sign in to MSZ ERP and open “Academy requests” to review it.</p>
</main>
</body>
</html>
