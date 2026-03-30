<?php

declare(strict_types=1);

namespace RashArt\SunatSender\DTOs;

/**
 * Credenciales de una cuenta SUNAT (SOL).
 *
 * Inmutable por diseño (readonly). Se instancia desde el array de config
 * o desde el modelo de cuenta del host app.
 */
final readonly class SunatAccount
{
    public function __construct(
        /** RUC del emisor (11 dígitos). */
        public string $ruc,

        /** Usuario SOL (ej: MODDATOS, o usuario secundario). */
        public string $solUser,

        /** Contraseña SOL. */
        public string $solPassword,

        /**
         * Certificado digital en formato PEM (contenido, no ruta).
         * Incluye clave privada y certificado público concatenados.
         */
        public string $certificate,

        /**
         * Razón social del emisor (para logs y XML).
         */
        public string $businessName = '',

        /**
         * Nombre comercial del emisor (opcional).
         */
        public string $tradeName = '',
    ) {
    }

    /**
     * Construye una instancia desde un array (config, modelo, etc.).
     *
     * @param  array{
     *     ruc: string,
     *     sol_user: string,
     *     sol_password: string,
     *     certificate: string,
     *     business_name?: string,
     *     trade_name?: string,
     * }  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            ruc:          $data['ruc'],
            solUser:      $data['sol_user'],
            solPassword:  $data['sol_password'],
            certificate:  $data['certificate'],
            businessName: $data['business_name'] ?? '',
            tradeName:    $data['trade_name'] ?? '',
        );
    }

    /**
     * Usuario compuesto requerido por el WSDL de SUNAT: "RUC + USER".
     */
    public function composedUser(): string
    {
        return $this->ruc . $this->solUser;
    }
}