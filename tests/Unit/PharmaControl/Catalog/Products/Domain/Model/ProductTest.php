<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Products\Domain\Model;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryId;
use PharmaControl\Catalog\Products\Domain\Entity\ProductIngredient;
use PharmaControl\Catalog\Products\Domain\Event\ProductCreated;
use PharmaControl\Catalog\Products\Domain\Event\ProductDeactivated;
use PharmaControl\Catalog\Products\Domain\Event\ProductImageAdded;
use PharmaControl\Catalog\Products\Domain\Event\ProductUpdated;
use PharmaControl\Catalog\Products\Domain\Model\Product;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductId;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductMargins;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductName;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductSpecs;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductType;
use PharmaControl\Catalog\Products\Domain\ValueObject\SaleCondition;
use PharmaControl\Catalog\Products\Domain\ValueObject\StockConfig;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusId;
use Tests\TestCase;

final class ProductTest extends TestCase
{
    private function makeProduct(array $overrides = []): Product
    {
        return Product::create(
            id: ProductId::generate(),
            type: $overrides['type'] ?? ProductType::GENERIC,
            name: new ProductName($overrides['name'] ?? 'Paracetamol 500mg'),
            description: $overrides['description'] ?? null,
            statusId: new StatusId('550e8400-e29b-41d4-a716-446655440001'),
            categoryId: new CategoryId('550e8400-e29b-41d4-a716-446655440002'),
            laboratoryId: $overrides['laboratoryId'] ?? null,
            saleCondition: SaleCondition::SIN_RECETA,
            sanitaryReg: null,
            barcode: null,
            specs: new ProductSpecs('u-id', 'pres-id', 'route-id', 10, 10, null),
            stockConfig: new StockConfig(5, 100, 30, true, false),
            margins: new ProductMargins(20.0, 10.0),
            ingredients: [],
            createdBy: new UserId('550e8400-e29b-41d4-a716-446655440003'),
        );
    }

    /** @test */
    public function it_records_product_created_event_on_create(): void
    {
        $product = $this->makeProduct();
        $events = $product->releaseEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProductCreated::class, $events[0]);
        $this->assertSame(ProductType::GENERIC, $events[0]->type);
    }

    /** @test */
    public function it_clears_events_after_release(): void
    {
        $product = $this->makeProduct();
        $product->releaseEvents();

        $this->assertEmpty($product->releaseEvents());
    }

    /** @test */
    public function it_records_product_updated_event_on_update(): void
    {
        $product = $this->makeProduct();
        $product->releaseEvents();

        $product->update(
            name: new ProductName('Ibuprofeno 400mg'),
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
        );

        $events = $product->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProductUpdated::class, $events[0]);
        $this->assertArrayHasKey('name', $events[0]->changes);
    }

    /** @test */
    public function it_records_product_deactivated_event_on_deactivate(): void
    {
        $product = $this->makeProduct();
        $product->releaseEvents();

        $product->deactivate();

        $events = $product->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProductDeactivated::class, $events[0]);
        $this->assertFalse($product->isActive());
    }

    /** @test */
    public function it_throws_when_deactivating_already_inactive_product(): void
    {
        $product = $this->makeProduct();
        $product->releaseEvents();
        $product->deactivate();
        $product->releaseEvents();

        $this->expectException(\DomainException::class);

        $product->deactivate();
    }

    /** @test */
    public function it_records_product_image_added_event_on_add_image(): void
    {
        $product = $this->makeProduct();
        $product->releaseEvents();

        $product->addImage('https://example.com/img.jpg');

        $events = $product->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProductImageAdded::class, $events[0]);
        $this->assertSame('https://example.com/img.jpg', $events[0]->imageUrl);
        $this->assertCount(1, $product->getImageUrls());
    }

    /** @test */
    public function it_throws_when_adding_more_than_10_images(): void
    {
        $product = $this->makeProduct();
        $product->releaseEvents();

        for ($i = 1; $i <= 10; $i++) {
            $product->addImage("https://example.com/img{$i}.jpg");
        }
        $product->releaseEvents();

        $this->expectException(\DomainException::class);

        $product->addImage('https://example.com/img11.jpg');
    }

    /** @test */
    public function it_removes_image_and_records_updated_event(): void
    {
        $product = $this->makeProduct();
        $product->addImage('https://example.com/img.jpg');
        $product->releaseEvents();

        $product->removeImage('https://example.com/img.jpg');

        $events = $product->releaseEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ProductUpdated::class, $events[0]);
        $this->assertEmpty($product->getImageUrls());
    }

    /** @test */
    public function it_throws_when_removing_nonexistent_image(): void
    {
        $product = $this->makeProduct();
        $product->releaseEvents();

        $this->expectException(\DomainException::class);

        $product->removeImage('https://example.com/nonexistent.jpg');
    }

    /** @test */
    public function it_replaces_ingredients_on_update(): void
    {
        $ingredient = new ProductIngredient('ing-id', '500', 'mg');
        $product = $this->makeProduct();
        $product->releaseEvents();

        $product->update(
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
            ingredients: [$ingredient],
        );

        $this->assertCount(1, $product->getIngredients());
        $this->assertSame('500', $product->getIngredients()[0]->concentration);
    }
}
