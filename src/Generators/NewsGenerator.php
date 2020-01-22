<?php

namespace App\Generators;

use Plasticode\Generators\EntityGenerator;
use Plasticode\Traits\Publishable;

use App\Data\Taggable;

class NewsGenerator extends EntityGenerator
{
	use Publishable;
	
	protected $taggable = Taggable::NEWS;
	
	public function beforeSave($data, $id = null)
	{
		$data['cache'] = null;

		$data = $this->publishIfNeeded($data);		

		return $data;
	}
}
