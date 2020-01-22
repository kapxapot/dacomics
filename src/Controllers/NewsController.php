<?php

namespace App\Controllers;

use Plasticode\Util\Sort;

class NewsController extends BaseController {
	public function item($request, $response, $args) {
		$id = $args['id'];
		$rebuild = $request->getQueryParam('rebuild', false);

		$row = $this->db->getNews($id);
		
		if (!$row) {
			return $this->notFound($request, $response);
		}

		$news = $this->builder->buildNews($row, true, $rebuild);

		$params = $this->buildParams([
			'news_id' => $id,
			'params' => [
				'news_item' => $news,
				'title' => $news['title'],
				'page_description' => $news['description'],
			],
		]);
		
		return $this->view->render($response, 'main/news/item.twig', $params);
	}
}
