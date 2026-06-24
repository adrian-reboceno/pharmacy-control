<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Domain\Model;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\Categories\Domain\ValueObject\CategoryId;
use PharmaControl\Catalog\Laboratories\Domain\ValueObject\LaboratoryId;
use PharmaControl\Catalog\Products\Domain\Entity\ProductIngredient;
use PharmaControl\Catalog\Products\Domain\Event\ProductCreated;
use PharmaControl\Catalog\Products\Domain\Event\ProductDeactivated;
use PharmaControl\Catalog\Products\Domain\Event\ProductImageAdded;
use PharmaControl\Catalog\Products\Domain\Event\ProductUpdated;
use PharmaControl\Catalog\Products\Domain\ValueObject\Barcode;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductId;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductMargins;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductName;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductSpecs;
use PharmaControl\Catalog\Products\Domain\ValueObject\ProductType;
use PharmaControl\Catalog\Products\Domain\ValueObject\SaleCondition;
use PharmaControl\Catalog\Products\Domain\ValueObject\SanitaryReg;
use PharmaControl\Catalog\Products\Domain\ValueObject\StockConfig;
use PharmaControl\Catalog\Status\Domain\ValueObject\StatusId;
use PharmaControl\Shared\Event\DomainEvent;

final class Product
{
    private ProductName    $name;
    private ?string        $description;
    private StatusId       $statusId;
    private CategoryId     $categoryId;
    private ?LaboratoryId  $laboratoryId;
    private SaleCondition  $saleCondition;
    private ?SanitaryReg   $sanitaryReg;
    private ?Barcode       $barcode;
    private ProductSpecs   $specs;
    private StockConfig    $stockConfig;
    private ProductMargins $margins;
    /** @var ProductIngredient[] */
    private array          $ingredients;
    /** @var string[] */
    private array          $imageUrls;
    private bool           $isActive;

    /** @var list<DomainEvent> */
    private array $domainEvents = [];

    private function __construct(
        public readonly ProductId          $id,
        public readonly ProductType        $type,
        ProductName                        $name,
        ?string                            $description,
        StatusId                           $statusId,
        CategoryId                         $categoryId,
        ?LaboratoryId                      $laboratoryId,
        SaleCondition                      $saleCondition,
        ?SanitaryReg                       $sanitaryReg,
        ?Barcode                           $barcode,
        ProductSpecs                       $specs,
        StockConfig                        $stockConfig,
        ProductMargins                     $margins,
        array                              $ingredients,
        array                              $imageUrls,
        bool                               $isActive,
        public readonly ?UserId            $createdBy,
        public readonly \DateTimeImmutable $createdAt,
        private \DateTimeImmutable         $updatedAt,
    ) {
        $this->name          = $name;
        $this->description   = $description;
        $this->statusId      = $statusId;
        $this->categoryId    = $categoryId;
        $this->laboratoryId  = $laboratoryId;
        $this->saleCondition = $saleCondition;
        $this->sanitaryReg   = $sanitaryReg;
        $this->barcode       = $barcode;
        $this->specs         = $specs;
        $this->stockConfig   = $stockConfig;
        $this->margins       = $margins;
        $this->ingredients   = $ingredients;
        $this->imageUrls     = $imageUrls;
        $this->isActive      = $isActive;
    }

    public static function create(
        ProductId      $id,
        ProductType    $type,
        ProductName    $name,
        ?string        $description,
        StatusId       $statusId,
        CategoryId     $categoryId,
        ?LaboratoryId  $laboratoryId,
        SaleCondition  $saleCondition,
        ?SanitaryReg   $sanitaryReg,
        ?Barcode       $barcode,
        ProductSpecs   $specs,
        StockConfig    $stockConfig,
        ProductMargins $margins,
        array          $ingredients,
        ?UserId        $createdBy,
    ): self {
        $now     = new \DateTimeImmutable;
        $product = new self(
            $id, $type, $name, $description,
            $statusId, $categoryId, $laboratoryId,
            $saleCondition, $sanitaryReg, $barcode,
            $specs, $stockConfig, $margins,
            $ingredients, [], true,
            $createdBy, $now, $now,
        );
        $product->recordEvent(new ProductCreated(
            id:        $id,
            type:      $type,
            name:      $name,
            createdBy: $createdBy,
            occurredAt: $now,
        ));

        return $product;
    }

