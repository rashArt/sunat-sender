<?php

namespace RashArt\SunatSender\DTOs;

class SendableDocument
{
    public function __construct(
        public readonly string $type,
        public readonly string $series,
        public readonly string $number,
        public readonly string $rucEmisor,
        public readonly string $xmlContent,
        public readonly array  $metadata = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'],
            series: $data['series'],
            number: $data['number'],
            rucEmisor: $data['ruc_emisor'],
            xmlContent: $data['xml_content'],
            metadata: $data['metadata'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'type'        => $this->type,
            'series'      => $this->series,
            'number'      => $this->number,
            'ruc_emisor'  => $this->rucEmisor,
            'xml_content' => $this->xmlContent,
            'metadata'    => $this->metadata,
        ];
    }

    public function getFileName(): string
    {
        return sprintf(
            '%s-%s-%s-%s.xml',
            $this->rucEmisor,
            $this->type,
            $this->series,
            $this->number
        );
    }
}
