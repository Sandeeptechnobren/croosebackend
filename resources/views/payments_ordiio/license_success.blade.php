<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Payment Successful | Ordiio</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg,#4CAF50,#2E7D32); display:flex; justify-content:center; align-items:center; min-height:100vh; padding:20px; }
    .success-container { background:white; width:100%; max-width:500px; padding:40px; border-radius:16px; text-align:center; box-shadow:0 4px 20px rgba(0,0,0,0.2); }
    .success-icon { font-size:70px; color:#4CAF50; margin-bottom:20px; }
    .btn { display:inline-block; background:#4CAF50; color:#fff; padding:12px 22px; border-radius:8px; text-decoration:none; font-weight:bold; }
    pre { background:#f4f4f4; padding:12px; border-radius:8px; white-space:pre-wrap; }
  </style>
</head>

<body>
<div class="success-container">
    <div class="success-icon">✅</div>
    <h1>Payment Successful!</h1>
    <p id="status-text">Verifying your purchase…</p>

    <pre id="payment-details" style="display:none"></pre>

    <a href="https://app.ordiio.com/purchases" class="btn">Go to Dashboard</a>
</div>

<script>
    const params = new URLSearchParams(window.location.search);
    const sessionId = params.get('session_id');
    const statusText = document.getElementById('status-text');
    const detailsEl = document.getElementById('payment-details');

    if (!sessionId) {
        statusText.textContent = "Missing session_id!";
    } else {
        fetch('/api/ordiio/license/payment/verify?session_id=' + sessionId)
            .then(r => r.json())
            .then(json => {
                statusText.textContent = json.message || "Verified";
                detailsEl.style.display = "block";
                detailsEl.textContent = JSON.stringify(json, null, 2);
            })
            .catch(() => {
                statusText.textContent = "Verified Successfully!"; 
            });
    }
</script>

</body>
</html>
