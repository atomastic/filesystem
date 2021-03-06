<?php

declare(strict_types=1);

use Atomastic\Filesystem\Filesystem;
use Atomastic\Filesystem\File;
use Atomastic\Filesystem\Directory;

beforeEach(function (): void {
    $this->tempDir = __DIR__ . '/tmp';
    @mkdir($this->tempDir);
});

afterEach(function (): void {
    $filesystem = new Filesystem();
    $filesystem->directory($this->tempDir)->delete();
    unset($this->tempDir);
});

test('test instances', function (): void {
    $this->assertInstanceOf(Filesystem::class, new Filesystem);
    $this->assertInstanceOf(File::class, new File('/1/1.txt'));
    $this->assertInstanceOf(Directory::class, new Directory('/1'));
});

test('test filesystem helper', function (): void {
    $this->assertInstanceOf(Filesystem::class, filesystem());
});

test('test deleteDirectory() method', function (): void {
    @mkdir($this->tempDir . '/1');
    @mkdir($this->tempDir . '/1/2');
    @mkdir($this->tempDir . '/1/2/3');

    $filesystem = new Filesystem();
    $this->assertTrue($filesystem->directory($this->tempDir . '/1')->delete());
});

test('test put() method', function (): void {
    $filesystem = new Filesystem();
    $this->assertEquals(4, $filesystem->file($this->tempDir . '/2.txt')->put('test'));
});

test('test isFile() method', function (): void {
    $filesystem = new Filesystem();
    $filesystem->file($this->tempDir . '/1.txt')->put('test');
    $this->assertTrue($filesystem->file($this->tempDir . '/1.txt')->isFile());
});

test('test isWindowsPath() method', function (): void {
    $filesystem = new Filesystem();
    $this->assertTrue($filesystem->isWindowsPath('C:\file\1.txt'));
});

test('test isDirectory() method', function (): void {
    $filesystem = new Filesystem();
    $this->assertTrue($filesystem->directory($this->tempDir)->isDirectory());
});

test('test isReadable() method', function (): void {
    if (PHP_OS_FAMILY === 'Windows') {
        $this->markTestSkipped('The operating system is Windows');
    }

    $filesystem = new Filesystem();
    $filesystem->file($this->tempDir . '/1.txt')->put('test');

    @chmod($this->tempDir . '/1.txt', 0000);
    $this->assertFalse($filesystem->file($this->tempDir . '/1.txt')->isReadable());
    @chmod($this->tempDir . '/1.txt', 0777);
    $this->assertTrue($filesystem->file($this->tempDir . '/1.txt')->isReadable());

    $this->assertFalse($filesystem->file($this->tempDir . '/2.txt')->isReadable());
});


test('test isWritable() method', function (): void {

    if (PHP_OS_FAMILY === 'Windows') {
        $this->markTestSkipped('The operating system is Windows');
    }

    $filesystem = new Filesystem();
    $filesystem->file($this->tempDir . '/1.txt')->put('test');

    @chmod($this->tempDir . '/1.txt', 0444);
    $this->assertFalse($filesystem->file($this->tempDir . '/1.txt')->isWritable());
    @chmod($this->tempDir . '/1.txt', 0777);
    $this->assertTrue($filesystem->file($this->tempDir . '/1.txt')->isWritable());

    $this->assertFalse($filesystem->file($this->tempDir . '/2.txt')->isWritable());
});

test('test isStream() method', function (): void {
    $filesystem = new Filesystem();
    $this->assertTrue($filesystem->isStream('file://1.txt'));
});


test('test isAbsolute method', function (): void {
    $filesystem = new Filesystem();

    $this->assertFalse($filesystem->isAbsolute(''));
    $this->assertTrue($filesystem->isAbsolute('\\'));
    $this->assertTrue($filesystem->isAbsolute('//'));
    $this->assertFalse($filesystem->isAbsolute('file'));
    $this->assertFalse($filesystem->isAbsolute('dir:/file'));
    $this->assertFalse($filesystem->isAbsolute('dir:\file'));
    $this->assertTrue($filesystem->isAbsolute('c:/file'));
    $this->assertTrue($filesystem->isAbsolute('c:/file/file.txt'));
    $this->assertTrue($filesystem->isAbsolute('c:\file'));
    $this->assertTrue($filesystem->isAbsolute('C:\file'));
    $this->assertTrue($filesystem->isAbsolute('http://file'));
    $this->assertTrue($filesystem->isAbsolute('remote://file'));
});

