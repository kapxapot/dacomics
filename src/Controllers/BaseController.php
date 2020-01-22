<?php

namespace App\Controllers;

use Plasticode\Controllers\Controller;

class BaseController extends Controller {
	protected $autoOneColumn = false;

	protected function buildParams($settings)
	{
		$params = parent::buildParams($settings);

        $game = $settings['game'] ?? null;
        
        if ($game) {
		    $params['game'] = $this->builder->buildGame($game);
        }

		return $params;
	}

	protected function buildPart($settings, $result, $part) {
		switch ($part) {
			case 'button':
				$result[$part] = true;
				break;

			default:
				$result = null;
				break;
		}
		
		return $result;
	}
}
