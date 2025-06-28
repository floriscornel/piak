<?php

declare(strict_types=1);

readonly class ApiResponse
{
    public function __construct(
        public SuccessResponse|ErrorResponse $response
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        if (! isset($data['success']) || ! is_bool($data['success'])) {
            throw new \InvalidArgumentException('success must be a boolean');
        }

        $response = match ($data['success']) {
            true => SuccessResponse::fromArray($data),
            false => ErrorResponse::fromArray($data)
        };

        return new self(response: $response);
    }

    public function isSuccessResponse(): bool
    {
        return $this->response instanceof SuccessResponse;
    }

    public function getSuccessResponse(): SuccessResponse
    {
        if (! $this->isSuccessResponse()) {
            throw new \InvalidArgumentException('Response is not a SuccessResponse');
        }

        /** @var SuccessResponse $response */
        $response = $this->response;

        return $response;
    }

    public function isErrorResponse(): bool
    {
        return $this->response instanceof ErrorResponse;
    }

    public function getErrorResponse(): ErrorResponse
    {
        if (! $this->isErrorResponse()) {
            throw new \InvalidArgumentException('Response is not an ErrorResponse');
        }

        /** @var ErrorResponse $response */
        $response = $this->response;

        return $response;
    }

    // Legacy helper methods for backward compatibility
    public function isSuccess(): bool
    {
        return $this->isSuccessResponse();
    }

    public function hasDetailedError(): bool
    {
        return $this->isErrorResponse() && $this->getErrorResponse()->error instanceof ErrorDetail;
    }
}
