<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once 'expected/PaymentRequest.php';
require_once 'expected/PaymentResponse.php';
require_once 'expected/ShippingAddress.php';

class ConditionalSchemasTest extends TestCase
{
    // Test PaymentRequest conditional validation

    public function test_card_payment_valid(): void
    {
        $data = [
            'type' => 'card',
            'amount' => 99.99,
            'cardNumber' => '4111111111111111',
            'expiryDate' => '12/25',
            'cvv' => '123',
        ];

        $payment = PaymentRequest::fromArray($data);
        $this->assertSame('card', $payment->type);
        $this->assertSame('4111111111111111', $payment->cardNumber);
    }

    public function test_card_payment_missing_fields(): void
    {
        $data = [
            'type' => 'card',
            'amount' => 50.00,
            'cardNumber' => '4111111111111111',
            // Missing expiryDate and cvv
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Card payments require cardNumber, expiryDate, and cvv');
        PaymentRequest::fromArray($data);
    }

    public function test_bank_payment_valid(): void
    {
        $data = [
            'type' => 'bank',
            'amount' => 1000.00,
            'iban' => 'DE89370400440532013000',
            'accountHolder' => 'Alice Smith',
        ];

        $payment = PaymentRequest::fromArray($data);
        $this->assertSame('bank', $payment->type);
        $this->assertSame('DE89370400440532013000', $payment->iban);
    }

    public function test_crypto_payment_valid(): void
    {
        $data = [
            'type' => 'crypto',
            'amount' => 0.5,
            'walletAddress' => '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa',
            'cryptoCurrency' => 'BTC',
        ];

        $payment = PaymentRequest::fromArray($data);
        $this->assertSame('crypto', $payment->type);
        $this->assertSame('BTC', $payment->cryptoCurrency);
    }

    public function test_paypal_payment_valid(): void
    {
        $data = [
            'type' => 'paypal',
            'amount' => 75.00,
            'paypalEmail' => 'user@example.com',
        ];

        $payment = PaymentRequest::fromArray($data);
        $this->assertSame('paypal', $payment->type);
        $this->assertSame('user@example.com', $payment->paypalEmail);
    }

    public function test_unknown_payment_type(): void
    {
        $data = [
            'type' => 'unknown',
            'amount' => 100.00,
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown payment type: unknown');
        PaymentRequest::fromArray($data);
    }

    // Test PaymentResponse conditional validation

    public function test_completed_payment_response(): void
    {
        $data = [
            'transactionId' => 'TXN123456',
            'status' => 'completed',
            'confirmationCode' => 'CONF789',
        ];

        $response = PaymentResponse::fromArray($data);
        $this->assertSame('completed', $response->status);
        $this->assertSame('CONF789', $response->confirmationCode);
    }

    public function test_completed_payment_missing_confirmation(): void
    {
        $data = [
            'transactionId' => 'TXN123456',
            'status' => 'completed',
            // Missing confirmationCode
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Completed payments require confirmationCode');
        PaymentResponse::fromArray($data);
    }

    public function test_failed_payment_response(): void
    {
        $data = [
            'transactionId' => 'TXN123456',
            'status' => 'failed',
            'errorCode' => 'CARD_DECLINED',
            'errorMessage' => 'Insufficient funds',
        ];

        $response = PaymentResponse::fromArray($data);
        $this->assertSame('failed', $response->status);
        $this->assertSame('CARD_DECLINED', $response->errorCode);
    }

    public function test_pending_payment_response(): void
    {
        $data = [
            'transactionId' => 'TXN123456',
            'status' => 'pending',
        ];

        $response = PaymentResponse::fromArray($data);
        $this->assertSame('pending', $response->status);
        // No additional validation required for pending status
    }

    // Test ShippingAddress country-specific validation

    public function test_us_address_valid(): void
    {
        $data = [
            'country' => 'US',
            'addressLine1' => '123 Main Street',
            'state' => 'NY',
            'postalCode' => '10001',
        ];

        $address = ShippingAddress::fromArray($data);
        $this->assertSame('US', $address->country);
        $this->assertSame('NY', $address->state);
        $this->assertSame('10001', $address->postalCode);
    }

    public function test_us_address_missing_state(): void
    {
        $data = [
            'country' => 'US',
            'addressLine1' => '123 Main Street',
            'postalCode' => '10001',
            // Missing state
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('US addresses require state and postalCode');
        ShippingAddress::fromArray($data);
    }

    public function test_us_address_invalid_postal_code(): void
    {
        $data = [
            'country' => 'US',
            'addressLine1' => '123 Main Street',
            'state' => 'NY',
            'postalCode' => 'INVALID',
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('US postal code must be in format 12345 or 12345-6789');
        ShippingAddress::fromArray($data);
    }

    public function test_uk_address_valid(): void
    {
        $data = [
            'country' => 'UK',
            'addressLine1' => '10 Downing Street',
            'postalCode' => 'SW1A 2AA',
        ];

        $address = ShippingAddress::fromArray($data);
        $this->assertSame('UK', $address->country);
        $this->assertSame('SW1A 2AA', $address->postalCode);
    }

    public function test_uk_address_missing_postal_code(): void
    {
        $data = [
            'country' => 'UK',
            'addressLine1' => '10 Downing Street',
            // Missing postalCode
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('UK addresses require postalCode');
        ShippingAddress::fromArray($data);
    }

    public function test_other_country_address(): void
    {
        $data = [
            'country' => 'FR',
            'addressLine1' => '1 Rue de la Paix',
        ];

        $address = ShippingAddress::fromArray($data);
        $this->assertSame('FR', $address->country);
        // No specific validation for France
    }
}
