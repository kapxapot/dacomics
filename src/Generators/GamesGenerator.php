<?php

namespace App\Generators;

use Plasticode\Generators\EntityGenerator;

class GamesGenerator extends EntityGenerator
{
	public function afterLoad($item)
	{
		$item['tags'] = $item['autotags'];

		return $item;
	}
}
