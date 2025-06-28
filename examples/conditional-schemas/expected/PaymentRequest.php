<?php

declare(strict_types=1);

readonly class PaymentRequest
{
    public function __construct(
        public string $type,
        public float $amount,
        public ?string $currency = 'USD',

        // Card payment fields
        public ?string $cardNumber = null,
        public ?string $expiryDate = null,
        public ?string $cvv = null,
        public ?string $cardholderName = null,

        // Bank payment fields
        public ?string $iban = null,
        public ?string $accountHolder = null,
        public ?string $bankCode = null,

        // Crypto payment fields
        public ?string $walletAddress = null,
        public ?string $cryptoCurrency = null,
        public ?string $network = null,

        // PayPal payment fields
        public ?string $paypalEmail = null,
        public ?string $paypalAccountId = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $instance = new self(
            type: $data['type'],
            amount: $data['amount'],
            currency: $data['currency'] ?? 'USD',
            cardNumber: $data['cardNumber'] ?? null,
            expiryDate: $data['expiryDate'] ?? null,
            cvv: $data['cvv'] ?? null,
            cardholderName: $data['cardholderName'] ?? null,
            iban: $data['iban'] ?? null,
            accountHolder: $data['accountHolder'] ?? null,
            bankCode: $data['bankCode'] ?? null,
            walletAddress: $data['walletAddress'] ?? null,
            cryptoCurrency: $data['cryptoCurrency'] ?? null,
            network: $data['network'] ?? null,
            paypalEmail: $data['paypalEmail'] ?? null,
            paypalAccountId: $data['paypalAccountId'] ?? null
        );

        // Validate conditional requirements
        self::validateConditionalFields($instance);

        return $instance;
    }

    private static function validateConditionalFields(self $payment): void
    {
        match ($payment->type) {
            'card' => self::validateCardFields($payment),
            'bank' => self::validateBankFields($payment),
            'crypto' => self::validateCryptoFields($payment),
            'paypal' => self::validatePaypalFields($payment),
            default => throw new \InvalidArgumentException("Unknown payment type: {$payment->type}")
        };
    }

    private static function validateCardFields(self $payment): void
    {
        if (empty($payment->cardNumber) || empty($payment->expiryDate) || empty($payment->cvv)) {
            throw new \InvalidArgumentException('Card payments require cardNumber, expiryDate, and cvv');
        }
    }

    private static function validateBankFields(self $payment): void
    {
        if (empty($payment->iban) || empty($payment->accountHolder)) {
            throw new \InvalidArgumentException('Bank payments require iban and accountHolder');
        }
    }

    private static function validateCryptoFields(self $payment): void
    {
        if (empty($payment->walletAddress) || empty($payment->cryptoCurrency)) {
            throw new \InvalidArgumentException('Crypto payments require walletAddress and cryptoCurrency');
        }
    }

    private static function validatePaypalFields(self $payment): void
    {
        if (empty($payment->paypalEmail)) {
            throw new \InvalidArgumentException('PayPal payments require paypalEmail');
        }
    }
}
