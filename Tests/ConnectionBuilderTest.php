<?php
namespace Gazelle\Tests;


use Gazelle\Builders\CallbackBuilder;
use Gazelle\Builders\InstanceBuilder;
use Gazelle\ConnectionBuilder;
use Gazelle\Decorators\AbstractChainDecorator;
use Gazelle\Connections\BuilderConnectionProxy;
use Gazelle\Decorators\CallbackDecorator;
use Gazelle\Exceptions\FatalGazelleException;
use Gazelle\IConnection;
use Gazelle\IRequestParams;
use Gazelle\IResponse;
use Gazelle\RequestParams;
use Gazelle\Tests\Support\CountingConnectionBuilder;
use Gazelle\Tests\Support\DummyConnection;
use Gazelle\Tests\Support\SequenceConnection;
use PHPUnit\Framework\TestCase;


class ConnectionBuilderTest extends TestCase
{
	public function testAcceptsInstanceClassCallbackAndBuilder(): void
	{
		$builder = new ConnectionBuilder();
		$instance = new DummyConnection();
		$builder->setMainObject($instance);
		self::assertSame($instance, $builder->get());

		$builder->setMainObject(DummyConnection::class);
		self::assertInstanceOf(DummyConnection::class, $builder->get());

		$builder->setMainObject(fn() => new DummyConnection());
		self::assertInstanceOf(DummyConnection::class, $builder->get());

		$provider = new CountingConnectionBuilder(new DummyConnection());
		$builder->setMainObject($provider);
		$builder->get();
		$builder->get();
		self::assertSame(1, $provider->calls);

		$builder->reuseConnection(false);
		$builder->get();
		$builder->get();
		self::assertSame(3, $provider->calls);
	}

	public function testBuildersRejectInvalidResults(): void
	{
		try {
			(new CallbackBuilder(fn() => new \stdClass()))->get();
			self::fail('CallbackBuilder must reject invalid result');
		} catch (FatalGazelleException) {
			self::addToAssertionCount(1);
		}

		$this->expectException(FatalGazelleException::class);
		(new InstanceBuilder(\stdClass::class))->get();
	}

	public function testConnectionBuilderRejectsInvalidConfiguration(): void
	{
		$builder = new ConnectionBuilder();
		$this->expectException(FatalGazelleException::class);
		$builder->setMainObject(new \stdClass());
	}

	public function testReuseCannotBeDisabledForFixedInstance(): void
	{
		$builder = new ConnectionBuilder();
		$builder->setMainObject(new DummyConnection());

		$this->expectException(FatalGazelleException::class);
		$builder->reuseConnection(false);
	}

	public function testTerminalCallbackDecoratorAndProxy(): void
	{
		$response = $this->createMock(IResponse::class);
		$connection = new SequenceConnection($response);
		$builder = new ConnectionBuilder();
		$builder->setMainObject($connection);
		$builder->addDecorators(function (IRequestParams $request) use ($response): IResponse {
			$request->setHeader('X-Decorated', 'yes');
			return $response;
		}, true);

		$proxy = new BuilderConnectionProxy($builder);
		$request = new RequestParams();
		self::assertSame($response, $proxy->request($request));
		self::assertSame('yes', $request->getHeader('X-Decorated'));
		self::assertCount(0, $connection->requests, 'A callback decorator is intentionally terminal');
		self::assertInstanceOf(CallbackDecorator::class, $builder->get());
	}

	public function testNonTerminalDecoratorAndProxyInvokeWrappedConnection(): void
	{
		$response = $this->createMock(IResponse::class);
		$connection = new SequenceConnection($response);
		$decorator = new class extends AbstractChainDecorator {
			protected function before(IRequestParams $requestParams): void
			{
				$requestParams->setHeader('X-Decorated', 'yes');
			}
		};
		$builder = new ConnectionBuilder();
		$builder->setMainObject($connection);
		$builder->addDecorators($decorator, true);

		$request = new RequestParams();
		self::assertSame($response, (new BuilderConnectionProxy($builder))->request($request));
		self::assertCount(1, $connection->requests);
		self::assertSame('yes', $connection->requests[0]->getHeader('X-Decorated'));
	}

	public function testCallbackDecoratorRejectsWrongReturnType(): void
	{
		$decorator = new CallbackDecorator(fn() => new \stdClass());
		$this->expectException(FatalGazelleException::class);
		$decorator->request(new RequestParams());
	}
}
