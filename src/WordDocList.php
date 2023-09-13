<?php
namespace Searchindex;

class DocWordList 
{
	protected $bytes;

	public function __construct ($bytes = null)
	{
		if (is_null($bytes)) {
			$this->init();
		} else {
			$this->bytes = $bytes;
		}
	}

	public function getBytes()
	{
		return $this->bytes;
	}

	protected function init()
	{
		$last_used = 0;
		$first_freed = 0;
		$count_freed = 0;
		$this->bytes = 'WLST' . pack('L4', $last_used, $first_freed, $count_freed, 0);
	}

	public function insert($doc_id) : int
	{
	}

	public function add($wlist_node, $doc_id) : void
	{
	}
}