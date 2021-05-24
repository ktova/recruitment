<?php

/*
 * Infomaniak Recruitement Exercise
 * Expand and output a structured and labelled tree from a submited object
 * Author : Teva Keo
 * Author URI : https://tova.dev
 */

include_once( 'Controller/ExpandController.php' );

header( "Access-Control-Allow-Origin: *" );
header( "Content-Type: application/json; charset=UTF-8" );
header( "Access-Control-Allow-Methods: POST" );
header( "Access-Control-Max-Age: 3600" );
header( "Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With" );

// Filter requests exclusively for /expand_validator endpoint
$uri = explode( '/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) );

// URI index is set to '3' for web demo purpose
if( $uri[3] !== 'expand_validator' ){
    header( "HTTP/1.1 404 Not Found" );
    echo(
        json_encode([
            'Error' => 'No endpoint submitted.'
        ])
    );
    exit();
}

// Set requested method and process it
$requestMethod = $_SERVER["REQUEST_METHOD"];
$controller = new ExpandController( $requestMethod );

// Handle POST Request
//$requestBody = json_encode(file_get_contents('php://input'),  JSON_FORCE_OBJECT);
$requestBody = file_get_contents( 'php://input' );
$controller->processRequest( $requestBody );

?>