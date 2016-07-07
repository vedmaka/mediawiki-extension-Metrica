<?php

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

class MetricaFakeDataGenerator extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( "\n" ."Generates fake data sets for Metrica database. \n" );
		$this->addDefaultParams();
	}

	protected function addDefaultParams() {

		$this->addOption( 'clean', 'truncates Metrica tables', false, false, 'c' );

	}

	public function execute() {

		$this->output( "\nStarting maintenance script" );

		$db = wfGetDB(DB_MASTER);

		if( $this->hasOption('clean')) {

			$db->delete('metrica', array('id > 0'));
			$this->output("\nTruncating table..");

		}else{

			$this->output("\nGenerating data..");

			$days = 14;
			$perDayMax = 100;
			$perDayMin = 100;
			for( $day = 0; $day < $days; $day++ ) {

				// Convert day to unix & timestamp
				$date = new DateTime();
				if( $day > 0 ) {
					$date = $date->sub( new DateInterval( 'P' . $day . 'D' ) );
				}

				// Randomize number of records per day
				mt_srand();
				$items = mt_rand( $perDayMin, $perDayMax );

				$this->output("\nProcessing day ".$day);

				for( $item = 0; $item < $items; $item++ ) {

					$entry = new MetricaEntry();

					$entry->action              = $this->randomize('view', 'edit');
					$entry->user_id             = mt_rand(0, 100);
					$entry->user_ip             = '' . mt_rand(100,200) .'.'. mt_rand(100,200) .'.' .mt_rand(100,200) .'.'. mt_rand(100,200);
					$entry->user_logged_in      = $this->randomize( 0, 1 );
					$entry->user_lang           = $this->randomize('ru', 'en');
					$entry->user_name           = $this->randomize('', 'Admin');
					$entry->page_id             = $this->randomize(1001, 1002);
					$entry->page_revision_id    = mt_rand(1000,3000);
					$entry->page_is_article     = $this->randomize(0, 1);
					$entry->page_name           = $this->randomize('Page 1', 'Page 2');
					$entry->page_categories     = $this->randomize('', 'Category 1;Category 2');
					$entry->page_namespace_id   = 0;
					$entry->page_is_main        = 0;
					$entry->created_at          = $date->getTimestamp();
					$entry->created_at_date     = $date->format("Y-m-d H:i:s");

					$entry->save();

				}
			}
		}

		$this->output("\nDone.");

		return true;

	}

	private function randomize( $a, $b ) {
		mt_srand();
		if( mt_rand(0, 1) > 0 ) {
			return $a;
		}
		return $b;
	}

}

$maintClass = 'MetricaFakeDataGenerator';
require_once ( RUN_MAINTENANCE_IF_MAIN );