@extends('superadmin.layout', ['title' => 'Academy requests'])

@section('content')
<section class="page-heading"><div><div class="eyebrow">Platform administration · {{ $openCount }} awaiting action</div><h1>Academy change requests</h1><p>Review updates that academy admins cannot make themselves.</p></div></section>
<section class="request-list">
    @forelse ($requests as $changeRequest)
    <article class="panel request-card">
        <div class="request-card-heading"><div><span class="fee-kicker">{{ $changeRequest->academy->name }} · {{ Str::headline($changeRequest->category) }}</span><h2>{{ $changeRequest->subject }}</h2><p>From {{ $changeRequest->requester->name }} · {{ $changeRequest->requester->email }} · {{ $changeRequest->created_at->format('d M Y, h:i A') }}</p></div><span class="status-pill {{ $changeRequest->status === 'completed' ? 'paid' : (in_array($changeRequest->status, ['open', 'in_review'], true) ? 'pending' : 'neutral') }}">{{ Str::headline($changeRequest->status) }}</span></div>
        <div class="request-details">{{ $changeRequest->details }}</div>
        <form method="POST" action="{{ route('superadmin.change-requests.update', $changeRequest) }}" class="request-review-form">@csrf @method('PATCH')
            <label><span>Review status</span><select name="status" required><option value="in_review" @selected($changeRequest->status === 'in_review')>In review</option><option value="completed" @selected($changeRequest->status === 'completed')>Completed</option><option value="declined" @selected($changeRequest->status === 'declined')>Declined</option></select></label>
            <label class="request-response"><span>Reply to academy admin</span><textarea name="admin_response" rows="2" maxlength="2000" placeholder="Add a note or explain what was changed…">{{ old('admin_response', $changeRequest->admin_response) }}</textarea></label>
            <button class="btn-primary" type="submit">Save review</button>
        </form>
    </article>
    @empty
    <div class="panel empty-state">No academy change requests have been submitted.</div>
    @endforelse
</section>
<div class="pagination-wrap">{{ $requests->links() }}</div>
@endsection
