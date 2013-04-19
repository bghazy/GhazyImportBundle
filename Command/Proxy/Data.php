<?php
namespace Ghazy\ImportBundle\Command\Proxy;

use Symfony\Component\Yaml\Parser;

class Data
{
	private $conn;
	
	public function __construct($conn)
	{
		$this->conn = $conn;
	}
	
	public function getData()
	{
		$data = array();
		try
		{
			$yaml = new Parser();
			$querys = $yaml->parse(file_get_contents(__DIR__.'/../../Resources/config/data.yml'));
			foreach ($querys['querys'] as $key=>$query)
			{
				$data[$key] = $this->conn->fetchAll($query);
				$data['info'][$key] = count($data[$key]);
			}
		}
		catch(\Exception $e)
		{
			$data['info'] = $e;
		}
		return $data;
	}
}