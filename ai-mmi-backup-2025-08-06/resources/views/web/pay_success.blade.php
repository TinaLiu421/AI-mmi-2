@extends('web.common')
@section('content')

<style>
.payment-success-container {
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--space-8);
    margin-top: 15vh;
}

.payment-success-card {
    background: var(--white);
    border-radius: var(--radius-2xl);
    box-shadow: var(--shadow-lg);
    padding: var(--space-12);
    max-width: 500px;
    width: 100%;
    text-align: center;
}

.success-icon {
    font-size: 4rem;
    color: var(--primary-blue);
    margin-bottom: var(--space-6);
}

.payment-success-card h2 {
    font-size: 2rem;
    font-weight: 700;
    color: var(--neutral-900);
    margin-bottom: var(--space-4);
}

.payment-success-card p {
    font-size: 1.125rem;
    color: var(--neutral-600);
    margin-bottom: var(--space-8);
}

.btn-home {
    display: inline-block;
    background: var(--gradient-primary);
    color: var(--white);
    padding: var(--space-4) var(--space-8);
    border-radius: var(--radius-full);
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition-normal);
    box-shadow: var(--shadow-md);
}

.btn-home:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

@media (max-width: 768px) {
    .payment-success-card {
        padding: var(--space-6);
    }
}
</style>

<div class="payment-success-container">
    <div class="payment-success-card">
        <div class="success-icon">
            <i class="fa fa-check-circle"></i>
        </div>

        <h2>Payment Successful!</h2>

        <p>Thanks! We're activating your access…</p>

        <a href="{{ $_page_base_url }}" class="btn-home">Go to Home</a>
    </div>
</div>

<script>
setTimeout(function() {
    window.location.href = '{{ $_page_base_url }}';
}, 3000);
</script>

@endsection
