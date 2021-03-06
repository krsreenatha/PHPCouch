<?php

namespace phpcouch\record;

class ViewResultRow extends Record implements ViewResultRowInterface
{
	const DEFAULT_ACCESSOR = null;
	
	protected $viewResult;
	
	public function __construct(ViewResultInterface $viewResult = null)
	{
		parent::__construct($viewResult->getDatabase()->getConnection());
		
		$this->viewResult = $viewResult;
	}
	
	public function getViewResult()
	{
		return $this->viewResult;
	}
	
	public function getDocument($accessor = null)
	{
		if($accessor === null) {
			$accessor = static::DEFAULT_ACCESSOR;
		}
		
		if($accessor === null) {
			// the value contains the document itself
			$doc = $this->value;
			if (!$doc) { // if the view didn't emit the actual doc as value but was called with include_docs=true
				$doc = $this->doc;
			}
		} elseif(is_callable($accessor)) {
			// an anonymous function or another kind of callback that will grab the value for us
			$doc = call_user_func($accessor, $this);
		} elseif(is_array($this->value) && isset($this->value[$accessor])) {
			// value is an array
			$doc = $this->value[$accessor];
		} elseif(isset($this->value->$accessor)) {
			// it's the name of a property
			$doc = $this->value->$accessor;
		} else {
			// exception
		}
		
		$retval = new Document($this->getViewResult()->getDatabase());
		$retval->hydrate($doc);
		
		return $retval;
	}
}

?>