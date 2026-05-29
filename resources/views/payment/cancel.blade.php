<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Cancelled</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            background-color: #fff5f5;
            text-align: center;
            padding: 60px;
        }
        .container {
            background: #ffffff;
            padding: 30px 50px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(255, 0, 0, 0.1);
            display: inline-block;
        }
        .crossmark {
            font-size: 60px;
            color: #e53e3e;
        }
        .message {
            font-size: 20px;
            color: #c53030;
            margin-top: 10px;
        }
        .btn {
            margin-top: 25px;
            padding: 10px 20px;
            background-color: #e53e3e;
            color: white;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #c53030;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="crossmark">?</div>
        <h2>Payment Cancelled</h2>
        <p class="message">Your payment was not completed. You may try again later.</p>
        <!-- <a href="{{ url('/') }}" class="btn">Return to Home</a> -->
    </div>
</body>
</html>
