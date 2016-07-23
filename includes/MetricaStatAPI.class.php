<?php


class MetricaStatAPI extends ApiBase {
	
	private $params;

	public function execute() {

		if( !$this->getUser()->isAllowed('metrica') ) {
			$this->dieUsage('Not enough permissions', 'no_permissions');
		}

		$this->params = $this->extractRequestParams();
		switch ($this->params['do']) {
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
			case 'contribution_scores':
				$this->contributionScores();
				break;
		}

	}

	private function contributionScores() {

		$data = array();

		// imported from ContributionScores ----------------------------------------------------------------------------
		$dbr = wfGetDB( DB_SLAVE );
		$userTable = $dbr->tableName( 'user' );
		$userGroupTable = $dbr->tableName( 'user_groups' );
		$revTable = $dbr->tableName( 'revision' );
		$ipBlocksTable = $dbr->tableName( 'ipblocks' );
		$sqlWhere = "";
		$nextPrefix = "WHERE";

		$date = time() - ( 60 * 60 * 24 * 7 );
		$dateString = $dbr->timestamp( $date );
		$sqlWhere .= " {$nextPrefix} rev_timestamp > '$dateString'";
		$nextPrefix = "AND";

		$sqlWhere .= " {$nextPrefix} rev_user NOT IN (SELECT ipb_user FROM {$ipBlocksTable} WHERE ipb_user <> 0)";
		$nextPrefix = "AND";
		$sqlWhere .= " {$nextPrefix} rev_user NOT IN (SELECT ug_user FROM {$userGroupTable} WHERE ug_group='bot')";

		$sqlMostPages = "SELECT rev_user,
						 COUNT(DISTINCT rev_page) AS page_count,
						 COUNT(rev_id) AS rev_count
						 FROM {$revTable}
						 {$sqlWhere}
						 GROUP BY rev_user
						 ORDER BY page_count DESC
						 LIMIT 30";

		$sqlMostRevs  = "SELECT rev_user,
						 COUNT(DISTINCT rev_page) AS page_count,
						 COUNT(rev_id) AS rev_count
						 FROM {$revTable}
						 {$sqlWhere}
						 GROUP BY rev_user
						 ORDER BY rev_count DESC
						 LIMIT 30";

		$sql = "SELECT user_id, " .
		       "user_name, " .
		       "user_real_name, " .
		       "page_count, " .
		       "rev_count, " .
		       "page_count+SQRT(rev_count-page_count)*2 AS wiki_rank " .
		       "FROM $userTable u JOIN (($sqlMostPages) UNION ($sqlMostRevs)) s ON (user_id=rev_user) " .
		       "ORDER BY wiki_rank DESC " .
		       "LIMIT 30";

		$res = $dbr->query( $sql );
		$altrow = '';
		$user_rank = 1;
		$lang = $this->getLanguage();

		while ( $row = $res->fetchObject() ) {

			$rank = $lang->formatNum( round( $user_rank, 0 ) );
			$score = $lang->formatNum( round( $row->wiki_rank, 0 ) );
			$pages = $lang->formatNum( $row->page_count );
			$changes = $lang->formatNum( $row->rev_count );

			$data[] = array(
				'user_id' => $row->user_id,
				'user_name' => $row->user_real_name ? $row->user_real_name : $row->user_name,
				'rank' => $rank,
				'score' => $score,
				'pages' => $pages,
				'changes' => $changes
			);

			$user_rank++;

		}

		$dbr->freeResult( $res );
		// imported from ContributionScores ----------------------------------------------------------------------------

		$this->getResult()->addValue( $this->getModuleName(), null, $data );

	}

	private function pageMostViewed() {

		$data = array();
		
		$conditions = array(
			'action' => 'view'
		);
		
		if( $this->params['start'] ) {
			$conditions[] = 'created_at >= '.$this->params['start'];
		}
		
		if( $this->params['end'] ) {
			$conditions[] = 'created_at <= '.$this->params['end'];
		}

		if( $this->params['exclude'] ) {
			$conditions[] = 'user_id NOT IN( SELECT ug_user FROM '.wfGetDB(DB_SLAVE)->tablePrefix().'user_groups ug WHERE ug.ug_group = "sysop" )';
		}

		$items = wfGetDB(DB_SLAVE)->select(
			'metrica',
			'page_id, page_categories, page_name, COUNT(*) as `count`',
			$conditions,
			__METHOD__,
			array(
				'GROUP BY' => 'page_id',
				'ORDER BY' => '`count` DESC'
			)
		);

		if( $items->numRows() ) {
			while( $row = $items->fetchRow() ) {

				$pageCategories = $row['page_categories'];
				if( $pageCategories ) {
					$pageCategories = explode(';', $pageCategories);
				}else{
					$pageCategories = array('Uncategorized');
				}

				foreach ($pageCategories as $cat) {
					$data[$cat][] = array(
						'page_id'   => $row['page_id'],
						'views'     => $row['count'],
						'page_name' => $row['page_name'],
						'link'      => Linker::link( Title::newFromID( $row['page_id'] ), $row['page_name'], array( 'target' => '_blank' ) )
					);
				}
			}
		}

		$this->getResult()->addValue( $this->getModuleName(), null, $data );

	}

