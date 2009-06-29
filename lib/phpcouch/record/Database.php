<?php

namespace phpcouch\record;

use phpcouch\Exception;

class Database extends Record
{
	public function __toString()
	{
		return $this->getName();
	}
	
	/**
	 * Get the name of this database.
	 *
	 * @return     string The database name.
	 *
	 * @author     David Zülke <david.zuelke@bitextender.com>
	 * @since      1.0.0
	 */
	public function getName()
	{
		return $this->db_name;
	}
	
	/**
	 * Get a list of all the documents in the database.
	 * 
	 * @param       PhpcouchIDocument The document to store.
	 *
	 * @return      stdClass list of all records
	 * 
	 * @throws      PhpcouchErrorException Yikes!
	 *
	 * @author      Simon Thulbourn
	 * @since       1.0.0
	 */
	public function listDocuments($allData = false)
	{
		$data = array();
		
		if($allData) {
			$data = array('include_docs' => 'true');
		}
		
		try {
			$docs = $this->getConnection()->getAdapter()->get($this->getConnection()->buildUri('_all_docs'));
			
			if($docs->total_rows == 0) {
				throw new Exception('No documents founds');
			}
			
			if($allData) {
				foreach($docs->rows as &$row) {
					$result = $this->getConnection()->getAdapter()->get($this->getConnection()->buildUri($row->id));
					
					if(isset($result->_id)) {
						$document = $this->newDocument();
						$document->hydrate($result);
						$row = $document;
					} else {
						throw new Exception('Something bad happened here, call the police');
					}
				}
			}
			
			return $docs;
		} catch(Exception $e) {
			throw new Exception($e->getMessage(), $e->getCode(), $e);
		}
	}
	
	/**
	 * Create a new document on the server.
	 *
	 * @param      PhpcouchIDocument The document to store.
	 *
	 * @throws     ?
	 *
	 * @author     David Zülke <david.zuelke@bitextender.com>
	 * @since      1.0.0
	 */
	public function createDocument(DocumentInterface $document)
	{
		$values = $document->dehydrate();
		
		if(isset($values['_id'])) {
			// there is an id? nice, but we don't need it, the URL is enough
			unset($values['_id']);
		}
		
		try {
			if($document->_id) {
				// create a named document
				$uri = $this->getConnection()->buildUri($document->_id);
				$result = $this->getConnection()->getAdapter()->put($uri, $values);
			} else {
				// let couchdb create an ID
				$uri = $this->getConnection()->buildUri();
				$result = $this->getConnection()->getAdapter()->post($uri, $values);
			}
			
			if(isset($result->ok) && $result->ok === true) {
				// all cool.
				$document->hydrate(array(Document::ID_FIELD => $result->id, Document::REVISION_FIELD => $result->rev));
				return;
			} else {
				throw new Exception('Result not OK :(');
				// TODO: add $result
			}
		} catch(Exception $e) {
			throw new Exception($e->getMessage(), $e->getCode(), $e);
			// TODO: add $result
		}
	}
	
	/**
	 * Retrieve a document from the database.
	 *
	 * @param      string The ID of the document.
	 *
	 * @return     PhpcouchIDocument A document instance.
	 *
	 * @throws     ?
	 *
	 * @author     David Zülke <david.zuelke@bitextender.com>
	 * @since      1.0.0
	 */
	public function retrieveDocument($id)
	{
		$uri = $this->getConnection()->buildUri($id);
		
		// TODO: grab and wrap exceptions
		$result = $this->getConnection()->getAdapter()->get($uri);
		
		if(isset($result->_id)) {
			$document = $this->newDocument();
			$document->hydrate($result);
			return $document;
		} else {
			// error
		}
	}
	
	/**
	 * Retrieve an attachment of a document.
	 *
	 * @param      string The name of the attachment.
	 * @param      string The document ID.
	 *
	 * @return     string The attachment contents.
	 *
	 * @throws     ?
	 *
	 * @author     David Zülke <david.zuelke@bitextender.com>
	 * @since      1.0.0
	 */
	public function retrieveAttachment($name, $id)
	{
		// TODO: this doesn't work atm
		if($id instanceof DocumentInterface) {
			$id = $id->_id;
		}
		
		$uri = $this->getConnection()->buildUri($id, array('attachment' => $name));
		
		return $this->getConnection()->getAdapter()->get($uri);
	}
	
	/**
	 * Save a modified document to the database.
	 *
	 * @param      PhpcouchIDocument The document to save.
	 *
	 * @throws     ?
	 *
	 * @author     David Zülke <david.zuelke@bitextender.com>
	 * @since      1.0.0
	 */
	public function updateDocument(DocumentInterface $document)
	{
		$values = $document->dehydrate();
		
		$uri = $this->getConnection()->buildUri($document->_id);
		
		$result = $this->getConnection()->getAdapter()->put($uri, $values);
		
		if(isset($result->ok) && $result->ok === true) {
			$document->_rev = $result->rev;
		} else {
			// error
		}
	}
	
	/**
	 * Delete a document.
	 *
	 * @param      PhpcouchDocument The name of the document to delete.
	 *
	 * @return     PhpcouchIDocument The deletion stub document.
	 *
	 * @throws     ?
	 *
	 * @author     David Zülke <david.zuelke@bitextender.com>
	 * @author     Simon Thulbourn <simon.thulbourn@bitextender.com>
	 * @since      1.0.0
	 */
	public function deleteDocument(DocumentInterface $doc)
	{
		if($doc instanceof DocumentInterface) {
			$headers = array('If-Match' => $doc->_rev);
			$id = $doc->_id;
		} else {
			throw new Exception('Parameter supplied is not of type PhpcouchDocument');
		}
		
		$uri = $this->getConnection()->buildUri($id);
		return $this->getConnection()->getAdapter()->delete($uri, $headers);
	}
	
	/**
	 * Make a new document instance with this connection set on it.
	 *
	 * @return     PhpcouchIDocument An empty document.
	 *
	 * @author     David Zülke <david.zuelke@bitextender.com>
	 * @since      1.0.0
	 */
	public function newDocument()
	{
		return new Document($this);
	}
	
	public function executeView($designDocument, $viewName, $viewResultClass = null)
	{
		if($designDocument instanceof DocumentInterface) {
			$designDocument = str_replace('_design/', '', $designDocument->getId());
		}
		
		if($viewResultClass === null) {
			$viewResultClass = '\phpcouch\record\ViewResult';
		}
		$viewResult = new ViewResult($this);
		$viewResult->hydrate($this->getConnection()->getAdapter()->get($this->getConnection()->baseUrl . $this->getName() . '/_design/' . $designDocument . '/_view/' . $viewName));
		
		return $viewResult;
	}
}

?>