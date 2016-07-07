<?php


class MetricaStatAPI extends ApiBase {

	public function execute() {

		if( !$this->getUser()->isAllowed('metrica') ) {
			$this->dieUsage('Not enough permissions', 'no_permissions');
		}

		$params = $this->extractRequestParams();
		switch ($params['do']) {
			case 'page_views':
				$this->pageViews();
				break;
			case 'page_edits':
				$this->pageEdits();
				break;
			case 'page_most_viewed':
				$this->pageMostViewed();
				break;
			case 'page_most_edited':
				$this->pageMostEdited();
				break;
		}

	}

	private function pageMostViewed() {

		$data = array();

		$items = wfGetDB(DB_SLAVE)->select(
			'metrica',
			'page_id, page_name, COUNT(*) as `count`',
			array(
				'action' => 'view'
			),
			__METHOD__,
			array(
				'GROUP BY' => 'page_id',
				'ORDER BY' => '`count` DESC'
			)
		);

		if( $items->numRows() ) {
			while( $row = $items->fetchRow() ) {
				$data[] = array(
					'page_id' => $row['page_id'],
					'views' => $row['count'],
					'page_name' => $row['page_name'],
					'link' => Linker::link( Title::newFromID( $row['page_id'] ), $row['page_name'], array( 'target' => '_blank' ) )
				);
			}
		}

		$this->getResult()->addValue( $this->getModuleName(), null, $data );

	}

	private function pageMostEdited() {

		$data = array();

		$items = wfGetDB(DB_SLAVE)->select(
			'metrica',
			'page_id, page_name, COUNT(*) as `count`',
			array(
				'action' => 'edit'
			),
			__METHOD__,
			array(
				'GROUP BY' => 'page_id',
				'ORDER BY' => '`count` DESC'
			)
		);

		if( $items->numRows() ) {
			while( $row = $items->fetchRow() ) {
				$data[] = array(
					'page_id' => $row['page_id'],
					'edits' => $row['count'],
					'page_name' => $row['page_name'],
					'link' => Linker::link( Title::newFromID( $row['page_id'] ), $row['page_name'], array( 'target' => '_blank' ) )
				);
			}
		}

		$this->getResult()->addValue( $this->getModuleName(), null, $data );

	}

	private function pageViews() {

		$data = array();

		$lowDate = new DateTime();
		$lowDate = $lowDate->sub( new DateInterval("P7D") );

		$items = wfGetDB(DB_SLAVE)->select(
			'metrica',
			'COUNT(*) as `count`, DATE_FORMAT(created_at_date, "%e %M") as `date`',
			array(
				'action' => 'view',
				'created_at >= '.$lowDate->getTimestamp()
			),
			__METHOD__,
			array(
				'GROUP BY' => 'DAY(created_at_date)'
			)
		);

		if( $items->numRows() ) {
			while( $row = $items->fetchRow() ) {
				$data[ $row['date'] ] = $row['count'];
			}
		}

		$labels = array_keys( $data );
		$values = array_values( $data );

		$data['labels'] = $labels;
		$data['values'] = $values;

		$this->getResult()->addValue( $this->getModuleName(), null, $data );

	}

	private function pageEdits() {

		$data = array();

		$lowDate = new DateTime();
		$lowDate = $lowDate->sub( new DateInterval("P7D") );

		$items = wfGetDB(DB_SLAVE)->select(
			'metrica',
			'COUNT(*) as `count`, DATE_FORMAT(created_at_date, "%e %M") as `date`',
			array(
				'action' => 'edit',
				'created_at >= '.$lowDate->getTimestamp()
			),
			__METHOD__,
			array(
				'GROUP BY' => 'DAY(created_at_date)'
			)
		);

		if( $items->numRows() ) {
			while( $row = $items->fetchRow() ) {
				$data[ $row['date'] ] = $row['count'];
			}
		}

		$labels = array_keys( $data );
		$values = array_values( $data );

		$data['labels'] = $labels;
		$data['values'] = $values;

		$this->getResult()->addValue( $this->getModuleName(), null, $data );

	}

	public function getAllowedParams( /* $flags = 0 */ ) {
		return array(
			'do' => array(
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_TYPE => 'string'
			)
		);
	}

}