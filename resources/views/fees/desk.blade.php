@extends('layouts.app', ['title' => 'Fee workspace'])

@section('content')
<section class="fee-workspace-heading">
    <div>
        <span class="fee-kicker">A SIMPLE WAY TO HANDLE FEES</span>
        <h1>Fee workspace</h1>
        <p>Follow these steps in order. The portal keeps each bill, payment and receipt connected to the student.</p>
    </div>
</section>

<section class="fee-meaning-strip" aria-label="Fee terms explained">
    <article><span>Bill / challan</span><p>The amount a student needs to pay.</p></article>
    <b aria-hidden="true">→</b>
    <article><span>Payment</span><p>The money you actually receive.</p></article>
    <b aria-hidden="true">→</b>
    <article><span>Receipt</span><p>Proof of the payment, created automatically.</p></article>
    <div class="fee-balance-formula">Remaining balance = bill amount − payments received</div>
</section>

@if ($stats !== [])
    <section class="fee-workspace-stats" aria-label="Fee summary">
        @if (isset($stats['open_bills']))<article><small>Bills still waiting for payment</small><strong>{{ number_format($stats['open_bills']) }}</strong></article>@endif
        @if (isset($stats['outstanding']))<article><small>Total still due</small><strong>₨ {{ number_format($stats['outstanding'], 0) }}</strong></article>@endif
        @if (isset($stats['collected_this_month']))<article><small>Received this month</small><strong>₨ {{ number_format($stats['collected_this_month'], 0) }}</strong></article>@endif
    </section>
@endif

<section class="fee-workflow-grid" aria-label="Fee steps">
    <article class="fee-workflow-card">
        <div class="fee-workflow-number">1</div>
        <div class="fee-workflow-copy">
            <span class="fee-kicker">SET UP ONCE</span>
            <h2>Set the monthly fee</h2>
            <p>Enter each student’s regular tuition in their student record. Extra charges such as transport or exams are optional.</p>
            <div class="fee-workflow-actions">
                @if ($features['students'])<a class="btn-outline" href="{{ route('modules.index', 'students') }}">Open student records</a>@elseif ($features['monthly_students'])<a class="btn-outline" href="{{ route('fees.monthly-students') }}">View monthly fee list</a>@endif
                @if ($feeHeadScope)<a class="fee-text-link" href="{{ route('fee-heads.index', $feeHeadScope) }}">Set up extra charges</a>@endif
            </div>
        </div>
    </article>

    <article class="fee-workflow-card">
        <div class="fee-workflow-number">2</div>
        <div class="fee-workflow-copy">
            <span class="fee-kicker">CREATE WHAT IS OWED</span>
            <h2>Create fee bills</h2>
            <p>A bill is not a payment. Choose one student, or create this month’s bills for a class or all active students. Existing bills are skipped.</p>
            <div class="fee-workflow-actions">
                @if ($features['single_bill'])<a class="btn-outline" href="{{ route('modules.create', 'fees') }}">Create one bill</a>@endif
                @if ($features['bulk_bills'])<a class="btn-primary" href="{{ route('fees.bulk.form') }}">Create class / monthly bills</a>@endif
            </div>
        </div>
    </article>

    <article class="fee-workflow-card">
        <div class="fee-workflow-number">3</div>
        <div class="fee-workflow-copy">
            <span class="fee-kicker">WHEN MONEY ARRIVES</span>
            <h2>Record a payment</h2>
            <p>Choose the student’s bill, enter the amount you received and select the payment method. A receipt is created automatically. Smaller amounts are allowed for part payments.</p>
            <div class="fee-workflow-actions">
                @if ($features['collect_payment'])<a class="btn-primary" href="{{ route('modules.create', 'payments') }}">Record payment</a>@endif
                @if ($features['view_receipts'])<a class="fee-text-link" href="{{ route('modules.index', 'payments') }}">Find a receipt</a>@endif
            </div>
        </div>
    </article>

    <article class="fee-workflow-card">
        <div class="fee-workflow-number">4</div>
        <div class="fee-workflow-copy">
            <span class="fee-kicker">FOLLOW UP</span>
            <h2>Check what is still due</h2>
            <p>Open a bill to see its payments and balance. Print the bill for a parent, or print a receipt after recording money received.</p>
            <div class="fee-workflow-actions">
                @if ($features['view_bills'])<a class="btn-outline" href="{{ route('modules.index', 'fees') }}">Find a bill</a>@endif
                @if ($features['pending_report'])<a class="fee-text-link" href="{{ route('reports', ['fee_report' => 'pending']) }}">View unpaid balances</a>@elseif ($features['defaulters'])<a class="fee-text-link" href="{{ route('reports', ['fee_report' => 'defaulters']) }}">View unpaid balances</a>@endif
            </div>
        </div>
    </article>
</section>

<p class="fee-workflow-note"><strong>Remember:</strong> Creating a bill only records what is owed. Record a payment separately when money is actually received.</p>
@endsection
