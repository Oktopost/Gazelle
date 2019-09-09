<?php
namespace Gazelle\Utils;


interface IWithCurlOptions
{
	public function getCurlOptions(): array;
}