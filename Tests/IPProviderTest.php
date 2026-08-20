<?php
namespace Gazelle\Tests;


use Gazelle\Tests\Support\StaticIPProvider;
use Gazelle\Utils\IP\AbstractIPProviderCache;
use Gazelle\Utils\IP\FileCacheIPProvider;
use Gazelle\Utils\IP\MemoryCacheIPProvider;
use PHPUnit\Framework\TestCase;


class IPProviderTest extends TestCase
{
	protected function setUp(): void
	{
		$this->resetStaticMemoryCache();
	}

	protected function tearDown(): void
	{
		$this->resetStaticMemoryCache();
	}

	private function resetStaticMemoryCache(): void
	{
		$property = new \ReflectionProperty(MemoryCacheIPProvider::class, 'staticCache');
		$property->setValue(null, []);
	}

	public function testAbstractProviderRandomSelection(): void
	{
		self::assertNull((new StaticIPProvider([]))->getRandomIP());
		self::assertSame('127.0.0.1', (new StaticIPProvider(['127.0.0.1']))->getRandomIP());
		self::assertContains(
			(new StaticIPProvider(['127.0.0.1', '127.0.0.2']))->getRandomIP(),
			['127.0.0.1', '127.0.0.2']
		);
	}

	public function testMemoryCacheReadsParentOnce(): void
	{
		$parent = new StaticIPProvider(['127.0.0.1']);
		$cache = new MemoryCacheIPProvider(60);
		$cache->setParent($parent);

		self::assertSame(['127.0.0.1'], $cache->getAllIPs());
		self::assertSame(['127.0.0.1'], $cache->getAllIPs());
		self::assertSame(1, $parent->calls);
	}

	public function testStaticMemoryCacheAndConstructorValidation(): void
	{
		$parent = new StaticIPProvider(['10.0.0.1']);
		$first = new MemoryCacheIPProvider(60, 'group', 'host');
		$first->setParent($parent);
		self::assertSame(['10.0.0.1'], $first->getAllIPs());

		$second = new MemoryCacheIPProvider(60, 'group', 'host');
		$second->setParent(new StaticIPProvider(['different']));
		self::assertSame(['10.0.0.1'], $second->getAllIPs());

		$this->expectException(\Exception::class);
		new MemoryCacheIPProvider(60, 'group');
	}

	public function testMemoryCacheRefreshesFromParentAfterExpiration(): void
	{
		$initial = new StaticIPProvider(['10.0.0.1']);
		$cache = new MemoryCacheIPProvider(60);
		$cache->setParent($initial);
		self::assertSame(['10.0.0.1'], $cache->getAllIPs());

		$updated = new StaticIPProvider(['10.0.0.2']);
		$cache->setParent($updated);
		$timeout = new \ReflectionProperty(MemoryCacheIPProvider::class, 'timeout');
		$timeout->setValue($cache, time() - 1);

		self::assertSame(['10.0.0.2'], $cache->getAllIPs());
		self::assertSame(1, $updated->calls);
	}

	public function testStaticMemoryCacheRefreshesAfterExpiration(): void
	{
		$initial = new StaticIPProvider(['10.0.1.1']);
		$cache = new MemoryCacheIPProvider(60, 'ttl-test', 'example.test');
		$cache->setParent($initial);
		self::assertSame(['10.0.1.1'], $cache->getAllIPs());

		$property = new \ReflectionProperty(MemoryCacheIPProvider::class, 'staticCache');
		$state = $property->getValue();
		$state['ttl-test:example.test']['timeout'] = time() - 1;
		$property->setValue(null, $state);
		$updated = new StaticIPProvider(['10.0.1.2']);
		$cache->setParent($updated);

		self::assertSame(['10.0.1.2'], $cache->getAllIPs());
		self::assertSame(1, $updated->calls);
	}

	public function testFileCachePersistsParentResult(): void
	{
		$directory = sys_get_temp_dir() . '/gazelle-tests-' . bin2hex(random_bytes(4));
		mkdir($directory);

		try {
			$parent = new StaticIPProvider(['192.0.2.1', '192.0.2.2']);
			$first = new FileCacheIPProvider('example.test', 60, $directory);
			$first->setParent($parent);
			self::assertSame(['192.0.2.1', '192.0.2.2'], $first->getAllIPs());

			$secondParent = new StaticIPProvider(['unwanted']);
			$second = new FileCacheIPProvider('example.test', 60, $directory);
			$second->setParent($secondParent);
			self::assertSame(['192.0.2.1', '192.0.2.2'], $second->getAllIPs());
			self::assertSame(0, $secondParent->calls);
		} finally {
			foreach (glob($directory . '/*') ?: [] as $file)
			{
				unlink($file);
			}
			rmdir($directory);
		}
	}

