<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\Token;

use function preg_replace;

/**
 * A parsed 1.x entity file: the original syntax tree and tokens, and a
 * copy to modify, printed back preserving the original formatting.
 *
 * @internal
 */
final readonly class LegacySource
{
    /**
     * @param array<Stmt>  $original
     * @param array<Stmt>  $modified a clone of $original, with names resolved
     * @param array<Token> $tokens
     * @param Class_      $class    the entity class within $modified
     */
    public function __construct(
        public array $original,
        public array $modified,
        public array $tokens,
        public Class_ $class,
    ) {}

    /**
     * The modified tree, printed with the original formatting wherever it
     * was not changed. Blank lines are left without indentation, and never
     * doubled.
     */
    public function print(): string
    {
        $code = (new Standard())->printFormatPreserving($this->modified, $this->original, $this->tokens);

        return (string) preg_replace(['/^[ \t]+$/m', '/\n{3,}/'], replacement: ['', "\n\n"], subject: $code);
    }

    /**
     * @param array<Stmt> $statements replacement top-level statements for the modified tree
     */
    public function withStatements(array $statements): self
    {
        return new self($this->original, $statements, $this->tokens, $this->class);
    }
}
