<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Make Payment - CROOSE</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            background: #f8f9fa;
        }
        .payment-card {
            max-width: 600px;
            margin: 60px auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            border-radius: 16px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card payment-card bg-white p-4">
        <div class="card-body">
            <h3 class="mb-4 text-primary">Complete Your Payment</h3>

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if(!isset($paymentData))
                <div class="alert alert-warning">
                    UUID not found or invalid. Please try again.
                </div>
            @else
                <div class="mb-3">
                    <p><strong>Reference ID:</strong> {{ $paymentData->uuid }}</p>
                    <p><strong>Amount:</strong> ₹{{ number_format($paymentData->amount, 2) }}</p>
                    <p><strong>Description:</strong> {{ $paymentData->description ?? 'Payment for your service' }}</p>
                </div>

                <form method="POST" action="{{ route('payments.redirect') }}">
                    @csrf
                    <input type="hidden" name="uuid" value="{{ $paymentData->uuid }}">
                    <input type="hidden" name="type" value="{{ $type ?? 'order' }}">

                    <div class="mb-3">
                        <label for="gateway" class="form-label">Select Payment Method</label>
                        <select class="form-select" name="gateway" id="gateway" required>
                            <option value="">-- Choose Payment Gateway --</option>
                            <option value="stripe">Stripe</option>
                            <option value="paystack">Paystack</option>
                            <option value="mobilemoney">Mobile Money</option>
                            <option value="whatsapppay">WhatsApp Pay</option>
                            <!-- Add more options as needed -->
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success w-100">Proceed to Payment</button>
                </form>
            @endif
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
