<?php

require_once 'expected/PaymentRequest.php';
require_once 'expected/PaymentResponse.php';
require_once 'expected/ShippingAddress.php';

echo "Testing PaymentRequest:\n";

// Test Card Payment
try {
    $payment = PaymentRequest::fromArray([
        'type' => 'card',
        'amount' => 99.99,
        'cardNumber' => '4111111111111111',
        'expiryDate' => '12/25',
        'cvv' => '123',
    ]);
    echo "✓ Card payment created successfully\n";
} catch (Exception $e) {
    echo '✗ Card payment failed: '.$e->getMessage()."\n";
}

// Test Card Payment missing fields
try {
    $payment = PaymentRequest::fromArray([
        'type' => 'card',
        'amount' => 50.00,
        'cardNumber' => '4111111111111111',
        // Missing expiryDate and cvv
    ]);
    echo "✗ Card payment validation failed - should have thrown exception\n";
} catch (Exception $e) {
    echo '✓ Card payment validation works: '.$e->getMessage()."\n";
}

echo "\nTesting PaymentResponse:\n";

// Test Completed Payment Response
try {
    $response = PaymentResponse::fromArray([
        'transactionId' => 'TXN123456',
        'status' => 'completed',
        'confirmationCode' => 'CONF789',
    ]);
    echo "✓ Completed payment response created successfully\n";
} catch (Exception $e) {
    echo '✗ Completed payment response failed: '.$e->getMessage()."\n";
}

// Test Completed Payment Response missing confirmation
try {
    $response = PaymentResponse::fromArray([
        'transactionId' => 'TXN123456',
        'status' => 'completed',
        // Missing confirmationCode
    ]);
    echo "✗ Completed payment validation failed - should have thrown exception\n";
} catch (Exception $e) {
    echo '✓ Completed payment validation works: '.$e->getMessage()."\n";
}

echo "\nTesting ShippingAddress:\n";

// Test US Address
try {
    $address = ShippingAddress::fromArray([
        'country' => 'US',
        'addressLine1' => '123 Main Street',
        'state' => 'NY',
        'postalCode' => '10001',
    ]);
    echo "✓ US address created successfully\n";
} catch (Exception $e) {
    echo '✗ US address failed: '.$e->getMessage()."\n";
}

// Test US Address missing state
try {
    $address = ShippingAddress::fromArray([
        'country' => 'US',
        'addressLine1' => '123 Main Street',
        'postalCode' => '10001',
        // Missing state
    ]);
    echo "✗ US address validation failed - should have thrown exception\n";
} catch (Exception $e) {
    echo '✓ US address validation works: '.$e->getMessage()."\n";
}

echo "\nAll tests completed!\n";
