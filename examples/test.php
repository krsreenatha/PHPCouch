<?php

use phpcouch\Phpcouch;
use phpcouch\Exception;
use phpcouch\connection;
use phpcouch\adapter;

error_reporting(E_ALL | E_STRICT);

set_include_path(get_include_path() . ':' . '/Users/dzuelke/Downloads/ZendFramework-1.0.2/library');

require('../lib/phpcouch/Phpcouch.php');
Phpcouch::bootstrap();

PhpCouch::registerConnection('default', $con = new connection\Connection(null, new adapter\PhpAdapter()));

var_dump($con->listDatabases());
var_dump('A UUID (from /_uuids): ' . $con->retrieveUuids()->uuids[0]);
var_dump('Database dir (from /_config): ' . $con->retrieveConfig()->couchdb->database_dir);
var_dump('CouchDB Version (from /): ' . $con->retrieveInfo()->version);
var_dump('Mean request time (from /_stats): ' . $con->retrieveStats()->couchdb->request_time->mean);

var_dump($db = $con->retrieveDatabase('test_suite_db/with_slashes'));

// foreach($db->callView('test', 'testing', array('reduce' => false, 'stale' => true, /*'keys' => array('foo', 'bar')*/)) as $row) {
// 	var_dump($row);
// }

foreach($db->listDocuments(array('include_docs' => true)) as $row) {
	var_dump($row->getDocument()->_id);
}

var_dump($db->retrieveDocument('_design/test'));
// var_dump($db->retrieveDocument('_design/testx'));

$new = $db->newDocument();
$new->foo = 'bar';
try {
	$new->save();
	var_dump($new);
} catch(\phpcouch\http\HttpClientErrorException $e) {
	var_dump($e->getResponse());
}

$newdb = uniqid('foobar');
try {
	var_dump($con->createDatabase($newdb));
	var_dump("$newdb created!");
} catch(Exception $e) {
	var_dump($e->getResponse());
}
try {
	var_dump($con->deleteDatabase($newdb));
	var_dump("$newdb deleted!");
} catch(Exception $e) {
	var_dump($e->getResponse());
}

try {
	$doc = $db->retrieveDocument($new->_id);
	$doc->title = 'hello again';
	$doc->save();
} catch(Exception $e) {
}

$doc = $db->newDocument();
$doc->_id = uniqid();
$doc->type = 'Page';
$doc->title = 'Hello world again!';
$doc->content = 'Something more';
var_dump($doc, $doc->toArray(), $doc->dehydrate());

$doc->save();

$doc->title .= 'Snap';
$doc->save();

$doc = $db->newDocument();
$doc->type = 'Page';
$doc->title = 'An unnamed document';
$doc->content = 'Yay zomg! :>>';
try {
	$doc->save();
	var_dump($doc);
} catch(\phpcouch\http\HttpClientErrorException $e) {
	var_dump($e->getResponse());
}

?>