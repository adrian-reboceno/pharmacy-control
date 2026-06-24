<?php

declare(strict_types=1);

namespace PharmaControl\Catalog\Products\Infrastructure\Storage;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PharmaControl\Catalog\Products\Infrastructure\Persistence\Eloquent\Model\EloquentProductImage;

final class ProductImageStorage
{
    public function store(string $productId, string $imageContent, string $mimeType): string
    {
        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => throw new \InvalidArgumentException("Tipo de imagen no soportado: {$mimeType}"),
        };

        $filename = uniqid() . ".{$extension}";
        $path     = "products/{$productId}/{$filename}";
        $decoded  = base64_decode($imageContent);

        Storage::put($path, $decoded);
        $url = Storage::url($path);

        $sortOrder = EloquentProductImage::where('product_id', $productId)->count();

        EloquentProductImage::create([
            'id'         => Str::uuid()->toString(),
            'product_id' => $productId,
            'url'        => $url,
            'filename'   => $filename,
            'mime_type'  => $mimeType,
            'size_bytes' => strlen($decoded),
            'sort_order' => $sortOrder,
            'is_primary' => $sortOrder === 0,
        ]);

        return $url;
    }

    public function delete(string $imageId): void
    {
        $image = EloquentProductImage::find($imageId);
        if ($image === null) {
            return;
        }

        $path = str_replace(Storage::url(''), '', $image->url);
        Storage::delete($path);
        $image->delete();
    }

    public function findUrlById(string $imageId): ?string
    {
        return EloquentProductImage::find($imageId)?->url;
    }
}
