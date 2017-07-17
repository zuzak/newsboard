<?php

function get( $url ) {
	$cacheFile = 'cache' . DIRECTORY_SEPARATOR . md5( $url );
	if ( file_exists( $cacheFile ) ) {
		$fh = fopen( $cacheFile, 'r' );
		$cacheTime = trim( fgets( $fh ) );

		if ( $cacheTime > strtotime('-2 minutes')) {
			return fread( $fh, filesize( $cacheFile ) );
		}
		fclose( $fh );
		unlink( $cacheFile );
	}

	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_HEADER, 0 );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

	$result = curl_exec( $ch );
	curl_close( $ch );

	$fh = fopen( $cacheFile, 'w+' );
	fwrite( $fh, time() . "\n" );
	fwrite( $fh, $result );
	fclose( $fh );

	return $result;
}

$API_KEY = 'd45935d83be44f1a9709f44c62f6e763';
$LAT = 53.3868;
$LONG = -1.4661;
$ENDPOINT = "https://api.darksky.net/forecast/$API_KEY/$LAT,$LONG?units=uk2";



header( 'Content-Type: application/json' );
$data = json_decode( get( $ENDPOINT ) );
$result = array(
	'forecast' => array(
		$data->{'minutely'}->{'summary'},
		$data->{'hourly'}->{'summary'},
	),
	'current' => array(
		'summary' => $data->{'currently'}->{'summary'},
		'wind' => array(
			'gust' => $data->{'currently'}->{'windGust'},
			'mph' =>  $data->{'currently'}->{'windSpeed'},
			'beaufort' =>  mph2beaufort( $data->{'currently'}->{'windSpeed'} )
		)
	)
);

echo( json_encode( $result ));

function mph2beaufort($mph) {
	if ( $mph <   1 ) return 0;
	if ( $mph <=  3 ) return 1;
	if ( $mph <=  7 ) return 2;
	if ( $mph <= 12 ) return 3;
	if ( $mph <= 18 ) return 4;
	if ( $mph <= 24 ) return 5;
	if ( $mph <= 31 ) return 6;
	if ( $mph <= 38 ) return 7;
	if ( $mph <= 46 ) return 8;
	if ( $mph <= 54 ) return 9;
	if ( $mph <= 63 ) return 10;
	if ( $mph <= 72 ) return 11;
	return 12;
}
