<<<<<<< HEAD
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Payment Successful | Ordiio</title>
  <style>
    /* Reset */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #4CAF50, #2E7D32);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 20px;
    }

    .success-container {
      background: #ffffff;
      color: #333;
      border-radius: 16px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      text-align: center;
      max-width: 500px;
      width: 100%;
      padding: 40px 30px;
      animation: fadeInUp 1s ease;
    }

    .success-icon {
      font-size: 70px;
      color: #4CAF50;
      margin-bottom: 20px;
      animation: bounce 1s infinite alternate;
    }

    h1 {
      font-size: 28px;
      margin-bottom: 10px;
      color: #2E7D32;
    }

    p {
      font-size: 16px;
      margin-bottom: 25px;
      color: #555;
    }

    .btn {
      display: inline-block;
      background: #4CAF50;
      color: white;
      padding: 12px 20px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: bold;
      transition: background 0.3s ease;
    }

    .btn:hover {
      background: #2E7D32;
    }

    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes bounce {
      from { transform: translateY(0); }
      to { transform: translateY(-8px); }
    }

    /* Responsive */
    @media (max-width: 600px) {
      h1 {
        font-size: 22px;
      }
      p {
        font-size: 14px;
      }
      .btn {
        padding: 10px 16px;
      }
    }
  </style>
</head>
<body>
  <div class="success-container">
    <div class="success-icon">✅</div>
    <h1>Payment Successful!</h1>
    <p>Thank you for your purchase. Your Ordiio license has been activated successfully.</p>
    <a href="https://new.ordiio.com/" class="btn">Go to Dashboard</a>
  </div>

  <script>
    // Little animation effect
    document.addEventListener("DOMContentLoaded", () => {
      const btn = document.querySelector(".btn");
      btn.addEventListener("click", () => {
        btn.textContent = "Redirecting...";
        setTimeout(() => {
          window.location.href = "/";
        }, 1000);
      });
    });
  </script>
</body>
</html>
||||||| parent of b872fe7 (Live code)
=======
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Payment Successful | Ordiio</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #4CAF50, #2E7D32);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 20px;
    }

    .success-container {
      background: #ffffff;
      color: #333;
      border-radius: 16px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      text-align: center;
      max-width: 500px;
      width: 100%;
      padding: 40px 30px;
      animation: fadeInUp 1s ease;
    }

    .success-icon {
      font-size: 70px;
      color: #4CAF50;
      margin-bottom: 20px;
      animation: bounce 1s infinite alternate;
    }

    h1 {
      font-size: 28px;
      margin-bottom: 10px;
      color: #2E7D32;
    }

    p {
      font-size: 16px;
      margin-bottom: 25px;
      color: #555;
    }

    .btn {
      display: inline-block;
      background: #4CAF50;
      color: white;
      padding: 12px 20px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: bold;
      transition: background 0.3s ease;
    }

    .btn:hover {
      background: #2E7D32;
    }

    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes bounce {
      from { transform: translateY(0); }
      to { transform: translateY(-8px); }
    }

    @media (max-width: 600px) {
      h1 {
        font-size: 22px;
      }
      p {
        font-size: 14px;
      }
      .btn {
        padding: 10px 16px;
      }
    }
  </style>
</head>
<body>
  <div class="success-container">
    <div class="success-icon">✅</div>
    <h1>Payment Successful!</h1>

    <p>
      Your Ordiio subscription has been activated successfully.
    </p>

    <a href="https://app.ordiio.com/subscriptions" class="btn">Go to Dashboard</a>
  </div>
</body>
</html>
>>>>>>> b872fe7 (Live code)
