<?php
namespace Ghazy\ImportBundle\Command\Proxy;

use Symfony\Component\Yaml\Parser;

class DataLoader
{
	private  $references =array();
	private  $compter;
	private  $em;
	private  $dataBuilder;
	private  $data;
	private  $info;
	
	public function __construct($em, $dataBuilder)
	{
		$this->em = $em;
		$this->dataBuilder = $dataBuilder;
	}
	
	protected function addReference($key, $reference)
	{
		$this->references[$key] = $reference;
	}
	
	protected function getReference($key)
	{
		return $this->references[$key];
	}
	
	public function loadData()
	{
		$this->data = $this->dataBuilder->getData();
		$this->info = $this->data['info'];
		$this->import();
	}
	
	protected function import() {
		$yaml = new Parser();
		$fixtures = $yaml->parse(file_get_contents(__DIR__.'/../../Resources/config/data_loader.yml'));
		foreach ($fixtures['fixtures'] as $key=>$fixture)
		{
			$this->compter = 0;
			foreach ($this->data[$key] as $data)
			{
				$entityBuilder = new \ReflectionClass($fixture['entity']);
				$entity = $entityBuilder->newInstance();
				foreach ($fixture['mapping'] as $keyMapping=>$mapping)
				{
					$this->compter ++;
					$method = 'set'.ucfirst($keyMapping);
					if(array_key_exists('type', $mapping))
					{
						switch ($mapping['type'])
						{
							case 'ref':
								call_user_func_array(array($entity,$method), array($em->merge($this->getReference($keyMapping.'-'.$data[$mapping['nativeColumn']]))));
								break;
							case 'dateTime':
								$dateTime = new \DateTime();
								$dateTime->setDate(substr($data[$mapping['nativeColumn']],0,4), substr($data[$mapping['nativeColumn']],5,2), substr($data[$mapping['nativeColumn']],8,2));
								$dateTime->setTime(substr($data[$mapping['nativeColumn']],11,2), substr($data[$mapping['nativeColumn']],14,2), substr($data[$mapping['nativeColumn']],17,2));
								call_user_func_array(array($entity,$method), array($dateTime));
								break;
						}
					}
					else 
					{
						call_user_func_array(array($entity,$method), array($data[$mapping['nativeColumn']]));
					}
				}
				$this->em->persist($entity);
				$metadata = $this->em->getClassMetaData(get_class($entity));
				$metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
				$this->addReference($fixture['entity_name'].'-'.$entity->getId(), $entity);
			}
			$this->em->flush();
			$this->info = $this->compter;
		}
	}
	public function getInfo()
	{
		return $this->info;
	}
}
