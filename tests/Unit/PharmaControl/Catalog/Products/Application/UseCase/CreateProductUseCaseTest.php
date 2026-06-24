<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Products\Application\UseCase;

use PHPUnit\Framework\MockObject\MockObject;
use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Catalog\Location\Domain\Contract\Repository\LocationRepositoryContract;
use PharmaControl\Catalog\Products\Application\DTO\ProductDTO;
use PharmaControl\Catalog\Products\Application\UseCase\CreateProduct\CreateProductCommand;
use PharmaControl\Catalog\Products\Application\UseCase\CreateProduct\CreateProductUseCase;
use PharmaControl\Catalog\Products\Domain\Contract\Repository\ProductRepositoryContract;
use PharmaControl\Catalog\Products\Domain\Exception\BrandedProductRequiresLaboratoryException;
use PharmaControl\Catalog\Products\Domain\Exception\DuplicateBarcodeException;
use PharmaControl\Catalog\Products\Domain\Exception\DuplicateProductNameException;
use PharmaControl\Catalog\Products\Domain\Exception\InvalidBarcodeException;
use PharmaControl\Catalog\Products\Domain\Model\Product;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductName;
use Tests\TestCase;

final class CreateProductUseCaseTest extends TestCase
{
    private ProductRepositoryContract&MockObject  $productRepo;
    private LocationRepositoryContract&MockObject $locationRepo;
    private EventPublisherContract&MockObject     $events;
    private CreateProductUseCase                  $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productRepo  = $this->createMock(ProductRepositoryContract::class);
        $this->locationRepo = $this->createMock(LocationRepositoryContract::class);
        $this->events       = $this->createMock(EventPublisherContract::class);

        $this->useCase = new CreateProductUseCase(
            $this->productRepo,
            $this->locationRepo,
            $this->events,
        );
    }

    private function makeCommand(array $overrides = []): CreateProductCommand
    {
        return new CreateProductCommand(
            type:            $overrides['type'] ?? 'GENERIC',
            name:            $overrides['name'] ?? 'Paracetamol 500mg',
            description:     null,
            statusId:        '550e8400-e29b-41d4-a716-446655440001',
            categoryId:      '550e8400-e29b-41d4-a716-446655440002',
            laboratoryId:    $overrides['laboratoryId'] ?? null,
            saleCondition:   'SIN_RECETA',
            sanitaryReg:     null,
            barcode:         $overrides['barcode'] ?? null,
            unitId:          '550e8400-e29b-41d4-a716-446655440004',
            presentationId:  '550e8400-e29b-41d4-a716-446655440005',
            routeId:         '550e8400-e29b-41d4-a716-446655440006',
            unitsPerBox:     10,
            unitsPerBlister: 10,
            locationId:      null,
            minStock:        5,
            maxStock:        100,
            expiryAlertDays: 30,
            manageLots:      true,
            allowFraction:   false,
            retailMargin:    20.0,
            wholesaleMargin: 10.0,
            ingredients:     [],
            actorUserId:     '550e8400-e29b-41d4-a716-446655440003',
        );
    }

    /** @test */
    public function it_creates_a_product_and_returns_dto(): void
    {
        $this->productRepo->expects($this->once())->method('findByName')->willReturn(null);
        $this->productRepo->expects($this->once())->method('save');
        $this->events->expects($this->once())->method('publish');

        $dto = ($this->useCase)($this->makeCommand());

        $this->assertInstanceOf(ProductDTO::class, $dto);
        $this->assertSame('Paracetamol 500mg', $dto->name);
        $this->assertSame('GENERIC', $dto->type);
    }

    /** @test */
    public function it_throws_when_branded_product_has_no_laboratory(): void
    {
        $this->expectException(BrandedProductRequiresLaboratoryException::class);

        ($this->useCase)($this->makeCommand(['type' => 'BRANDED', 'laboratoryId' => null]));
    }

    /** @test */
    public function it_throws_when_name_is_duplicate(): void
    {
        $existing = $this->createMock(Product::class);
        $this->productRepo->method('findByName')->willReturn($existing);

        $this->expectException(DuplicateProductNameException::class);

        ($this->useCase)($this->makeCommand());
    }

    /** @test */
    public function it_throws_when_barcode_format_is_invalid(): void
    {
        $this->productRepo->method('findByName')->willReturn(null);

        $this->expectException(InvalidBarcodeException::class);

        ($this->useCase)($this->makeCommand(['barcode' => '1234567890123']));
    }

    /** @test */
    public function it_throws_when_barcode_is_duplicate(): void
    {
        $this->productRepo->method('findByName')->willReturn(null);
        $existing = $this->createMock(Product::class);
        $this->productRepo->method('findByBarcode')->willReturn($existing);

        $this->expectException(DuplicateBarcodeException::class);

        // valid EAN-13
        ($this->useCase)($this->makeCommand(['barcode' => '7501031311309']));
    }

    /** @test */
    public function it_publishes_domain_events_after_save(): void
    {
        $this->productRepo->method('findByName')->willReturn(null);
        $this->productRepo->expects($this->once())->method('save');
        $this->events->expects($this->atLeastOnce())->method('publish');

        ($this->useCase)($this->makeCommand());
    }
}
