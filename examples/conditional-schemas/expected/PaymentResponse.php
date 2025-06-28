<?php

declare(strict_types=1);

readonly class PaymentResponse
{
    public function __construct(
        public ?string $transactionId = null,
        public ?string $status = null,
        public ?float $processingFee = null,

        // Conditional fields for completed payments
        public ?string $confirmationCode = null,
        public ?string $processedAt = null,

        // Conditional fields for failed payments
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public ?int $retryAfter = null
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $instance = new self(
            transactionId: $data['transactionId'] ?? null,
            status: $data['status'] ?? null,
            processingFee: $data['processingFee'] ?? null,
            confirmationCode: $data['confirmationCode'] ?? null,
            processedAt: $data['processedAt'] ?? null,
            errorCode: $data['errorCode'] ?? null,
            errorMessage: $data['errorMessage'] ?? null,
            retryAfter: $data['retryAfter'] ?? null
        );

        // Validate conditional requirements
        self::validateConditionalFields($instance);

        return $instance;
    }

    private static function validateConditionalFields(self $response): void
    {
        if ($response->status === 'completed') {
            if (empty($response->confirmationCode)) {
                throw new \InvalidArgumentException('Completed payments require confirmationCode');
            }
        } elseif ($response->status === 'failed') {
            if (empty($response->errorCode) || empty($response->errorMessage)) {
                throw new \InvalidArgumentException('Failed payments require errorCode and errorMessage');
            }
        }
    }
}
