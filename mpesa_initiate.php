<?php
session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $phone = preg_replace('/[^0-9]/', '', $_POST['mpesa_phone'] ?? '');
    $county = $_POST['county'] ?? '';
    if (empty($phone) || !preg_match('/^[0-9]{10,12}$/', $phone)) {
        throw new Exception('Please enter a valid M-Pesa phone number (format: 07XXXXXXXX)');
    }
    // Calculate amount (including delivery fee)
    $delivery_fee = ($county === 'Nairobi') ? 0 : 500;
    $order_totals = calculate_cart_totals($delivery_fee);
    $amount = $order_totals['total_amount'];
    if ($amount <= 0) {
        throw new Exception('Invalid order amount.');
    }
    // Call real M-Pesa API
    $mpesa_response = initiate_mpesa_payment($phone, $amount);
    if (!$mpesa_response || (isset($mpesa_response['ResponseCode']) && $mpesa_response['ResponseCode'] !== '0')) {
        $errorMsg = $mpesa_response['errorMessage'] ?? 'Failed to initiate M-Pesa payment.';
        throw new Exception($errorMsg);
    }
    $transaction_id = $mpesa_response['CheckoutRequestID'] ?? ('MPESA' . time() . rand(100, 999));
    $_SESSION['mpesa_phone'] = $phone;
    $_SESSION['mpesa_transaction_id'] = $transaction_id;
    $_SESSION['mpesa_payment_initiated'] = true;
    $_SESSION['mpesa_amount'] = $amount;
    echo json_encode([
        'success' => true,
        'message' => 'Payment request sent to ' . $phone . '. Please complete payment on your phone.',
        'amount' => $amount,
        'phone' => $phone
    ]);
    exit;
} catch (Exception $e) {
    $_SESSION['mpesa_payment_initiated'] = false;
    echo json_encode([
        'success' => false,
        'message' => 'M-Pesa initiation failed: ' . $e->getMessage()
    ]);
    exit;
}
