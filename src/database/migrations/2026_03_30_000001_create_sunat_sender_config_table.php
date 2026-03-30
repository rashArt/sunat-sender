<?php

declare(strict_types=1);

namespace RashArt\SunatSender\Models;

use Illuminate\Database\Eloquent\Model;
use RashArt\SunatSender\DTOs\SunatAccountData;

/**
 * Modelo Eloquent que representa la configuración de un emisor
 * almacenada en la tabla `sunat_sender_config`.
 *
 * Los campos `sol_password` y `certificate` se almacenan cifrados.
 */
class SunatAccount extends Model
{
    protected $table = 'sunat_sender_config';

    protected $fillable = [
        'ruc',
        'sol_user',
        'sol_password',
        'certificate',
        'business_name',
        'trade_name',
        'provider',
        'provider_url',
        'provider_token',
        'provider_key',
        'is_active',
    ];

    /**
     * Campos cifrados en base de datos (usando encrypt/decrypt de Laravel).
     *
     * @var list<string>
     */
    protected $encryptable = [
        'sol_password',
        'certificate',
        'provider_token',
        'provider_key',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Convierte el modelo al Value Object de solo lectura.
     */
    public function toValueObject(): SunatAccountData
    {
        return SunatAccountData::fromArray($this->toArray());
    }

    /**
     * Scope para obtener únicamente cuentas activas.
     */
    public function scopeActive($query): mixed
    {
        return $query->where('is_active', true);
    }

    /**
     * Busca una cuenta activa por RUC.
     */
    public static function findByRuc(string $ruc): ?self
    {
        return static::active()->where('ruc', $ruc)->first();
    }
}