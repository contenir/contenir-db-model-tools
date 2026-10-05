<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

use Contenir\Db\Model\Tools\Exception\UpgradeException;
use PhpParser\ConstExprEvaluationException;
use PhpParser\ConstExprEvaluator;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;

use function in_array;

/**
 * Evaluates the default values of a 1.x entity's mapping properties:
 * literals, X::class and AbstractEntity::RELATION_* constants.
 *
 * @internal
 */
final readonly class LegacyDefaults
{
    /** Properties 1.x entities declared their mapping in. */
    public const array PROPERTIES = ['columns', 'primaryKeys', 'versionColumn', 'relations'];

    /**
     * @return array<string, mixed> mapping property => evaluated default value
     *
     * @throws UpgradeException When a default is not a supported constant expression.
     */
    public static function of(string $file, Class_ $class): array
    {
        $evaluator = new ConstExprEvaluator(self::fallback(...));
        $values    = [];
        foreach ($class->getProperties() as $property) {
            foreach ($property->props as $item) {
                $name = $item->name->toString();
                if (null === $item->default || ! in_array($name, self::PROPERTIES, strict: true)) {
                    continue;
                }

                try {
                    $values[$name] = $evaluator->evaluateDirectly($item->default);
                } catch (ConstExprEvaluationException $e) {
                    throw UpgradeException::unsupportedExpression($file, $name, $e->getMessage());
                }
            }
        }

        return $values;
    }

    /**
     * @throws ConstExprEvaluationException
     *
     * @mago-expect analysis:mixed-assignment Node attributes are untyped; the name is checked before use.
     */
    private static function className(ClassConstFetch $expr): string
    {
        $resolved = $expr->class instanceof Name ? $expr->class->getAttribute('resolvedName') : null;
        if (! $resolved instanceof Name) {
            throw new ConstExprEvaluationException('dynamic class names are not supported');
        }

        return $resolved->toString();
    }

    /**
     * @throws ConstExprEvaluationException
     */
    private static function fallback(Expr $expr): string
    {
        if (! $expr instanceof ClassConstFetch || ! $expr->name instanceof Identifier) {
            throw new ConstExprEvaluationException('only literals, ::class and RELATION_* constants are supported');
        }

        return match ($expr->name->toString()) {
            'RELATION_SINGLE' => 'single',
            'RELATION_MANY'   => 'many',
            'class'           => self::className($expr),
            default           => throw new ConstExprEvaluationException("unsupported constant {$expr->name}"),
        };
    }
}
