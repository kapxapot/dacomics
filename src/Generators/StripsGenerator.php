<?php

namespace App\Generators;

use Plasticode\Generators\EntityGenerator;
use Plasticode\Traits\Publishable;

use App\Data\Taggable;

class StripsGenerator extends EntityGenerator
{
	use Publishable;
	
	protected $taggable = Taggable::STRIPS;
	
	public function getRules($data, $id = null)
	{
	    $rules = parent::getRules($data, $id);
	    
	    $rules['picture'] = $this->optional('image');
	    $rules['thumb'] = $this->rule('image');
	    
	    return $rules;
	}

	public function getOptions()
	{
	    $options = parent::getOptions();
	    
		$options['admin_template'] = 'strips';
    	$options['admin_args'] = [
		    'upload_path' => 'admin.strips.upload',
		];
	    
	    return $options;
	}

	public function afterLoad($item)
	{
		$item['picture'] = $this->strips->getPictureUrl($item);
		$item['thumb'] = $this->strips->getThumbUrl($item);
		
		unset($item['type']);

		$item['page_url'] = $this->linker->strip($item['id']);

		return $item;
	}

	public function beforeSave($data, $id = null)
	{
		if (isset($data['points'])) {
			unset($data['points']);
		}

		if (isset($data['picture'])) {
			unset($data['picture']);
		}

		if (isset($data['thumb'])) {
			unset($data['thumb']);
		}

		$data = $this->publishIfNeeded($data);		
		
		return $data;
	}
	
	public function afterSave($item, $data)
	{
		$this->strips->save($item, $data);
	}
	
	public function afterDelete($item)
	{
		$this->strips->delete($item);
	}
}
