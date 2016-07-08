<?php

/**
 * Class MetricaEntry
 *
 * @property string action
 * @property int user_id
 * @property string user_ip
 * @property int user_logged_in
 * @property string user_lang
 * @property string user_name
 * @property int page_id
 * @property int page_revision_id
 * @property int page_is_article
 * @property string page_name
 * @property string page_categories
 * @property int page_namespace_id
 * @property int page_is_main
 * @property int created_at
 * @property string|null created_at_date
 */
class MetricaEntry {

	/**
	 * @var array
	 */
	protected $fields = array(
		'action'            => '',
		'user_id'           => 0,
		'user_ip'           => '',
		'user_logged_in'    => 0,
		'user_lang'         => '',
		'user_name'         => '',
		'page_id'           => 0,
		'page_revision_id'  => 0,
		'page_is_article'   => 0,
		'page_name'         => '',
		'page_categories'   => '',
		'page_namespace_id' => 0,
		'page_is_main'      => 0,
		'created_at'        => 0
	);

	protected $id = null;

	protected static $db_slave;
	protected static $db_master;

	/**
	 * MetricaEntry constructor.
	 *
	 * @param array $fields
	 */
	public function __construct( $fields = null ) {
		if( $fields !== null && is_array($fields) ) {
			$this->fields = $fields;
		}
		self::$db_master = wfGetDB(DB_MASTER);
		self::$db_slave = wfGetDB(DB_SLAVE);
	}

	/**
	 * @param $name
	 * @param $value
	 *
	 * @return bool
	 */
	public function __set( $name, $value ) {
		if( array_key_exists( $name, $this->fields ) ) {
			$this->fields[ $name ] = $value;
		}else{
			// Bypass check for these fields
			if( $name == 'created_at' || $name == 'created_at_date' ) {
				$this->fields[ $name ] = $value;
			}
		}
		return false;
	}

	/**
	 * @param $name
	 *
	 * @return mixed|null
	 */
	public function __get( $name ) {
		if( array_key_exists( $name, $this->fields ) ) {
			return $this->fields[ $name ];
		}
		return null;
	}

	public function setId( $id )
	{
		$this->id = $id;
	}

	public static function newById( $id )
	{
		$entry = new self();
		$entry->loadEntry( $id );
		return $entry;
	}

	public static function query( $conditions = array() )
	{
		$items = array();
		$result = self::$db_slave->select( 'metrica', '*', $conditions );
		if( $result->numRows() ) {
			while( $row = $result->fetchRow() ) {
				$entry = new self();
				$entry->setId( $row['id'] );
				foreach ( $row as $fieldKey => $fieldValue ) {
					$entry->$fieldKey = $fieldValue;
				}
				$items[] = $entry;
			}
		}
		return $items;
	}

	public function save()
	{
		$this->saveEntry();
	}

	private function saveEntry()
	{
		if( $this->id ) {
			self::$db_master->update(
				'metrica',
				$this->fields,
				array(
					'id' => $this->id
				)
			);
		}else{
			self::$db_master->insert(
				'metrica',
				$this->fields
			);
			$this->id = self::$db_master->insertId();
		}
		return $this->id;
	}

	private function loadEntry( $id )
	{
		$row = self::$db_slave->selectRow( 'metrica', '*', array( 'id' => $id ) );
		if( $row ) {
			foreach ( $row as $fieldKey => $fieldValue ) {
				$this->$fieldKey = $fieldValue;
			}
			return true;
		}else{
			return false;
		}
	}

}