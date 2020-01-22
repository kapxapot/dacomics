<?php

namespace App\Controllers\Admin;

use Plasticode\Controllers\Admin\ImageUploadController;
use Plasticode\IO\File;
use Plasticode\Util\Date;

use App\Data\Tables;

class StripController extends ImageUploadController
{
	/**
	 * Adds strips.
	 */
	protected function addImage($context, $image, $fileName)
	{
	    $item = $this->db->create(Tables::STRIPS, $context);
	    $item->title = File::getName($fileName);
	    $item->type = $image->imgType;
	    $item->published = 1;
	    $item->published_at = Date::dbNow();

        $this->db->dirty(Tables::STRIPS, $item); // !!!!!
        
	    $item->save(); // !!!

	    $this->strips->saveImage($item, $image);
	}
}
