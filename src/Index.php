<?php
namespace Searchindex;

class Index 
{
	protected $filename;
	protected $words;
	protected $wordDocs;
	protected $docs;
	protected $docWords;
	
	public function __construct ($filename)
	{
		$this->filename = $filename;
		if (file_exists($filename)) {
			$this->load();
		} else {
			$this->init();
		}
	}

	protected function init()
	{
		$this->words = new WordIndex();
		$this->wordDocs = new WordDocList();
		$this->docs = new DocIndex();
		$this->docWords = new DocWordList();
	}

	protected function load()
	{
		$bytes = file_get_contents($this->filename);
		$lengths = unpack('L3', substr($bytes, 4, 3 * 4));
		$start = 16;
		$this->words = new WordIndex(substr($bytes, $start, $lengths[0]));
		$start += $lengths[0];
		$this->wordDocs = new WordDocList(substr($bytes, $start, $lengths[1]));
		$start += $lengths[1];
		$this->docs = new DocIndex(substr($bytes, $start, $lengths[2]));
		$start += $lengths[2];
		$this->words = new DocWordList(substr($bytes, $start));
	}

	public function getBytes()
	{
		$bytes = 'INDX';
		$bytes .= pack('L3', strlen($this->words->getBytes()), strlen($this->wordDocs->getBytes()), strlen($this->docs->getBytes()));
		$bytes .= $this->words->getBytes() . $this->wordDocs->getBytes() . $this->docs->getBytes() . $this->docWords->getBytes();
		return $bytes;
	}
	protected function save()
	{
		if(!empty($this->filename)) {
			file_put_contents($this->filename, $this->getBytes());
		} else {
			return $this->getBytes();
		}
	}

	protected function insertWord($doc_id, $word)
	{
		$result = $this->words->find($word);
		if (!$result[0]) {
			$result = $this->words->insert($word, $result[1], $result[2]);
		}
		assert($result[0] === true, 'A node must be found or inserted');
		$word_node = end($result[1]);
		
		$wlist_node = $this->words->getListNode($word_node);
		if ($wlist_node) {
			$this->wordDocs->add($wlist_node, $doc_id);
		} else {
			$wlist_node = $this->wordDocs->insert($doc_id);
			$this->words->setListNode($word_node, $wlist_node);
		}
		
		$dlist_node = $this->docs->getListNode($doc_id);
		if ($dlist_node) {
			$this->docWords->add($dlist_node, $word_node);
		} else {
			$dlist_node = $this->docWords->insert($word_node);
			$this->docs->setListNode($doc_id, $dlist_node);
		}
	}
}