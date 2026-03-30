<?php

namespace RashArt\SunatSender\Tests\Unit;

use Mockery;
use RashArt\SunatSender\Contracts\ProviderInterface;
use RashArt\SunatSender\Contracts\SunatSenderInterface;
use RashArt\SunatSender\DTOs\SendableDocument;
use RashArt\SunatSender\DTOs\SunatResponse;
use RashArt\SunatSender\Facades\SunatSender;
use RashArt\SunatSender\Services\SunatSenderService;
use RashArt\SunatSender\Tests\TestCase;

class SunatSenderServiceTest extends TestCase
{
    private ProviderInterface $providerMock;
    private SunatSenderService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->providerMock = Mockery::mock(ProviderInterface::class);
        $this->service = new SunatSenderService($this->providerMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_send_delegates_to_provider(): void
    {
        $document = $this->makeDocument();
        $expected = SunatResponse::success(0, 'OK');

        $this->providerMock
            ->shouldReceive('send')
            ->once()
            ->with($document)
            ->andReturn($expected);

        $result = $this->service->send($document);

        $this->assertTrue($result->success);
        $this->assertEquals(0, $result->code);
    }

    public function test_send_batch_delegates_to_provider(): void
    {
        $documents = [$this->makeDocument()];
        $expected  = SunatResponse::success(0, 'Lote enviado', ticketNumber: 'TICKET-001');

        $this->providerMock
            ->shouldReceive('sendBatch')
            ->once()
            ->with($documents)
            ->andReturn($expected);

        $result = $this->service->sendBatch($documents);

        $this->assertTrue($result->success);
        $this->assertEquals('TICKET-001', $result->ticketNumber);
    }

    public function test_get_status_delegates_to_provider(): void
    {
        $ticket   = 'TICKET-001';
        $expected = SunatResponse::success(0, 'Procesado', ticketNumber: $ticket, cdrContent: 'cdr-base64');

        $this->providerMock
            ->shouldReceive('getStatus')
            ->once()
            ->with($ticket)
            ->andReturn($expected);

        $result = $this->service->getStatus($ticket);

        $this->assertFalse($result->isPending());
        $this->assertEquals('cdr-base64', $result->cdrContent);
    }

    public function test_get_provider_name(): void
    {
        $this->providerMock
            ->shouldReceive('getName')
            ->once()
            ->andReturn('ose');

        $this->assertEquals('ose', $this->service->getProviderName());
    }

    public function test_with_provider_returns_new_instance(): void
    {
        $newProvider = Mockery::mock(ProviderInterface::class);
        $newProvider->shouldReceive('getName')->andReturn('pse');

        $newService = $this->service->withProvider($newProvider);

        $this->assertNotSame($this->service, $newService);
        $this->assertEquals('pse', $newService->getProviderName());
    }

    public function test_service_is_bound_in_container(): void
    {
        $this->assertInstanceOf(
            SunatSenderInterface::class,
            $this->app->make(SunatSenderInterface::class)
        );
    }

    public function test_facade_resolves_correctly(): void
    {
        $this->assertInstanceOf(
            SunatSenderInterface::class,
            SunatSender::getFacadeRoot()
        );
    }

    public function test_sunat_response_is_accepted_when_code_zero(): void
    {
        $response = SunatResponse::success(0, 'Aceptado');
        $this->assertTrue($response->isAccepted());
    }

    public function test_sunat_response_is_pending_with_ticket_but_no_cdr(): void
    {
        $response = SunatResponse::success(0, 'En proceso', ticketNumber: 'T-001');
        $this->assertTrue($response->isPending());
    }

    public function test_sunat_response_failure(): void
    {
        $response = SunatResponse::failure(2800, 'RUC no encontrado');
        $this->assertFalse($response->success);
        $this->assertEquals(2800, $response->code);
    }

    public function test_sendable_document_gets_correct_filename(): void
    {
        $document = $this->makeDocument();
        $this->assertEquals('20000000001-01-F001-00000001.xml', $document->getFileName());
    }

    public function test_sendable_document_to_array(): void
    {
        $document = $this->makeDocument();
        $array    = $document->toArray();

        $this->assertArrayHasKey('ruc_emisor', $array);
        $this->assertArrayHasKey('xml_content', $array);
        $this->assertEquals('20000000001', $array['ruc_emisor']);
    }

    private function makeDocument(): SendableDocument
    {
        return new SendableDocument(
            type: '01',
            series: 'F001',
            number: '00000001',
            rucEmisor: '20000000001',
            xmlContent: '<Invoice/>',
        );
    }
}
