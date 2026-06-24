<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Products\Application\UseCase;

use PharmaControl\Auth\Domain\Contract\Service\EventPublisherContract;
use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryId;
use PharmaControl\Catalog\Location\Domain\Contract\Repository\LocationRepositoryContract;
use PharmaControl\Catalog\Products\Application\DTO\ProductDTO;
use PharmaControl\Catalog\Products\Application\UseCase\UpdateProduct\UpdateProductCommand;
use PharmaControl\Catalog\Products\Application\UseCase\UpdateProduct\UpdateProductUseCase;
use PharmaControl\Catalog\Products\Domain\Contract\Repository\ProductRepositoryContract;
use PharmaControl\Catalog\Products\Domain\Exception\DuplicateProductNameException;
use PharmaControl\Catalog\Products\Domain\Exception\ProductNotFoundException;
use PharmaControl\Catalog\Products\Domain\Model\Product;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductId;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductMargins;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductName;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductSpecs;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductType;
use PharmaControl\Catalog\Products\Domain\ValueObject\SaleCondition;
use PharmaControl\Catalog\Products\Domain\ValueObject\StockConfig;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusId;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

final class UpdateProductUseCaseTest extends TestCase
{
    private ProductRepositoryContract&MockObject $productRepo;

    private LocationRepositoryContract&MockObject $locationRepo;

    private EventPublisherContract&MockObject $events;

    private UpdateProductUseCase $useCase;

    private string $productId = '550e8400-e29b-41d4-a716-446655440010';

    protected function setUp(): void
    {
        parent::setUp();

        $this->productRepo = $this->createMock(ProductRepositoryContract::class);
        $this->locationRepo = $this->createMock(LocationRepositoryContract::class);
        $this->events = $this->createMock(EventPublisherContract::class);

        $this->useCase = new UpdateProductUseCase(
            $this->productRepo,
            $this->locationRepo,
            $this->events,
        );
    }

    private function makeExistingProduct(): Product
    {
        return Product::reconstitute(
            id: new ProductId($this->productId),
            type: ProductType::GENERIC,
            name: new ProductName('Paracetamol 500mg'),
            description: null,
            statusId: new StatusId('550e8400-e29b-41d4-a716-446655440001'),
            categoryId: new CategoryId('550e8400-e29b-41d4-a716-446655440002'),
            laboratoryId: null,
            saleCondition: SaleCondition::SIN_RECETA,
            sanitaryReg: null,
            barcode: null,
            specs: new ProductSpecs('u-id', 'pres-id', 'route-id', 10, 10, null),
            stockConfig: new StockConfig(5, 100, 30, true, false),
            margins: new ProductMargins(20.0, 10.0),
            ingredients: [],
            imageUrls: [],
            isActive: true,
            createdBy: new UserId('550e8400-e29b-41d4-a716-446655440003'),
            createdAt: new \DateTimeImmutable,
            updatedAt: new \DateTimeImmutable,
        );
    }

    private function makeCommand(array $overrides = []): UpdateProductCommand
    {
        return new UpdateProductCommand(
            id: $overrides['id'] ?? $this->productId,
            name: $overrides['name'] ?? 'Paracetamol 500mg',
            description: null,
            statusId: '550e8400-e29b-41d4-a716-446655440001',
            categoryId: '550e8400-e29b-41d4-a716-446655440002',
            laboratoryId: null,
            saleCondition: 'SIN_RECETA',
            sanitaryReg: null,
            barcode: null,
            unitId: 'u-id',
            presentationId: 'pres-id',
            routeId: 'route-id',
            unitsPerBox: 10,
            unitsPerBlister: 10,
            locationId: null,
            minStock: 5,
            maxStock: 100,
            expiryAlertDays: 30,
            manageLots: true,
            allowFraction: false,
            retailMargin: 20.0,
            wholesaleMargin: 10.0,
            ingredients: [],
            actorUserId: '550e8400-e29b-41d4-a716-446655440003',
        );
    }

    /** @test */
    public function it_throws_when_product_not_found(): void
    {
        $this->productRepo->method('findById')->willReturn(null);

        $this->expectException(ProductNotFoundException::class);

        ($this->useCase)($this->makeCommand());
    }

    /** @test */
    public function it_updates_product_and_returns_dto(): void
    {
        $product = $this->makeExistingProduct();
        $this->productRepo->method('findById')->willReturn($product);
        $this->productRepo->method('findByName')->willReturn(null);
        $this->productRepo->expects($this->once())->method('save');
        $this->events->expects($this->once())->method('publish');

        $dto = ($this->useCase)($this->makeCommand(['name' => 'Ibuprofeno 400mg']));

        $this->assertInstanceOf(ProductDTO::class, $dto);
        $this->assertSame('Ibuprofeno 400mg', $dto->name);
    }

    /** @test */
    public function it_throws_when_new_name_belongs_to_another_product(): void
    {
        $product = $this->makeExistingProduct();
        $this->productRepo->method('findById')->willReturn($product);

        $otherProduct = $this->createMock(Product::class);
        $otherId = new ProductId('550e8400-e29b-41d4-a716-446655440099');
        $otherProduct->method('getId')->willReturn($otherId);
        $this->productRepo->method('findByName')->willReturn($otherProduct);

        $this->expectException(DuplicateProductNameException::class);

        ($this->useCase)($this->makeCommand(['name' => 'Ibuprofeno Otro']));
    }

    /** @test */
    public function it_allows_keeping_the_same_name_on_update(): void
    {
        $product = $this->makeExistingProduct();
        $this->productRepo->method('findById')->willReturn($product);
        // findByName returns the same product (same ID → not a conflict)
        $this->productRepo->method('findByName')->willReturn($product);
        $this->productRepo->expects($this->once())->method('save');

        $dto = ($this->useCase)($this->makeCommand());

        $this->assertSame('Paracetamol 500mg', $dto->name);
    }
}