test('test exists() method', function (): void {
    $filesystem = new Filesystem();
    $filesystem->file($this->tempDir . '/1.txt')->put('test');
    $this->assertTrue($filesystem->file($this->tempDir . '/1.txt')->exists());

    $filesystem->file($this->tempDir . '/1.txt')->put('test');
    $filesystem->file($this->tempDir . '/2.txt')->put('test');
    $this->assertTrue($filesystem->file($this->tempDir . '/1.txt')->exists());
    $this->assertTrue($filesystem->file($this->tempDir . '/2.txt')->exists());
});

test('test delete() method', function (): void {
    $filesystem = new Filesystem();
    $filesystem->file($this->tempDir . '/1.txt')->put('test');
    $this->assertTrue($filesystem->file($this->tempDir . '/1.txt')->delete());

    $filesystem->file($this->tempDir . '/1.txt')->put('test');
    $filesystem->file($this->tempDir . '/2.txt')->put('test');
    $this->assertTrue($filesystem->file($this->tempDir . '/1.txt')->delete());
    $this->assertTrue($filesystem->file($this->tempDir . '/2.txt')->delete());
});

test('test hash() method', function (): void {
    $filesystem = new Filesystem();
    $filesystem->file($this->tempDir . '/1.txt')->put('test');
    $this->assertEquals('098f6bcd4621d373cade4e832627b4f6', $filesystem->file($this->tempDir . '/1.txt')->hash());
});


test('test get() method', function (): void {
    $filesystem = new Filesystem();
    $filesystem->file($this->tempDir . '/1.txt')->put('test');
    $this->assertEquals('test', $filesystem->file($this->tempDir . '/1.txt')->get());
});

test('test prepend() method', function (): void {
    $filesystem = new Filesystem();
    $filesystem->file($this->tempDir . '/1.txt')->put('world');
    $this->assertEquals(11, $filesystem->file($this->tempDir . '/1.txt')->prepend('hello '));
    $this->assertEquals('hello world', $filesystem->file($this->tempDir . '/1.txt')->get());
});

test('test append() method', function (): void {
    $filesystem = new Filesystem();
    $filesystem->file($this->tempDir . '/1.txt')->put('hello');
    $this->assertEquals(6, $filesystem->file($this->tempDir . '/1.txt')->append(' world'));
    $this->assertEquals('hello world', $filesystem->file($this->tempDir . '/1.txt')->get());
});


test('test chmod() method', function (): void {
    if (PHP_OS_FAMILY === 'Windows') {
        $this->markTestSkipped('The operating system is Windows');
    }

    $filesystem = new Filesystem();
    $filesystem->file($this->tempDir . '/1.txt')->put('test');

    // Set
    $filesystem->file($this->tempDir . '/1.txt')->chmod(0755);
    $filePermission      = substr(sprintf('%o', fileperms($this->tempDir . '/1.txt')), -4);
    $expectedPermissions = DIRECTORY_SEPARATOR === '\\' ? '0666' : '0755';
    $this->assertEquals($expectedPermissions, $filePermission);

    // Get
    $filesystem->file($this->tempDir . '/2.txt')->put('test');
    chmod($this->tempDir . '/2.txt', 0755);
    $filePermission      = $filesystem->file($this->tempDir . '/1.txt')->chmod();
    $expectedPermissions = DIRECTORY_SEPARATOR === '\\' ? '0666' : '0755';
    $this->assertEquals($expectedPermissions, $filePermission);
});

