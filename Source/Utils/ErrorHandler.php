<?php
namespace Gazelle\Utils;


use Gazelle\Exceptions\Request\TimeoutException;
use Traitor\TStaticClass;

use Gazelle\IResponseData;
use Gazelle\Exceptions\Response\ServerException;
use Gazelle\Exceptions\Response\ClientException;
use Gazelle\Exceptions\Request\UnhandledCurlException;


class ErrorHandler
{
	use TStaticClass;
	
	
	/**
	 * @param resource $resource
	 * @param IResponseData $data
	 */
	public static function handleCurlException($resource, IResponseData $data): void
	{
		$code = curl_errno($resource);
		
		switch ($code)
		{
			case CURLE_OPERATION_TIMEOUTED:
				throw new TimeoutException($data);
			
			default:
				throw new UnhandledCurlException($resource, $data->getRequestParams());
		}
	}
	
	
	public static function handle(IResponseData $responseData): void
	{
		if (!$responseData->isFailed())
			return;
		
		$code = $responseData->getCode();
		
		switch ($code)
		{
			case 400:
				throw new ClientException\BadRequestException($responseData);
			case 401:
				throw new ClientException\UnauthorizedException($responseData);
			case 402:
				throw new ClientException\PaymentRequiredException($responseData);
			case 403:
				throw new ClientException\ForbiddenException($responseData);
			case 404:
				throw new ClientException\NotFoundException($responseData);
			case 405:
				throw new ClientException\MethodNotAllowedException($responseData);
			case 409:
				throw new ClientException\ConflictException($responseData);
			case 411:
				throw new ClientException\LengthRequiredException($responseData);
			case 412:
				throw new ClientException\PreconditionFailedException($responseData);
			case 415:
				throw new ClientException\UnsupportedMediaTypeException($responseData);
			case 426:
				throw new ClientException\UpgradeRequiredException($responseData);
			case 429:
				throw new ClientException\TooManyRequestsException($responseData);
		}
		
		switch ($code)
		{
			case 500:
				throw new ServerException\InternalServerErrorException($responseData);
			case 501:
				throw new ServerException\NotImplementedException($responseData);
			case 502:
				throw new ServerException\BadGatewayException($responseData);
			case 503:
				throw new ServerException\ServiceUnavailableException($responseData);
			case 504:
				throw new ServerException\GatewayTimeoutException($responseData);
			case 598:
				throw new ServerException\NetworkReadTimeoutException($responseData);
			case 599:
				throw new ServerException\NetworkConnectTimeoutException($responseData);
		}
		
		if (400 <= $code && $code < 500)
		{
			throw new ClientException\GenericClientException($responseData);
		}
		else 
		{
			throw new ServerException\GenericServerException($responseData);
		}
	}
}