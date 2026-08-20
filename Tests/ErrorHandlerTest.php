<?php
namespace Gazelle\Tests;


use Gazelle\Exceptions\Response\ClientException;
use Gazelle\Exceptions\Response\ServerException;
use Gazelle\IResponse;
use Gazelle\RequestMetaData;
use Gazelle\RequestParams;
use Gazelle\Response;
use Gazelle\Utils\ErrorHandler;
use PHPUnit\Framework\TestCase;


class ErrorHandlerTest extends TestCase
{
	private function response(int $code): Response
	{
		return (new Response(new RequestParams(), new RequestMetaData(0.0, 1.0)))
			->setCode($code)
			->setBody('')
			->setHeaders([]);
	}

	/** @dataProvider errorProvider */
	public function testMapsStatusToSpecificException(int $code, string $exception): void
	{
		try {
			ErrorHandler::handle($this->response($code));
			self::fail("Expected exception for HTTP $code");
		} catch (\Throwable $error) {
			self::assertInstanceOf($exception, $error);
			self::assertSame($code, $error->code());
			self::assertSame($code >= 500, $error->isServerError());
			self::assertSame($code < 500, $error->isClientError());
			self::assertInstanceOf(IResponse::class, $error->response());
			self::assertInstanceOf(RequestParams::class, $error->request());
		}
	}

	public function testSuccessfulResponseIsIgnored(): void
	{
		ErrorHandler::handle($this->response(204));
		self::addToAssertionCount(1);
	}

	public static function errorProvider(): array
	{
		return [
			[400, ClientException\BadRequestException::class],
			[401, ClientException\UnauthorizedException::class],
			[402, ClientException\PaymentRequiredException::class],
			[403, ClientException\ForbiddenException::class],
			[404, ClientException\NotFoundException::class],
			[405, ClientException\MethodNotAllowedException::class],
			[409, ClientException\ConflictException::class],
			[411, ClientException\LengthRequiredException::class],
			[412, ClientException\PreconditionFailedException::class],
			[415, ClientException\UnsupportedMediaTypeException::class],
			[426, ClientException\UpgradeRequiredException::class],
			[429, ClientException\TooManyRequestsException::class],
			[418, ClientException\GenericClientException::class],
			[500, ServerException\InternalServerErrorException::class],
			[501, ServerException\NotImplementedException::class],
			[502, ServerException\BadGatewayException::class],
			[503, ServerException\ServiceUnavailableException::class],
			[504, ServerException\GatewayTimeoutException::class],
			[598, ServerException\NetworkReadTimeoutException::class],
			[599, ServerException\NetworkConnectTimeoutException::class],
			[505, ServerException\GenericServerException::class],
		];
	}
}
