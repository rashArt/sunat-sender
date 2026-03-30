<?php

namespace RashArt\SunatSender\DTOs;

class SunatResponse
{
    public function __construct(
        public readonly bool   $success,
        public readonly int    $code,
        public readonly string $message,
        public readonly ?string $ticketNumber = null,
        public readonly ?string $cdrContent   = null,
        public readonly array  $raw           = [],
    ) {}

    public static function success(
        int $code,
        string $message,
        ?string $ticketNumber = null,
        ?string $cdrContent = null,
        array $raw = []
    ): self {
        return new self(
            success: true,
            code: $code,
            message: $message,
            ticketNumber: $ticketNumber,
            cdrContent: $cdrContent,
            raw: $raw,
        );
    }

    public static function failure(
        int $code,
        string $message,
        array $raw = []
    ): self {
        return new self(
            success: false,
            code: $code,
            message: $message,
            raw: $raw,
        );
    }

    public function isAccepted(): bool
    {
        return $this->success && $this->code === 0;
    }

    public function isPending(): bool
    {
        return $this->ticketNumber !== null && $this->cdrContent === null;
    }

    public function toArray(): array
    {
        return [
            'success'       => $this->success,
            'code'          => $this->code,
            'message'       => $this->message,
            'ticket_number' => $this->ticketNumber,
            'cdr_content'   => $this->cdrContent,
            'raw'           => $this->raw,
        ];
    }
}
