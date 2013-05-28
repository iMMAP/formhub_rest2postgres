<?php
use Immap\Database\PostgresSQL;
require 'immap/database/PostgresSQL.php';
require 'vendor/autoload.php';

$app = new \Slim\Slim();

$app->get('/', function () {
 
});

$app->post('/formhub-json', function () use ($app) {
          $request = $app->request();
          $body = $request->getBody();
		  $pg_class = new PostgresSQL();
	      $pg_class->db_connect();
          $pg_class->save($body);
		  $pg_class->db_close();  
        });
		
$app->run();
?>