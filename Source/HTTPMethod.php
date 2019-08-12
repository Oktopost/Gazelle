<?php
namespace Gazelle;


use Traitor\TConstsClass;


class HTTPMethod
{
	use TConstsClass;
	
	
	public const GET		= 'get';
	public const HEAD		= 'head';
	public const POST		= 'post';
	public const PUT		= 'put';
	public const DELETE		= 'delete';
	public const CONNECT	= 'connect';
	public const OPTIONS	= 'options';
	public const TRACE		= 'trace';
	public const PATCH		= 'patch';
}