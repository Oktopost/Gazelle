<?php
namespace Gazelle\Tests;


use Gazelle\AbstractConnector;
use Gazelle\Exceptions\GazelleException;
use Gazelle\Gazelle;
use Gazelle\GazelleMock;
use Gazelle\IRequestParams;
use Gazelle\Request;
use Gazelle\RequestMetaData;
use Gazelle\RequestParams;
use Gazelle\Response;
use Gazelle\Tests\Support\SequenceConnection;
use Gazelle\Tests\Support\QueueMultiConnection;
use Gazelle\Tests\Support\RecordingMultiDecorator;
use PHPUnit\Framework\TestCase;


class GazelleFacadeTest extends TestCase
{
	private function response(string $body = 'body'): Response
	{
		return (new Response(new RequestParams(), new RequestMetaData(0.0, 1.0)))
			->setCode(200)->setHeaders([])->setBody($body);
	}

	public function testConfigurationTemplateAndRequestCloning(): void
	{
		$connection = new SequenceConnection($this->response());
		$gazelle = (new Gazelle())->setConnection($connection)->reuseConnection(true);

		$gazelle->template()->setURL('https://example.test/base')->setHeader('X-Base', 'yes');
		$request = $gazelle->request('https://other.test/path', ['X-Request' => 'yes']);
		self::assertInstanceOf(Request::class, $request);
		self::assertSame('other.test', $request->getDomain());
		self::assertSame('yes', $request->getHeader('X-Base'));
		self::assertSame('yes', $request->getHeader('X-Request'));
		self::assertNotSame($gazelle->template(), $request);
	}

	public function testMultiDecoratorsAreInstalledAndExecutedAsAChain(): void
	{
		$events = new \ArrayObject();
		$inner = new RecordingMultiDecorator($events, 'inner');
		$outer = new RecordingMultiDecorator($events, 'outer');
		$connection = new QueueMultiConnection();
		GazelleMock::$multiConnection = $connection;

		try {
			$gazelle = new Gazelle();
			self::assertSame($gazelle, $gazelle->addMultiDecorators($inner));
			self::assertSame($gazelle, $gazelle->addMultiDecorators([$outer]));
			$result = $gazelle->multi()->request('https://example.test')->get();

			self::assertSame(['outer', 'inner'], $events->getArrayCopy());
			self::assertSame([$result], $connection->sent);
		} finally {
			GazelleMock::reset();
		}
	}

	public function testFileGetContentNormalAndSafeFailure(): void
	{
		$gazelle = (new Gazelle())->setConnection(new SequenceConnection($this->response('content')));
		self::assertSame('content', $gazelle->fileGetContent('https://example.test'));

		$error = new GazelleException('failed');
		$gazelle->setConnection(new SequenceConnection($error));
		$caught = null;
		self::assertNull($gazelle->fileGetContent('https://example.test', true, $caught));
		self::assertSame($error, $caught);
	}

	public function testConnectorBuildsRelativeAndAbsoluteRequests(): void
	{
		$connector = new class extends AbstractConnector {
			protected function setupTemplate(IRequestParams $template): void
			{
				$template->setURL('https://example.test/api/');
			}

			protected function getDefaultTags(): array
			{
				return ['scope' => 'test'];
			}

			public function build($path, array $params, array $headers, string $body): Request
			{
				return $this->request($path, $params, $headers, $body);
			}
		};

		$relative = $connector->build('/users', ['q' => 'one two'], ['X-Test' => 'yes'], 'payload');
		self::assertSame('/api/users', $relative->getPath());
		self::assertSame('one two', $relative->getQueryParam('q'));
		self::assertSame('yes', $relative->getHeader('X-Test'));
		self::assertSame('payload', $relative->getBody());
		self::assertSame('test', $relative->getTag('scope'));

		$absolute = $connector->build('http://other.test/root', [], [], '0');
		self::assertSame('other.test', $absolute->getDomain());
	}
}
