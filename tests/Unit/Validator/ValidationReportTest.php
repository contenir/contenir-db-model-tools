<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Unit\Validator;

use Contenir\Db\Model\Tools\Validator\Issue;
use Contenir\Db\Model\Tools\Validator\Severity;
use Contenir\Db\Model\Tools\Validator\ValidationReport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ValidationReport::class)]
#[CoversClass(Issue::class)]
#[Group('unit')]
final class ValidationReportTest extends TestCase
{
    private static function report(Issue ...$issues): ValidationReport
    {
        $report = new ValidationReport();
        $report->add('A', []);
        $report->add('B', $issues);

        return $report;
    }

    #[Test]
    public function cleanReportPassesStrictly(): void
    {
        static::assertTrue(self::report()->passes(strict: true));
    }

    #[Test]
    public function countsIssuesBySeverity(): void
    {
        $report = self::report(Issue::error('e'), Issue::warning('w1'), Issue::warning('w2'));

        static::assertSame([2, 1, 2], [
            $report->size(),
            $report->count(Severity::Error),
            $report->count(Severity::Warning),
        ]);
    }

    #[Test]
    public function failsWithErrors(): void
    {
        static::assertFalse(self::report(Issue::error('e'))->passes());
    }

    #[Test]
    public function keepsIssuesPerEntityInOrder(): void
    {
        $warning = Issue::warning('w');

        static::assertSame(['A' => [], 'B' => [$warning]], self::report($warning)->entities());
    }

    #[Test]
    public function passesWithOnlyWarningsUnlessStrict(): void
    {
        $report = self::report(Issue::warning('w'));

        static::assertSame([true, false], [$report->passes(), $report->passes(strict: true)]);
    }
}
