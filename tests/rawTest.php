<?php
$starttime = microtime( true );
$file      = fsockopen( 'api.eazycdv.cloud', 443, $errno, $errstr, 10 );
$stoptime  = microtime( true );
$status    = 0;

if ( ! $file ) {
	$status = - 1;
}  // Site is down
else {
	fclose( $file );
	$status = ( $stoptime - $starttime ) * 1000;
	$status = floor( $status );
}
var_dump($status);