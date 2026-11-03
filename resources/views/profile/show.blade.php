@extends($user->isSuperAdmin() ? 'superadmin.layout' : 'layouts.app', ['title' => 'My profile'])

@section('content')
<section class="page-heading"><div><div class="eyebrow">Account settings</div><h1>My profile</h1><p>{{ $user->isSuperAdmin() ? 'Manage your platform administrator account.' : 'Update your academy details, secure your password, or ask the Super Admin for help.' }}</p></div></section>

@if ($user->isSuperAdmin())
<form method="POST" enctype="multipart/form-data" action="{{ route('profile.update') }}" class="panel form-panel profile-panel">@csrf @method('PUT')
    <div class="section-title"><b>Super Admin profile</b><span>Change your name, picture, and optionally your password.</span></div>
    <div class="form-grid">
        <div class="form-field"><label for="profile_name">Full name</label><input id="profile_name" name="name" required maxlength="255" value="{{ old('name', $user->name) }}">@error('name')<small class="field-error">{{ $message }}</small>@enderror</div>
        <div class="form-field"><label for="profile_photo">Profile picture</label>@if ($user->photo_path)<img class="photo-preview" src="{{ asset('storage/'.$user->photo_path) }}" alt="Your profile picture">@endif<input id="profile_photo" name="photo" type="file" accept="image/png,image/jpeg,image/webp"><small class="form-hint">JPG, PNG or WebP · maximum 2 MB</small>@error('photo')<small class="field-error">{{ $message }}</small>@enderror</div>
        <div class="form-field"><label for="new_password">New password <span class="optional-label">Optional</span></label><input id="new_password" name="new_password" type="password" minlength="12" autocomplete="new-password"><small class="form-hint">Leave blank to keep your current password. Use at least 12 characters.</small>@error('new_password')<small class="field-error">{{ $message }}</small>@enderror</div>
        <div class="form-field"><label for="new_password_confirmation">Confirm new password</label><input id="new_password_confirmation" name="new_password_confirmation" type="password" minlength="12" autocomplete="new-password"></div>
    </div>
    <div class="form-actions"><button class="btn-primary" type="submit">Save profile</button></div>
</form>
@else
<form method="POST" action="{{ route('profile.update') }}" class="panel form-panel profile-panel">@csrf @method('PUT')
    <div class="section-title"><b>Academy details you can update</b><span>Only the academy name and campus address can be changed here.</span></div>
    <div class="form-grid">
        <div class="form-field"><label for="academy_name">Academy name</label><input id="academy_name" name="academy_name" required maxlength="255" value="{{ old('academy_name', $user->academy->name) }}">@error('academy_name')<small class="field-error">{{ $message }}</small>@enderror</div>
        <div class="form-field"><label for="academy_address">Campus address</label><textarea id="academy_address" name="address" rows="3" maxlength="1000">{{ old('address', $user->academy->address) }}</textarea>@error('address')<small class="field-error">{{ $message }}</small>@enderror</div>
    </div>
    <div class="profile-readonly"><b>Academy contact email</b><span>{{ $user->academy->contact_email ?: 'Not set' }}</span><b>Phone</b><span>{{ $user->academy->contact_phone ?: 'Not set' }}</span><b>City</b><span>{{ $user->academy->city ?: 'Not set' }}</span><b>Province</b><span>{{ $user->academy->province ?: 'Not set' }}</span></div>
    <p class="form-hint">Contact details, logo, letterhead and module access are managed by the Super Admin. Send a request below if anything else needs to change.</p>
    <div class="form-actions"><button class="btn-primary" type="submit">Save academy details</button></div>
</form>

<form method="POST" action="{{ route('profile.password.send-verification') }}" class="panel form-panel profile-panel">@csrf
    <div class="section-title"><b>Change your password</b><span>Your current password remains active until you confirm the change from your email.</span></div>
    <div class="form-grid">
        <div class="form-field"><label for="current_password">Current password</label><input id="current_password" name="current_password" type="password" required autocomplete="current-password">@error('current_password')<small class="field-error">{{ $message }}</small>@enderror</div>
        <div class="form-field"><label for="admin_new_password">New password</label><input id="admin_new_password" name="new_password" type="password" minlength="12" required autocomplete="new-password"><small class="form-hint">At least 12 characters.</small>@error('new_password')<small class="field-error">{{ $message }}</small>@enderror</div>
        <div class="form-field"><label for="admin_new_password_confirmation">Confirm new password</label><input id="admin_new_password_confirmation" name="new_password_confirmation" type="password" minlength="12" required autocomplete="new-password"></div>
    </div>
    <p class="profile-security-note">We’ll email a verification button to <b>{{ $user->email }}</b>. After you verify, we’ll send a separate one-time sign-in link.</p>
    <div class="form-actions"><button class="btn-primary" type="submit">Email password verification</button></div>
</form>

<form method="POST" action="{{ route('profile.change-requests.store') }}" class="panel form-panel profile-panel">@csrf
    <div class="section-title"><b>Request another academy change</b><span>Describe what you need. The Super Admin will review and respond.</span></div>
    <div class="form-grid">
        <div class="form-field"><label for="request_category">Request type</label><select id="request_category" name="category" required><option value="contact" @selected(old('category') === 'contact')>Contact information</option><option value="branding" @selected(old('category') === 'branding')>Logo or letterhead</option><option value="modules" @selected(old('category') === 'modules')>Module access</option><option value="other" @selected(old('category') === 'other')>Something else</option></select>@error('category')<small class="field-error">{{ $message }}</small>@enderror</div>
        <div class="form-field"><label for="request_subject">Short title</label><input id="request_subject" name="subject" required maxlength="180" value="{{ old('subject') }}" placeholder="e.g. Update academy phone number">@error('subject')<small class="field-error">{{ $message }}</small>@enderror</div>
        <div class="form-field wide"><label for="request_details">What needs to change?</label><textarea id="request_details" name="details" rows="4" required maxlength="3000" placeholder="Tell us the current detail and what you want changed.">{{ old('details') }}</textarea>@error('details')<small class="field-error">{{ $message }}</small>@enderror</div>
    </div>
    <div class="form-actions"><button class="btn-primary" type="submit">Send request</button></div>
</form>

<section class="panel profile-requests"><div class="panel-heading"><div><h2>Your recent requests</h2><p>Track requests sent to the Super Admin.</p></div></div>
    <div class="table-scroll"><table><thead><tr><th>Request</th><th>Type</th><th>Status</th><th>Response</th><th>Sent</th></tr></thead><tbody>
        @forelse ($changeRequests as $changeRequest)<tr><td><strong>{{ $changeRequest->subject }}</strong><small>{{ Str::limit($changeRequest->details, 110) }}</small></td><td>{{ Str::headline($changeRequest->category) }}</td><td><span class="status-pill {{ $changeRequest->status === 'completed' ? 'paid' : (in_array($changeRequest->status, ['open', 'in_review'], true) ? 'pending' : 'neutral') }}">{{ Str::headline($changeRequest->status) }}</span></td><td>{{ $changeRequest->admin_response ?: 'Awaiting review' }}</td><td>{{ $changeRequest->created_at->format('d M Y') }}</td></tr>
        @empty<tr><td class="empty-state" colspan="5">You haven’t sent any requests yet.</td></tr>@endforelse
    </tbody></table></div>
</section>
@endif
@endsection
