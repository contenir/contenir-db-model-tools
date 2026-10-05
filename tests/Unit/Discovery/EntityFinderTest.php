<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Unit\Discovery;

use Contenir\Db\Model\Tools\Discovery\EntityFinder;
use Contenir\Db\Model\Tools\Discovery\PhpFiles;
use Contenir\Db\Model\Tools\Exception\ToolException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(EntityFinder::class)]
#[CoversClass(PhpFiles::class)]
#[CoversClass(ToolException::class)]
#[Group('unit')]
final class EntityFinderTest extends TestCase
{
    private vfsStreamDirectory $root;

    /**
     * @mago-expect lint:no-literal-namespace-string The classes only exist as parsed source.
     */
    #[Test]
    public function findsTableClassesInTheTree(): void
    {
        vfsStream::create([
            'User.php' => "<?php\nnamespace App\\Entity;\nuse Contenir\\Db\\Model\\Mapping\\Table;\n#[Table('users')]\nfinal class User {}\n",
            'Nested' => [
                'Order.php' => "<?php\nnamespace App\\Entity\\Nested;\n#[\\Contenir\\Db\\Model\\Mapping\\Table('orders'), \\Attribute]\nclass Order {}\n",
            ],
            'Plain.php' => "<?php\nnamespace App;\n#[\\Attribute]\nfinal class Plain { public function f(): object { return new #[\\Contenir\\Db\\Model\\Mapping\\Table('x')] class {}; } }\n",
            'README.md' => '#[Table]',
        ], $this->root);

        static::assertSame(
            ['App\\Entity\\Nested\\Order', 'App\\Entity\\User'],
            (new EntityFinder())->find($this->root->url()),
        );
    }

    #[Test]
    public function reportsFilesThatDoNotParse(): void
    {
        vfsStream::newFile('Broken.php')->withContent('<?php class {')->at($this->root);

        $this->expectException(ToolException::class);
        $this->expectExceptionMessageMatches('~^Cannot parse vfs://root/Broken\.php: Syntax error~');

        (new EntityFinder())->find($this->root->url());
    }

    #[Test]
    public function reportsMissingDirectories(): void
    {
        $this->expectException(ToolException::class);
        $this->expectExceptionMessageMatches('~^Cannot read vfs://root/missing: ~');

        (new EntityFinder())->find("{$this->root->url()}/missing");
    }

    protected function setUp(): void
    {
        $this->root = vfsStream::setup();
    }
}
