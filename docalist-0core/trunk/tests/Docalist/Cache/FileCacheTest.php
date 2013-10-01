<?php
namespace Docalist\Tests\Cache;

use WP_UnitTestCase;
use Docalist\Cache\FileCache;

class FileCacheTest extends WP_UnitTestCase {
    /**
     * @var FileCache
     */
    protected $cache;
    protected $root;
    protected $dir;

    public function setup() {
        parent::setup();

        $this->root = __DIR__;
        $this->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'docalist-cache-tests';

        $this->cache = new FileCache($this->root, $this->dir);
    }

    public function testCache() {
        $this->assertSame($this->cache->root(), $this->root . DIRECTORY_SEPARATOR);
        $this->assertSame($this->cache->directory(), $this->dir . DIRECTORY_SEPARATOR);
    }

    public function testInexistent() {
        $file = DIRECTORY_SEPARATOR . 'dir' . DIRECTORY_SEPARATOR . 'subdir' . DIRECTORY_SEPARATOR . 'inexistent.txt';

        $path = $this->root . $file;
        $cachePath = $this->dir . $file;

        $this->assertSame($this->cache->path($path), $cachePath);

        $this->assertFalse($this->cache->has($path));
        $this->assertNull($this->cache->get($path));
    }

    public function testExistent() {
        $file = DIRECTORY_SEPARATOR . 'dir' . DIRECTORY_SEPARATOR . 'subdir' . DIRECTORY_SEPARATOR . 'toto.txt';
        $path = $this->root . $file;

        $this->cache->put($path, 'abcd');

        $this->assertTrue($this->cache->has($path));
        $this->assertSame($this->cache->get($path), 'abcd');

        $this->assertTrue($this->cache->clear($path));

        $this->assertFalse($this->cache->has($path));
        $this->assertNull($this->cache->get($path));
    }

    public function testClear() {
        $file = DIRECTORY_SEPARATOR . 'dir' . DIRECTORY_SEPARATOR . 'subdir' . DIRECTORY_SEPARATOR . 'toto.txt';
        $path1 = $this->root . $file;
        $path2 = $this->root . $file . '.bis';

        $this->cache->put($path1, 'abcd');
        $this->cache->put($path2, 'efgh');

        $dir = $this->dir . DIRECTORY_SEPARATOR . 'dir';

        $this->assertTrue(file_exists($dir));

        $this->assertTrue($this->cache->clear($path1));

        $this->assertFalse($this->cache->has($path1));
        $this->assertTrue($this->cache->has($path2));

        $this->assertTrue($this->cache->clear($path2));

        $this->assertFalse($this->cache->has($path2));

        $this->assertFalse(file_exists($dir));
    }
}