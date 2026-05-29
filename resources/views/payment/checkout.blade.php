<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Redirecting to Payment...</title>
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
    <div style="text-align: center; margin-top: 100px;">
        <h2>Redirecting to secure Stripe payment...</h2>
        <p>Please wait, do not refresh the page.</p>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const stripe = Stripe("{{ config('services.stripe.key') }}");

            stripe.redirectToCheckout({
                sessionId: "{{ $checkoutSession->id }}"
            }).then(function (result) {
                
                if (result.error) {
                    alert(result.error.message);
                }
            });
        });
    </script>
</body>
</html>