	private function pageMostEdited() {

		$data = array();
		
		$conditions = array(
			'action' => 'edit'
		);
		
		if( $this->params['start'] ) {
			$conditions[] = 'created_at >= '.$this->params['start'];
		}
		
		if( $this->params['end'] ) {
			$conditions[] = 'created_at <= '.$this->params['end'];
		}

		if( $this->params['exclude'] ) {
			$conditions[] = 'user_id NOT IN( SELECT ug_user FROM '.wfGetDB(DB_SLAVE)->tablePrefix().'user_groups ug WHERE ug.ug_group = "sysop" )';
		}

		$items = wfGetDB(DB_SLAVE)->select(
			'metrica',
			'page_id, page_categories, page_name, COUNT(*) as `count`',
			$conditions,
			__METHOD__,
			array(
				'GROUP BY' => 'page_id',
				'ORDER BY' => '`count` DESC'
			)
		);

		if( $items->numRows() ) {
			while( $row = $items->fetchRow() ) {

				$pageCategories = $row['page_categories'];
				if( $pageCategories ) {
					$pageCategories = explode(';', $pageCategories);
				}else{
					$pageCategories = array('Uncategorized');
				}

				foreach ($pageCategories as $cat) {
					$data[$cat][] = array(
						'page_id'   => $row['page_id'],
						'edits'     => $row['count'],
						'page_name' => $row['page_name'],
						'categories'=> $row['page_categories'],
						'link'      => Linker::link( Title::newFromID( $row['page_id'] ), $row['page_name'], array( 'target' => '_blank' ) )
					);
				}
			}
		}

		$this->getResult()->addValue( $this->getModuleName(), null, $data );

	}

	private function pageViews() {

		$data = array();
		
		$conditions = array(
			'action' => 'view'
		);

		if( $this->params['start'] || $this->params['end'] ) {
			
			if( $this->params['start'] ) {
				$conditions[] = 'created_at >= '.$this->params['start'];
			}
			
			if( $this->params['end'] ) {
				$conditions[] = 'created_at <= '.$this->params['end'];
			}
			
		}else{
			
			$lowDate = new DateTime();
			$lowDate = $lowDate->sub( new DateInterval( "P7D" ) );
			$conditions[] = 'created_at >= '.$lowDate->getTimestamp();
		
		}

		if( $this->params['exclude'] ) {
			$conditions[] = 'user_id NOT IN( SELECT ug_user FROM '.wfGetDB(DB_SLAVE)->tablePrefix().'user_groups ug WHERE ug.ug_group = "sysop" )';
		}

		$items = wfGetDB(DB_SLAVE)->select(
			'metrica',
			'COUNT(*) as `count`, DATE_FORMAT(created_at_date, "%e %M") as `date`',
			$conditions,
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
		
		$conditions = array(
			'action' => 'edit'
		);

		if( $this->params['start'] || $this->params['end'] ) {
			
			if( $this->params['start'] ) {
				$conditions[] = 'created_at >= '.$this->params['start'];
			}
			
			if( $this->params['end'] ) {
				$conditions[] = 'created_at <= '.$this->params['end'];
			}
			
		}else{
			
			$lowDate = new DateTime();
			$lowDate = $lowDate->sub( new DateInterval( "P7D" ) );
			$conditions[] = 'created_at >= '.$lowDate->getTimestamp();
		
		}

		if( $this->params['exclude'] ) {
			$conditions[] = 'user_id NOT IN( SELECT ug_user FROM '.wfGetDB(DB_SLAVE)->tablePrefix().'user_groups ug WHERE ug.ug_group = "sysop" )';
		}

		$items = wfGetDB(DB_SLAVE)->select(
			'metrica',
			'COUNT(*) as `count`, DATE_FORMAT(created_at_date, "%e %M") as `date`',
			$conditions,
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
			),
			'start' => array(
				ApiBase::PARAM_REQUIRED => false,
				ApiBase::PARAM_TYPE => 'integer'
			),
			'end' => array(
				ApiBase::PARAM_REQUIRED => false,
				ApiBase::PARAM_TYPE => 'integer'
			),
			'exclude' => array(
				ApiBase::PARAM_REQUIRED => false,
				ApiBase::PARAM_TYPE => 'string'
			)
		);
	}

}