test('test directory chmod() method', function (): void {
    if (PHP_OS_FAMILY === 'Windows') {
        $this->markTestSkipped('The operating system is Windows');
    }

    $filesystem = new Filesystem();

    // Set
    $filesystem->directory($this->tempDir)->chmod(0755);
    $filePermission      = substr(sprintf('%o', fileperms($this->tempDir)), -4);
    $expectedPermissions = DIRECTORY_SEPARATOR === '\\' ? '0666' : '0755';
    $this->assertEquals($expectedPermissions, $filePermission);

    // Get
    chmod($this->tempDir, 0755);
    $filePermission      = $filesystem->directory($this->tempDir)->chmod();
    $expectedPermissions = DIRECTORY_SEPARATOR === '\\' ? '0666' : '0755';
    $this->assertEquals($expectedPermissions, $filePermission);
});


test('test copy() method', function (): void {
    $filesystem = new Filesystem();
    $filesystem->file($this->tempDir . '/1.txt')->put('hello');
    $this->assertTrue($filesystem->file($this->tempDir . '/1.txt')->copy($this->tempDir . '/2.txt'));
});


test('test move() method', function (): void {
    $filesystem = new Filesystem();
    $filesystem->file($this->tempDir . '/1.txt')->put('hello');
    $filesystem->file($this->tempDir . '/1.txt')->move($this->tempDir . '/2.txt');
    $this->assertTrue($filesystem->file($this->tempDir . '/2.txt')->exists());
});

test('test directory create() method', function (): void {
    $filesystem = new Filesystem();
    $this->assertTrue($filesystem->directory($this->tempDir . '/1')->create());
    $this->assertTrue($filesystem->directory($this->tempDir . '/1/2/3/4/')->create(0755, true));
    $this->assertTrue($filesystem->directory($this->tempDir . '/2/3/4/')->create(0755, true));
});


test('test directory move() method', function (): void {
    @mkdir($this->tempDir . '/1');
    @mkdir($this->tempDir . '/3');

    $filesystem = new Filesystem();
    $this->assertTrue($filesystem->directory($this->tempDir . '/1')->move($this->tempDir . '/2'));
    $this->assertTrue($filesystem->directory($this->tempDir . '/3')->move($this->tempDir . '/4'));
});


test('test directory copy() method', function (): void {
    @mkdir($this->tempDir . '/1');
    @mkdir($this->tempDir . '/3');
    @mkdir($this->tempDir . '/4');

    $filesystem = new Filesystem();
    $this->assertTrue($filesystem->directory($this->tempDir . '/1')->copy($this->tempDir . '/2'));
    $this->assertTrue($filesystem->directory($this->tempDir . '/3')->copy($this->tempDir . '/4'));
});


test('test directory delete() method', function (): void {
    @mkdir($this->tempDir . '/1');
    @mkdir($this->tempDir . '/1/2');
    @mkdir($this->tempDir . '/1/2/3');

    $filesystem = new Filesystem();
    $this->assertTrue($filesystem->directory($this->tempDir . '/1')->delete());
});


test('test directory clean() method', function (): void {
    @mkdir($this->tempDir . '/1');
    $filesystem = new Filesystem();
    $filesystem->file($this->tempDir . '/1/1.txt')->put('hello');

    $filesystem = new Filesystem();
    $this->assertTrue($filesystem->directory($this->tempDir . '/1')->clean());
    $this->assertFalse($filesystem->file($this->tempDir . '/1/1.txt')->exists());
});


test('test glob() method', function (): void {
    $filesystem = new Filesystem();
    $filesystem->file($this->tempDir . '/1.txt')->put('hello');
    $filesystem->file($this->tempDir . '/2.txt')->put('world');

    $glob = $filesystem->glob($this->tempDir . '/*.txt');
    $this->assertContains($this->tempDir . '/1.txt', $glob);
    $this->assertContains($this->tempDir . '/2.txt', $glob);

    $glob = $filesystem->glob($this->tempDir . '/*.html');
    $this->assertEquals(0, count($glob));
});

test('test size() method', function (): void {
    $filesystem = new Filesystem();
    $filesystem->file($this->tempDir . '/1.txt')->put('hello world');

    $this->assertEquals(11, $filesystem->file($this->tempDir . '/1.txt')->size());
});


