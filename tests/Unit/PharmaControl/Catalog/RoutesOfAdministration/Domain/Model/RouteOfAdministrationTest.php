<?php

declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\RoutesOfAdministration\Domain\Model;

use PharmaControl\Auth\Domain\ValueObject\UserId;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Event\RouteCreated;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Event\RouteDeactivated;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Event\RouteUpdated;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\Model\RouteOfAdministration;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteCode;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteId;
use PharmaControl\Catalog\RoutesOfAdministration\Domain\ValueObject\RouteName;
use PHPUnit\Framework\TestCase;

final class RouteOfAdministrationTest extends TestCase
{
    private function makeRoute(string $name = 'Oral', string $code = 'VO'): RouteOfAdministration
    {
        return RouteOfAdministration::create(
            RouteId::generate(),
            new RouteName($name),
            new RouteCode($code),
            'Descripción de prueba',
            UserId::generate(),
        );
    }

    public function test_create_emits_route_created_event(): void
    {
        $route = $this->makeRoute();
        $events = $route->releaseEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(RouteCreated::class, $events[0]);
    }

    public function test_reconstitute_does_not_emit_events(): void
    {
        $route = RouteOfAdministration::reconstitute(
            id: RouteId::generate(),
            name: new RouteName('Oral'),
            code: new RouteCode('VO'),
            description: null,
            isActive: true,
            createdBy: null,
            createdAt: new \DateTimeImmutable,
            updatedAt: new \DateTimeImmutable,
        );

        self::assertSame([], $route->releaseEvents());
    }

    public function test_update_changes_name_code_and_description_and_emits_route_updated(): void
    {
        $route = $this->makeRoute('Oral', 'VO');
        $route->releaseEvents();

        $route->update(new RouteName('Sublingual'), new RouteCode('SL'), 'Nueva descripción');
        $events = $route->releaseEvents();

        self::assertCount(1, $events);
        self::assertInstanceOf(RouteUpdated::class, $events[0]);
        self::assertSame('Sublingual', $route->getName()->value);
        self::assertSame('SL', $route->getCode()->value);
        self::assertSame('Nueva descripción', $route->getDescription());
    }

    public function test_update_sets_updated_at_to_current_time(): void
    {
        $before = new \DateTimeImmutable;
        $route = $this->makeRoute();
        $route->releaseEvents();

        $route->update(new RouteName('Sublingual'), new RouteCode('SL'), null);

        self::assertGreaterThanOrEqual($before, $route->getUpdatedAt());
    }

    public function test_deactivate_sets_is_active_false_and_emits_route_deactivated(): void
    {
        $route = $this->makeRoute();
        $route->releaseEvents();

        $route->deactivate();
        $events = $route->releaseEvents();

        self::assertFalse($route->isActive());
        self::assertCount(1, $events);
        self::assertInstanceOf(RouteDeactivated::class, $events[0]);
    }

    public function test_deactivate_throws_domain_exception_when_already_inactive(): void
    {
        $route = $this->makeRoute();
        $route->releaseEvents();
        $route->deactivate();
        $route->releaseEvents();

        $this->expectException(\DomainException::class);

        $route->deactivate();
    }

    public function test_release_events_clears_internal_events_after_returning(): void
    {
        $route = $this->makeRoute();

        $first = $route->releaseEvents();
        $second = $route->releaseEvents();

        self::assertCount(1, $first);
        self::assertCount(0, $second);
    }
}