	public function testFileCacheRefreshesFromParentAfterExpiration(): void
	{
		$directory = sys_get_temp_dir() . '/gazelle-expired-' . bin2hex(random_bytes(4));
		mkdir($directory);
		$cacheFile = $directory . '/_gazelle_example.test_.cache';

		try {
			$initial = new StaticIPProvider(['192.0.2.1']);
			$cache = new FileCacheIPProvider('example.test', 60, $directory);
			$cache->setParent($initial);
			self::assertSame(['192.0.2.1'], $cache->getAllIPs());

			file_put_contents($cacheFile, (time() - 1) . ',192.0.2.1');
			$updated = new StaticIPProvider(['192.0.2.2']);
			$cache->setParent($updated);
			self::assertSame(['192.0.2.2'], $cache->getAllIPs());
			self::assertSame(1, $updated->calls);
		} finally {
			foreach (glob($directory . '/*') ?: [] as $file) {
				unlink($file);
			}
			rmdir($directory);
		}
	}

	public function testFileCacheRandomIPUsesCachedParentResult(): void
	{
		$directory = sys_get_temp_dir() . '/gazelle-random-ip-' . bin2hex(random_bytes(4));
		mkdir($directory);

		try {
			$ips = ['198.51.100.1', '198.51.100.2'];
			$parent = new StaticIPProvider($ips);
			$cache = new FileCacheIPProvider('example.test', 60, $directory);
			$cache->setParent($parent);

			self::assertContains($cache->getRandomIP(), $ips);
			self::assertContains($cache->getRandomIP(), $ips);
			self::assertSame(1, $parent->calls);
		} finally {
			foreach (glob($directory . '/*') ?: [] as $file)
			{
				unlink($file);
			}
			rmdir($directory);
		}
	}

	public function testCacheChainFallsBackToParent(): void
	{
		$base = new StaticIPProvider(['203.0.113.1']);
		$cache = new class extends AbstractIPProviderCache {
			protected function doGetAllIPs(): array { return []; }
		};

		$chain = AbstractIPProviderCache::createChain($base, $cache);
		self::assertSame(['203.0.113.1'], $chain->getAllIPs());
	}

	public function testCacheRandomIPResolvesFromParentCachesResultAndAvoidsRecursion(): void
	{
		$ips = ['203.0.113.10', '203.0.113.11', '203.0.113.12'];
		$autoload = dirname(__DIR__) . '/vendor/autoload.php';
		$code = 'require ' . var_export($autoload, true) . ';'
			. '$parent = new Gazelle\\Tests\\Support\\StaticIPProvider(' . var_export($ips, true) . ');'
			. '$cache = new Gazelle\\Utils\\IP\\MemoryCacheIPProvider(60);'
			. '$cache->setParent($parent);'
			. '$first = $cache->getRandomIP();'
			. '$second = $cache->getRandomIP();'
			. 'echo json_encode(["first" => $first, "second" => $second, "parentCalls" => $parent->calls]);';

		$process = proc_open(
			[PHP_BINARY, '-n', '-d', 'memory_limit=16M', '-r', $code],
			[['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
			$pipes
		);
		self::assertIsResource($process);
		fclose($pipes[0]);
		$output = stream_get_contents($pipes[1]);
		fclose($pipes[1]);
		$stderr = stream_get_contents($pipes[2]);
		fclose($pipes[2]);
		$exitCode = proc_close($process);

		self::assertSame(0, $exitCode, "getRandomIP() must terminate normally, stderr:\n" . $stderr);

		$result = json_decode($output, true);
		self::assertIsArray($result, 'Child process must print a JSON object, got: ' . $output);

		self::assertContains($result['first'], $ips, 'getRandomIP() must return one of the parent IPs');
		self::assertContains($result['second'], $ips, 'getRandomIP() must return one of the parent IPs');
		self::assertSame(1, $result['parentCalls'], 'The second call must be served from the cache, not the parent');
	}
}