test('test direcotory size() method', function (): void {
    $filesystem = new Filesystem();
    @mkdir($this->tempDir . '/1');
    @mkdir($this->tempDir . '/1/2');
    $filesystem->file($this->tempDir . '/1/1.txt')->put('hello world');
    $filesystem->file($this->tempDir . '/1/2.txt')->put('hello world');
    $filesystem->file($this->tempDir . '/1/2/1.txt')->put('hello world');
    $filesystem->file($this->tempDir . '/1/2/2.txt')->put('hello world');

    $this->assertEquals(44, $filesystem->directory($this->tempDir . '/1')->size());
});

test('test lastModified() method', function (): void {
    $filesystem = new Filesystem();

    $filesystem->file($this->tempDir . '/1.txt')->put('hello world');
    $time = filemtime($this->tempDir . '/1.txt');

    $this->assertEquals($time, $filesystem->file($this->tempDir . '/1.txt')->lastModified());
});

test('test lastAccess() method', function (): void {
    $filesystem = new Filesystem();

    $filesystem->file($this->tempDir . '/1.txt')->put('hello world');
    $time = fileatime($this->tempDir . '/1.txt');

    $this->assertEquals($time, $filesystem->file($this->tempDir . '/1.txt')->lastAccess());
});

test('test mimeType() method', function (): void {
    $filesystem = new Filesystem();

    $filesystem->file($this->tempDir . '/1.txt')->put('hello world');

    $this->assertEquals('text/plain', $filesystem->file($this->tempDir . '/1.txt')->mimeType());
});

test('test type() method', function (): void {
    $filesystem = new Filesystem();

    $filesystem->file($this->tempDir . '/1.txt')->put('hello world');

    $this->assertEquals('file', $filesystem->file($this->tempDir . '/1.txt')->type());
    $this->assertEquals('dir', $filesystem->file($this->tempDir)->type());
});

test('test extension() method', function (): void {
    $filesystem = new Filesystem();

    $filesystem->file($this->tempDir . '/1.txt')->put('hello world');

    $this->assertEquals('txt', $filesystem->file($this->tempDir . '/1.txt')->extension());
});

test('test basename() method', function (): void {
    $filesystem = new Filesystem();

    $filesystem->file($this->tempDir . '/1.txt')->put('hello world');

    $this->assertEquals('1.txt', $filesystem->file($this->tempDir . '/1.txt')->basename());
});


test('test name() method', function (): void {
    $filesystem = new Filesystem();

    $filesystem->file($this->tempDir . '/1.txt')->put('hello world');

    $this->assertEquals('1', $filesystem->file($this->tempDir . '/1.txt')->name());
});

test('test find() method', function (): void {
    @mkdir($this->tempDir . '/1');

    $filesystem = new Filesystem();
    $filesystem->file($this->tempDir . '/1/1.txt')->put('hello world');

    $this->assertEquals(1, count(iterator_to_array($filesystem->find()->in($this->tempDir)->files()->name('*.txt'), false)));

    // alternative
    $this->assertEquals(1, count(iterator_to_array((new Filesystem)->find()->in($this->tempDir)->files()->name('*.txt'), false)));
});

test('test path() method', function (): void {
    $filesystem = new Filesystem();

    $this->assertEquals($this->tempDir . '/1.txt', $filesystem->file($this->tempDir . '/1.txt')->path());
    $this->assertEquals($this->tempDir, $filesystem->directory($this->tempDir)->path());
});

test('test macro() method', function (): void {
    @mkdir($this->tempDir . '/1');
    $filesystem = new Filesystem();
    $filesystem->file($this->tempDir . '/1/1.txt')->put('hello world');
    $filesystem->file($this->tempDir . '/1/2.txt')->put('hello world');

    Filesystem::macro('countFiles', function($path) {
        return count(iterator_to_array($this->find()->in($path)->files(), false));
    });

    $filesystem = new Filesystem();
    $this->assertEquals(2, $filesystem->countFiles($this->tempDir . '/1'));
});
