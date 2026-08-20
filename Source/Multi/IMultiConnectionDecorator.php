<?php
namespace Gazelle\Multi;


interface IMultiConnectionDecorator extends IMultiConnection
{
	public function setNextConnection(IMultiConnection $connection): void;
}