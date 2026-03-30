<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sunat_sender_logs', function (Blueprint $table) {
            $table->id();

            // Identificación del documento enviado
            $table->string('ruc', 11);
            $table->string('document_type', 2);   // '01', '03', '07', etc.
            $table->string('serie', 4);
            $table->unsignedInteger('correlativo');
            $table->string('file_name');           // ej: 20123456789-01-F001-1.zip

            // Proveedor usado en este envío
            $table->enum('provider', ['sunat', 'ose', 'pse']);

            // Tiempos
            $table->timestamp('request_at')->nullable();
            $table->timestamp('response_at')->nullable();

            // Estado del envío
            $table->enum('status', [
                'pending',
                'sent',
                'accepted',
                'rejected',
                'error',
            ])->default('pending');

            // Respuesta SUNAT
            $table->string('sunat_code')->nullable();
            $table->text('sunat_description')->nullable();
            $table->string('ticket_number')->nullable(); // para documentos asíncronos

            // Payload crudo (opcional, para debug)
            $table->longText('raw_request')->nullable();
            $table->longText('raw_response')->nullable();

            $table->timestamps();

            // Índices útiles
            $table->index(['ruc', 'document_type', 'serie', 'correlativo'], 'idx_sunat_log_document');
            $table->index('status');
            $table->index('ticket_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sunat_sender_logs');
    }
};