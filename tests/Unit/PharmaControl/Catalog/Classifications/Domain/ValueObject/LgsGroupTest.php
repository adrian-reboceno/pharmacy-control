<?php

// ── ARCHIVO: tests/Unit/PharmaControl/Catalog/Classifications/Domain/ValueObject/LgsGroupTest.php ──
declare(strict_types=1);

namespace Tests\Unit\PharmaControl\Catalog\Classifications\Domain\ValueObject;

use PharmaControl\Catalog\Classifications\Domain\ValueObject\LgsGroup;
use PHPUnit\Framework\TestCase;

final class LgsGroupTest extends TestCase
{
    public function test_has_correct_backed_string_values_for_all_7_groups(): void
    {
        self::assertSame('I', LgsGroup::I->value);
        self::assertSame('II', LgsGroup::II->value);
        self::assertSame('III', LgsGroup::III->value);
        self::assertSame('IV_A', LgsGroup::IV_A->value);
        self::assertSame('IV_B', LgsGroup::IV_B->value);
        self::assertSame('V', LgsGroup::V->value);
        self::assertSame('VI', LgsGroup::VI->value);
    }

    public function test_label_returns_human_readable_string(): void
    {
        self::assertSame('Grupo I — Estupefacientes', LgsGroup::I->label());
        self::assertSame('Grupo II — Psicotrópicos', LgsGroup::II->label());
        self::assertSame('Grupo III — Psicotrópicos', LgsGroup::III->label());
        self::assertSame('Grupo IV-A — Antibióticos', LgsGroup::IV_A->label());
        self::assertSame('Grupo IV-B — Medicamentos con receta', LgsGroup::IV_B->label());
        self::assertSame('Grupo V — OTC exclusivo farmacias', LgsGroup::V->label());
        self::assertSame('Grupo VI — OTC libre acceso', LgsGroup::VI->label());
    }

    public function test_is_controlled_returns_true_for_groups_i_i_i_ii_i_i_v_a_i_v_b(): void
    {
        self::assertTrue(LgsGroup::I->isControlled());
        self::assertTrue(LgsGroup::II->isControlled());
        self::assertTrue(LgsGroup::III->isControlled());
        self::assertTrue(LgsGroup::IV_A->isControlled());
        self::assertTrue(LgsGroup::IV_B->isControlled());
    }

    public function test_is_controlled_returns_false_for_groups_v_and_vi(): void
    {
        self::assertFalse(LgsGroup::V->isControlled());
        self::assertFalse(LgsGroup::VI->isControlled());
    }

    public function test_from_returns_correct_case_for_all_values(): void
    {
        self::assertSame(LgsGroup::I, LgsGroup::from('I'));
        self::assertSame(LgsGroup::IV_A, LgsGroup::from('IV_A'));
        self::assertSame(LgsGroup::IV_B, LgsGroup::from('IV_B'));
        self::assertSame(LgsGroup::VI, LgsGroup::from('VI'));
    }

    public function test_from_throws_value_error_for_invalid_string(): void
    {
        $this->expectException(\ValueError::class);

        LgsGroup::from('VII');
    }
}
