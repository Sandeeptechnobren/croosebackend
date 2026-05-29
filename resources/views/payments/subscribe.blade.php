<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Croose - Secure Payment</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://js.stripe.com/v3/"></script>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #0d0b25, #1a183a);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .brand-wrap {
            position: absolute;
            top: 40px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand {
            font-size: 36px;
            font-weight: 700;
            color: #ffffff;
        }

        .brand span {
            font-family: Georgia, serif;
            font-weight: bold;
            background: linear-gradient(to right, #ffffff, #ccc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .container {
            background-color: #fff;
            padding: 36px 28px 34px;
            border-radius: 18px;
            max-width: 460px;
            width: 100%;
            text-align: center;
            box-shadow: 0 18px 40px rgba(0,0,0,.35);
        }

        .container h2 {
            margin-bottom: 18px;
            font-size: 26px;
            font-weight: 700;
            color: #111;
        }

        .info {
            font-size: 15px;
            color: #444;
            margin-bottom: 26px;
            line-height: 1.6;
        }

        .info strong { color: #000; }

        /* ===== FINAL BUTTON WITH RIPPLE + COLOR EFFECT ===== */
        .btn {
            width: 100%;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            border-radius: 14px;
            font-size: 17px;
            font-weight: 600;
            color: #fff;
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all .25s ease;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 300%;
            height: 300%;
            background: rgba(255,255,255,0.15);
            transition: all .9s ease;
            border-radius: 50%;
            transform: translate(-50%, -50%) scale(0);
        }

        .btn:hover::before {
            transform: translate(-50%, -50%) scale(1);
        }

        .btn-stripe {
           background-color: #630db8ff;
            box-shadow: 0 10px 26px rgba(103,114,229,.45);
        }

        .btn-stripe:hover {
             background-color: #098835;
            transform: translateY(-2px);
            box-shadow: 0 14px 34px rgba(84,212,118,.6);
        }

        .btn:active {
            transform: translateY(0);
            box-shadow: 0 8px 18px rgba(0,0,0,.3);
        }

        .note {
            font-size: 13px;
            color: #666;
            margin-top: 16px;
        }

        @media (max-width: 480px) {
            .container { padding: 26px 20px; }
            .container h2 { font-size: 22px; }
            .brand { font-size: 28px; }
        }
    </style>
</head>
<body>

    <div class="brand-wrap">
        <div class="brand"><span>Croose</span></div>
    </div>

    <div class="container">
        <h2>Pay for Your Subscription</h2>

        <div class="info">
            <div><strong>Product:</strong> {{ $subscription->name ?? 'Subscription Plan' }}</div>
            <div><strong>Amount:</strong> {{ $subscription->currency ?? 'INR' }} {{ number_format($subscription->price, 2) }}</div>
        </div>

        <button class="btn btn-stripe" id="payBtn">
            Pay with Stripe
        </button>

        <div class="note">
            You’ll be securely redirected to complete the payment.
        </div>
    </div>

    <script>
        document.getElementById('payBtn').addEventListener('click', function () {
            window.location.href = "{{ url('/api/stripe-checkout/'.$subscription->uuid.'/'.$customer->whatsapp_number) }}";
        });
    </script>

</body>
</html>
