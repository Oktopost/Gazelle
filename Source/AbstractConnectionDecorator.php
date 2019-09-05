<?php
namespace Gazelle;


abstract class AbstractConnectionDecorator implements IConnectionDecorator
{
	/** @var IConnection */
	private $child;
	
	
	protected function getChild(): IConnection
	{
		return $this->child;
	}
	
	protected function invokeChild(IRequestSettings $data, IRequestConfig $config): IResponseData
	{
		return $this->child->request($data, $config);
	}
	
	
	public function setChild(IConnection $connection): void
	{
		$this->child = $connection;
	}
}