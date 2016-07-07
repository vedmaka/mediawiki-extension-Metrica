<?php

class MetricaAPI extends ApiBase {

	public function execute() {

		$postData = $this->getRequest()->getArray('metrica');

		// Extend metrica data with some more protected information
		$postData['user']['ip'] = $this->getRequest()->getIP();
		$postData['created_at'] = time();

		// Store record
		$entry = new MetricaEntry();

		$entry->action              = $postData['action'];
		$entry->user_id             = $postData['user']['userId'];
		$entry->user_ip             = $postData['user']['ip'];
		$entry->user_logged_in      = $postData['user']['isLoggedIn'] == "1" ? 1 : 0;
		$entry->user_lang           = $postData['user']['userLanguage'];
		$entry->user_name           = $postData['user']['userName'];
		$entry->page_id             = $postData['page']['pageId'];
		$entry->page_revision_id    = $postData['page']['revisionId'];
		$entry->page_is_article     = $postData['page']['isArticle']  == "true" ? 1 : 0;
		$entry->page_name           = $postData['page']['pageName'];
		$entry->page_categories     = count( $postData['page']['categories'] ) ? implode(';', $postData['page']['categories']) : '';
		$entry->page_namespace_id   = $postData['page']['namespace'];
		$entry->page_is_main        = $postData['page']['isMainPage'] == "true" ? 1 : 0;
		$entry->created_at          = $postData['created_at'];

		$entry->save();

		$this->getResult()->addValue( $this->getModuleName(), null, array() );

	}

	public function mustBePosted() {
		return true;
	}

	public function getAllowedParams( /* $flags = 0 */ ) {
		return array(
			'metrica' => array(
				ApiBase::PARAM_REQUIRED => false
			)
		);
	}

}