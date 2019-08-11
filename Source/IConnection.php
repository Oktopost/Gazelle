<?php
namespace Gazelle;


interface IConnection
{
	public function request(IRequestData $requestData, IRequestConfig $config): IResponseData;
}