    public static function reconstitute(
        ProductId      $id,
        ProductType    $type,
        ProductName    $name,
        ?string        $description,
        StatusId       $statusId,
        CategoryId     $categoryId,
        ?LaboratoryId  $laboratoryId,
        SaleCondition  $saleCondition,
        ?SanitaryReg   $sanitaryReg,
        ?Barcode       $barcode,
        ProductSpecs   $specs,
        StockConfig    $stockConfig,
        ProductMargins $margins,
        array          $ingredients,
        array          $imageUrls,
        bool           $isActive,
        ?UserId        $createdBy,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            $id, $type, $name, $description,
            $statusId, $categoryId, $laboratoryId,
            $saleCondition, $sanitaryReg, $barcode,
            $specs, $stockConfig, $margins,
            $ingredients, $imageUrls, $isActive,
            $createdBy, $createdAt, $updatedAt,
        );
    }

    public function update(
        ProductName    $name,
        ?string        $description,
        StatusId       $statusId,
        CategoryId     $categoryId,
        ?LaboratoryId  $laboratoryId,
        SaleCondition  $saleCondition,
        ?SanitaryReg   $sanitaryReg,
        ?Barcode       $barcode,
        ProductSpecs   $specs,
        StockConfig    $stockConfig,
        ProductMargins $margins,
        array          $ingredients,
    ): void {
        $changes = [];

        if (! $this->name->equals($name)) {
            $changes['name'] = ['old' => $this->name->value, 'new' => $name->value];
        }
        if ($this->description !== $description) {
            $changes['description'] = ['old' => $this->description, 'new' => $description];
        }
        if (! $this->statusId->equals($statusId)) {
            $changes['status_id'] = ['old' => $this->statusId->value, 'new' => $statusId->value];
        }

        $this->name          = $name;
        $this->description   = $description;
        $this->statusId      = $statusId;
        $this->categoryId    = $categoryId;
        $this->laboratoryId  = $laboratoryId;
        $this->saleCondition = $saleCondition;
        $this->sanitaryReg   = $sanitaryReg;
        $this->barcode       = $barcode;
        $this->specs         = $specs;
        $this->stockConfig   = $stockConfig;
        $this->margins       = $margins;
        $this->ingredients   = $ingredients;
        $this->updatedAt     = new \DateTimeImmutable;

        $this->recordEvent(new ProductUpdated(
            id:         $this->id,
            changes:    $changes,
            occurredAt: $this->updatedAt,
        ));
    }

    public function deactivate(): void
    {
        if (! $this->isActive) {
            throw new \DomainException('El producto ya está inactivo.');
        }
        $this->isActive  = false;
        $this->updatedAt = new \DateTimeImmutable;
        $this->recordEvent(new ProductDeactivated(
            id:         $this->id,
            occurredAt: $this->updatedAt,
        ));
    }

    public function addImage(string $url): void
    {
        if (count($this->imageUrls) >= 10) {
            throw new \DomainException('El producto ya tiene el máximo de 10 imágenes.');
        }
        $this->imageUrls[] = $url;
        $this->updatedAt   = new \DateTimeImmutable;
        $this->recordEvent(new ProductImageAdded(
            id:         $this->id,
            imageUrl:   $url,
            occurredAt: $this->updatedAt,
        ));
    }

    public function removeImage(string $url): void
    {
        $key = array_search($url, $this->imageUrls, true);
        if ($key === false) {
            throw new \DomainException('La imagen no existe en el producto.');
        }
        array_splice($this->imageUrls, (int) $key, 1);
        $this->updatedAt = new \DateTimeImmutable;
        $this->recordEvent(new ProductUpdated(
            id:         $this->id,
            changes:    ['image_removed' => $url],
            occurredAt: $this->updatedAt,
        ));
    }

    public function getId(): ProductId
    {
        return $this->id;
    }

    public function getType(): ProductType
    {
        return $this->type;
    }

    public function getName(): ProductName
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getStatusId(): StatusId
    {
        return $this->statusId;
    }

    public function getCategoryId(): CategoryId
    {
        return $this->categoryId;
    }

    public function getLaboratoryId(): ?LaboratoryId
    {
        return $this->laboratoryId;
    }

    public function getSaleCondition(): SaleCondition
    {
        return $this->saleCondition;
    }

    public function getSanitaryReg(): ?SanitaryReg
    {
        return $this->sanitaryReg;
    }

    public function getBarcode(): ?Barcode
    {
        return $this->barcode;
    }

    public function getSpecs(): ProductSpecs
    {
        return $this->specs;
    }

    public function getStockConfig(): StockConfig
    {
        return $this->stockConfig;
    }

    public function getMargins(): ProductMargins
    {
        return $this->margins;
    }

    /** @return ProductIngredient[] */
    public function getIngredients(): array
    {
        return $this->ingredients;
    }

    /** @return string[] */
    public function getImageUrls(): array
    {
        return $this->imageUrls;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedBy(): ?UserId
    {
        return $this->createdBy;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function releaseEvents(): array
    {
        $events           = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    private function recordEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }
}
