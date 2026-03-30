<?php

declare(strict_types=1);

namespace RashArt\SunatSender\Exceptions;

/**
 * Excepción lanzada cuando los datos del documento no superan
 * la validación previa al envío (campos requeridos, valores inválidos, etc.).
 */
class ValidationException extends SunatSenderException
{
    /** @var array<string, string[]> */
    private array $errors;

    /**
     * @param array<string, string[]> $errors
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;

        $summary = collect($errors)
            ->map(fn (array $msgs, string $field) => "{$field}: " . implode(', ', $msgs))
            ->implode(' | ');

        parent::__construct("Validación fallida: {$summary}");
    }

    /** @return array<string, string[]> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /** @param array<string, string[]> $errors */
    public static function withErrors(array $errors): self
    {
        return new self($errors);
    }
}