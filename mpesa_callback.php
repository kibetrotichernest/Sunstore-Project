<?php
// Mpesa callback handler for STK Push
file_put_contents(__DIR__ . '/includes/logs/mpesa_callback.log', date('c') . "\n" . file_get_contents('php://input') . "\n\n", FILE_APPEND);

// Respond with success to Safaricom
header('Content-Type: application/json');
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Received']);
