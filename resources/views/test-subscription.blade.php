<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Test Subscription Payment - Digit Transway</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Razorpay Checkout Script -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 50px 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .test-container {
            max-width: 900px;
            margin: 0 auto;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
        .test-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 15px 30px;
            font-size: 18px;
            border-radius: 50px;
            color: white;
            transition: all 0.3s;
            width: 100%;
        }
        .test-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        .info-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 8px 15px;
            border-radius: 20px;
            display: inline-block;
            margin: 5px;
        }
        .status-success {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .status-error {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .log-box {
            background: #1e1e1e;
            color: #00ff00;
            padding: 15px;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            max-height: 300px;
            overflow-y: auto;
            font-size: 13px;
        }
        .feature-list {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .feature-list li:before {
            content: "✓ ";
            color: #4caf50;
            font-weight: bold;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <!-- Header -->
        <div class="text-center text-white mb-4">
            <h1>🚀 Subscription Payment Test</h1>
            <p>Test Razorpay Subscription Integration</p>
        </div>

        <!-- Plan Selection Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">📋 Select Plan to Test</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($plans as $plan)
                    <div class="col-md-6 mb-3">
                        <div class="card h-100" style="border: 2px solid #e0e0e0;">
                            <div class="card-body">
                                <h5 class="card-title">{{ $plan->name }}</h5>
                                <h3 class="text-primary">₹{{ $plan->price }}</h3>
                                <p class="text-muted">Duration: {{ ucfirst($plan->duration_type) }}</p>
                                <ul class="feature-list">
                                    @if($plan->features)
                                        @foreach(array_slice($plan->features, 0, 3) as $feature)
                                            <li>{{ $feature }}</li>
                                        @endforeach
                                    @endif
                                </ul>
                                <button class="btn test-btn mt-3" onclick="subscribeToPlan({{ $plan->id }})">
                                    Subscribe Now
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Current Subscription Status -->
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">📊 Your Current Subscription</h3>
            </div>
            <div class="card-body">
                @if($activeSubscription)
                    <div class="alert alert-success">
                        <h5>✅ Active Subscription Found</h5>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <p><strong>Plan:</strong> {{ $activeSubscription->plan_name }}</p>
                                <p><strong>Status:</strong>
                                    <span class="badge bg-success">{{ $activeSubscription->subscription_status }}</span>
                                </p>
                                <p><strong>Started:</strong> {{ $activeSubscription->starts_at }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Expires:</strong> {{ $activeSubscription->expires_at }}</p>
                                <p><strong>Auto Renew:</strong>
                                    {{ $activeSubscription->auto_renew ? 'Yes' : 'No' }}
                                </p>
                                <p><strong>Razorpay ID:</strong>
                                    <code>{{ $activeSubscription->razorpay_subscription_id }}</code>
                                </p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-info">
                        <p class="mb-0">ℹ️ No active subscription found. Please select a plan above to subscribe.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Test Credentials -->
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">💳 Test Card Details</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Success Card:</h6>
                        <div class="info-badge">Card: 4111 1111 1111 1111</div>
                        <div class="info-badge">CVV: Any 3 digits</div>
                        <div class="info-badge">Expiry: Any future date</div>
                    </div>
                    <div class="col-md-6">
                        <h6>Alternative Success Card:</h6>
                        <div class="info-badge">Card: 5555 5555 5555 4444</div>
                        <div class="info-badge">CVV: Any 3 digits</div>
                        <div class="info-badge">Expiry: Any future date</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Messages -->
        <div id="successMessage" class="card" style="display: none;">
            <div class="card-body status-success">
                <h4>✅ Payment Successful!</h4>
                <p><strong>Subscription ID:</strong> <span id="successSubId"></span></p>
                <p><strong>Payment ID:</strong> <span id="successPayId"></span></p>
                <p><strong>Signature:</strong> <code id="successSignature"></code></p>
                <button class="btn btn-success mt-3" onclick="window.location.reload()">
                    Refresh Page
                </button>
            </div>
        </div>

        <div id="errorMessage" class="card" style="display: none;">
            <div class="card-body status-error">
                <h4>❌ Payment Failed</h4>
                <p id="errorDetails"></p>
                <button class="btn btn-danger mt-3" onclick="hideError()">
                    Try Again
                </button>
            </div>
        </div>

        <!-- Console Log -->
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">📝 Console Log</h3>
            </div>
            <div class="card-body">
                <div id="consoleLog" class="log-box">
                    <div>[{{ date('H:i:s') }}] Page loaded. Ready for testing...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // CSRF Token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Add log entry
        function addLog(message, type = 'info') {
            const logBox = document.getElementById('consoleLog');
            const time = new Date().toLocaleTimeString();
            const color = type === 'error' ? '#ff0000' : type === 'success' ? '#00ff00' : '#00ff00';
            logBox.innerHTML += `<div style="color: ${color}">[${time}] ${message}</div>`;
            logBox.scrollTop = logBox.scrollHeight;
        }

        // Subscribe to plan
        function subscribeToPlan(planId) {
            addLog(`Subscribing to plan ID: ${planId}`);

            // Get auth token (you should get this from login)
            const token = localStorage.getItem('auth_token') || prompt('Enter your auth token:');

            if (!token) {
                addLog('No auth token provided!', 'error');
                return;
            }

            addLog('Calling API: /api/plans/subscribe');

            fetch('/api/plans/subscribe', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ plan_id: planId })
            })
            .then(response => response.json())
            .then(data => {
                addLog('API Response received');
                console.log('API Response:', data);

                if (data.success) {
                    addLog('✅ Subscription created successfully', 'success');
                    openRazorpayCheckout(data.data);
                } else {
                    addLog('❌ API Error: ' + data.message, 'error');
                    showError(data.message);
                }
            })
            .catch(error => {
                addLog('❌ Network Error: ' + error.message, 'error');
                console.error('Error:', error);
                showError('Network error: ' + error.message);
            });
        }

        // Open Razorpay Checkout
        function openRazorpayCheckout(data) {
            addLog('Opening Razorpay Checkout...');

            const options = {
                "key": data.razorpay_key,
                "subscription_id": data.razorpay_subscription_id,
                "name": "Digit Transway",
                "description": data.plan.name + " - " + data.plan.duration_type,
                "image": "/logo.png",
                "handler": function (response) {
                    addLog('✅ Payment successful!', 'success');
                    addLog('Subscription ID: ' + response.razorpay_subscription_id, 'success');
                    addLog('Payment ID: ' + response.razorpay_payment_id, 'success');

                    showSuccess(response);
                },
                "prefill": {
                    "name": data.customer.name,
                    "email": data.customer.email,
                    "contact": data.customer.contact
                },
                "notes": {
                    "subscription_id": data.subscription_id,
                    "plan_id": data.plan.id
                },
                "theme": {
                    "color": "#667eea"
                },
                "modal": {
                    "ondismiss": function() {
                        addLog('⚠️ Payment window closed by user', 'error');
                    }
                }
            };

            const rzp = new Razorpay(options);

            rzp.on('payment.failed', function (response){
                addLog('❌ Payment failed: ' + response.error.description, 'error');
                console.error('Payment Error:', response.error);
                showError(response.error.description);
            });

            rzp.open();
            addLog('Razorpay Checkout opened');
        }

        // Show success message
        function showSuccess(response) {
            document.getElementById('successSubId').textContent = response.razorpay_subscription_id;
            document.getElementById('successPayId').textContent = response.razorpay_payment_id;
            document.getElementById('successSignature').textContent = response.razorpay_signature;
            document.getElementById('successMessage').style.display = 'block';
            document.getElementById('errorMessage').style.display = 'none';

            // Scroll to success message
            document.getElementById('successMessage').scrollIntoView({ behavior: 'smooth' });
        }

        // Show error message
        function showError(message) {
            document.getElementById('errorDetails').textContent = message;
            document.getElementById('errorMessage').style.display = 'block';
            document.getElementById('successMessage').style.display = 'none';

            // Scroll to error message
            document.getElementById('errorMessage').scrollIntoView({ behavior: 'smooth' });
        }

        // Hide error
        function hideError() {
            document.getElementById('errorMessage').style.display = 'none';
        }

        // Log page ready
        addLog('✅ Test page ready. Click a plan to subscribe.', 'success');
    </script>
</body>
</html>
