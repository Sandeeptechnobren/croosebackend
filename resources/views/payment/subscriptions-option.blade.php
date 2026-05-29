<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Croose | Pay for Your Subscription</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(145deg, #0d0b25, #1a183a);
      display: flex; align-items: center; justify-content: center;
      min-height: 100vh; padding: 20px;
      animation: fadeInBody 1.2s ease-in;
    }
    @keyframes fadeInBody { 0%{opacity:0;transform:translateY(30px);}100%{opacity:1;transform:translateY(0);} }
    .croose-logo { font-size: 36px; font-weight: 700; color: #fff; margin-bottom: 25px; text-align: center; animation: floatLogo 2.5s ease-in-out infinite alternate; }
    @keyframes floatLogo { 0%{transform:translateY(0);}100%{transform:translateY(-5px);} }
    .croose-logo span { font-family: Georgia, serif; font-weight: bold; background: linear-gradient(to right, #ffffff, #ccc); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .container { background-color: #fff; padding: 32px 24px; border-radius: 16px; max-width: 460px; width: 100%; box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2); animation: fadeInCard 1.4s ease-out; }
    @keyframes fadeInCard { from{opacity:0;transform:scale(0.95);}to{opacity:1;transform:scale(1);} }
    h1 { font-size: 24px; margin-bottom: 20px; color: #111; text-align: center; }
    .info { font-size: 16px; color: #333; margin-bottom: 30px; line-height: 1.6; text-align: center; }
    .btn { display: flex; align-items: center; justify-content: center; gap: 10px; text-decoration: none; padding: 14px 20px; margin: 12px 0; border-radius: 10px; font-size: 16px; font-weight: 600; color: #fff; position: relative; overflow: hidden; transition: all 0.3s ease; }
    .btn::before { content: ''; position: absolute; top: 50%; left: 50%; width: 300%; height: 300%; background: rgba(255, 255, 255, 0.1); transition: all 1s ease; border-radius: 50%; transform: translate(-50%, -50%) scale(0); }
    .btn:hover::before { transform: translate(-50%, -50%) scale(1); }
    .btn-stripe { background-color: #6772e5; }
    .btn-stripe:hover { background-color: #54d476ff; }
    .btn-paystack { background-color: #630db8ff; }
    .btn-paystack:hover { background-color: #098835; }
    .btn img { width: 22px; height: 22px; }
    .note { margin-top: 18px; font-size: 14px; color: #555; text-align: center; }
    .loader { display: none; margin: 16px auto; border: 4px solid #f3f3f3; border-top: 4px solid #0aa83f; border-radius: 50%; width: 32px; height: 32px; animation: spin 1s linear infinite; }
    @keyframes spin { 0%{transform:rotate(0deg);}100%{transform:rotate(360deg);} }
    @media (max-width: 480px) {
      .container { padding: 24px 18px; }
      h1 { font-size: 20px; }
      .btn { font-size: 15px; }
      .croose-logo { font-size: 28px; }
    }
  </style>
</head>
<body>
 
  <div>
    <div class="croose-logo"><span>Croose</span></div>

    <div class="container">
      <h1>Pay for Your Subscription</h1>

      <div class="info">
        <strong>Subscription:</strong> {{ $subscription_name }} <br />
        <strong>Amount:</strong> {{ $subscription_amount }} {{ strtoupper($currency) }}
      </div>

      {{-- Stripe (USD) --}}
      <!-- <a href="javascript:void(0)" class="btn btn-stripe" onclick="redirectWithLoader('{{ route('payment.stripe.subscription', ['uuid' => $uuid]) }}')">
        <img src="https://cdn-icons-png.flaticon.com/512/349/349221.png" alt="Stripe Icon" />
        Pay with Stripe (USD)
      </a> --> 

      {{-- Paystack (GHS) --}}
      <a href="javascript:void(0)" class="btn btn-paystack" onclick="redirectWithLoader('{{ route('payment.paystack.subscription', ['uuid' => $uuid]) }}')">
        <img src="https://cdn-icons-png.flaticon.com/512/5968/5968571.png" alt="Paystack Icon" />
        Pay with Paystack (GHS)
      </a>

      <div class="loader" id="loader"></div>

      <p class="note">You’ll be securely redirected to complete the payment.</p>
    </div>
  </div>

  <script>
    function redirectWithLoader(url) {
      document.getElementById('loader').style.display = 'block';
      window.location.href = url;
    }
  </script>

</body>
</html>
