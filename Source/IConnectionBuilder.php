<?php
namespace Gazelle;


interface IConnectionBuilder
{
	public function get(): IConnection;
}