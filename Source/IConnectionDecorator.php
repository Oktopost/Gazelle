<?php
namespace Gazelle;


interface IConnectionDecorator extends IConnection
{
	public function setChild(IConnection $connection): void;
}