<?php
namespace Searchindex;

class DocIndex
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
        $this->bytes = 'DOCS';
    }

    public function setListNode($doc_id, $dlist_node) : void
    {
    }

    public function getListNode($doc_id) : int
    {
        return 0;
    }
}