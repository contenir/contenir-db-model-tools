<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Unit\Command;

use Contenir\Db\Model\Tools\Command\EntityWriter;
use Contenir\Db\Model\Tools\Exception\ToolException;
use Contenir\Db\Model\Tools\Generator\GeneratedClass;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

#[CoversClass(EntityWriter::class)]
#[CoversClass(ToolException::class)]
#[Group('unit')]
final class EntityWriterTest extends TestCase
{
    private vfsStreamDirectory $root;

    #[Test]
    public function overwritesWhenForced(): void
    {
        vfsStream::newFile('User.php')->withContent('old')->at($this->root);

        $path = (new EntityWriter($this->root->url(), force: true))->write(new GeneratedClass('User', 'new'));

        static::assertSame('new', file_get_contents($path));
    }

    #[Test]
    public function refusesToOverwriteWithoutForce(): void
    {
        vfsStream::newFile('User.php')->at($this->root);

        $this->expectExceptionObject(ToolException::fileExists('vfs://root/User.php'));

        (new EntityWriter($this->root->url()))->write(new GeneratedClass('User', 'new'));
    }

    #[Test]
    public function reportsWhyTheDirectoryCannotBeCreated(): void
    {
        $this->root->chmod(0o555);

        $this->expectException(ToolException::class);
        $this->expectExceptionMessageMatches('~^Cannot write vfs://root/Entity: ~');

        (new EntityWriter("{$this->root->url()}/Entity"))->write(new GeneratedClass('User', ''));
    }

    #[Test]
    public function reportsWhyTheFileCannotBeWritten(): void
    {
        $this->root->chmod(0o555);

        $this->expectException(ToolException::class);
        $this->expectExceptionMessageMatches(
            '~^Cannot write vfs://root/User\.php: file_put_contents\(.*\): Failed to open stream~',
        );

        (new EntityWriter($this->root->url()))->write(new GeneratedClass('User', ''));
    }

    #[Test]
    public function writesClassFileCreatingTheDirectory(): void
    {
        $path = (new EntityWriter("{$this->root->url()}/src/Entity/"))->write(new GeneratedClass(
            'User',
            '<?php // user',
        ));

        static::assertSame(['vfs://root/src/Entity/User.php', '<?php // user'], [$path, file_get_contents($path)]);
    }

    protected function setUp(): void
    {
        $this->root = vfsStream::setup();
    }
}
