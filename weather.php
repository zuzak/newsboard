<?php

header('Access-Control-Allow-Origin: *');
header( 'Content-Type: application/json' );
$config = parse_ini_file( 'config.cfg', true );
if ( date('w') == 0 || date('w') == 6) {
	die('{"forecast":["Forecast back on Monday"]}');
}
if ( date('H') < 6 || date('H') >= 19 ) {
	die('{"forecast":["","Forecast back at 0600 UTC"]}');
}

function get( $url ) {
	$cacheFile = 'cache' . DIRECTORY_SEPARATOR . md5( $url );
	if ( file_exists( $cacheFile ) ) {
		$fh = fopen( $cacheFile, 'r' );
		$cacheTime = trim( fgets( $fh ) );

		if ( $cacheTime > strtotime( '-59 seconds' )) {
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
	$status = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
	if ( $status == 403 ) {
		die( '{"forecast": ["Rate-limited", "Cannot connect to weather information."]}' );
	} elseif ( $status !== 200 ) {
		die( '{"forecast": ["Error '. $status . '", "Problem with weather information", "<img src=\"http://thecatapi.com/api/images/get?format=src&results_per_page=1&foo=' . uniqid() . '\" />"]}' );
	}
	curl_close( $ch );

	$fh = fopen( $cacheFile, 'w+' );
	fwrite( $fh, time() . "\n" );
	fwrite( $fh, $result );
	fclose( $fh );

	return $result;
}

$API_KEY = $config['weather']['api'];
$LAT = $config['weather']['latitude'];
$LONG = $config['weather']['longitude'];

$ENDPOINT = "https://api.darksky.net/forecast/$API_KEY/$LAT,$LONG?units=uk2";

$data = json_decode( get( $ENDPOINT ) );
$result = array(
	'forecast' => array(
		$data->{'currently'}->{'summary'},
		parse_data( $data->{'currently'} ),
		# constructPrecipitationString( $data->{'minutely'}->{'data'}[0], 'next minute (maybe?)' ),
		# constructPrecipitationString( $data->{'hourly'}->{'data'}['0'], 'next hour' ),
		'&nbsp;',
		$data->{'minutely'}->{'summary'},
		$data->{'hourly'}->{'summary'},
		$data->{'daily'}->{'summary'},
		parse_warnings( $data )
	),
	'current' => array(
		'summary' => $data->{'currently'}->{'summary'},
		'wind' => array(
			'gust' => $data->{'currently'}->{'windGust'},
			'mph' =>  $data->{'currently'}->{'windSpeed'},
			'beaufort' =>  mph2beaufort( $data->{'currently'}->{'windSpeed'} )
		)
	),
	'rainchance' => parseRainChance( $data->{'minutely'}, 'precipProbability' ),
	'rainintensity' => parseRainChance2( $data->{'minutely'}, 'precipIntensity' ),
	'co2' => get( 'http://www.hqcasanova.com/co2/' )
);

echo( json_encode( $result ) );

function parse_warnings( $data ) {
	$ret = array();
	if ( $data->{'alerts'} ) {
		foreach ( $data->{'alerts'} as $alert ) {
			$ret[] = $alert->{'title'};
		}
		return '⚠ ' . implode( array_unique( $ret ), ' and ' );
	}
	return null;
}

function parseRainChance( $data, $field ) {
	$result = array();
	foreach ( $data->{'data'} as $datum ) {
		array_push( $result, $datum->{$field} );
	}
	return $result;
}

function parseRainChance2( $data, $field ) {
	$result = array();
	foreach ( $data->{'data'} as $datum ) {
		array_push( $result, $datum->{$field} / 5 );
	}
	return $result;
}


function constructPrecipitationString($data, $append) {
	if ( $data->{'precipType'}) {
		$val =  $data->{'precipProbability'} * 100;
		return $val .	'% chance of ' . $data->{'precipType'} . ' ' . $append;
	}
	return '';
}

function convert_windspeed( $wind, $gust ) {
	$windforce = intval( mph2beaufort( $wind ) );
	$gustforce = intval( mph2beaufort( $gust ) );
	return str_repeat( '<small>●</small>', $windforce ) . str_repeat( '<small>○</small>', $gustforce - $windforce );
}


function parse_data( $data ) {
	$ret = array();
	$ret[] = '';
	$ret[] = constructPrecipitationString( $data, 'right now' );
	if ($data->{'precipIntensity'} !== 0) {
		/*$val = round( $data->{'precipIntensity'} );
		if ($val == 0) {
			$val = '<1';
		}*/
		$val = $data->{'precipIntensity'};
		$ret[] = $val .	'<style="font-size: 0.5em !important">mm/h</span>';
	}
	$ret[] = '';
	$ret[] = $data->{'temperature'} . '°C ' . convert_windspeed( $data->{'windSpeed'}, $data->{'windGust'} ) . '<span style="font-size: 50%; margin-left: 1em;">(' . mph2humanbeaufort( mph2beaufort( $data->{'windSpeed'} ) ) . ')</span>';

	$delta = $data->{'temperature'} - $data->{'apparentTemperature'};
	if ( $delta == 0 ) {
	} else {
		if ( $delta < 0 ) {
			$diff = abs($delta) . '°C warmer';
		} else {
			$diff = abs($delta) . '°C cooler';
		}
	}
	/*
	if ($diff) {
		$ret[] = '(feels ' . $diff . ')';
	}
	 */
	/*$ret[] = round($data->{'temperature'}) . '°C ' . str_repeat( '•', mph2beaufort( $data->{'windSpeed'} ));
	if (round($data->{'temperature'}) !== round($data->{'apparentTemperature'})) {
		$ret[] = 'feels like ' . $data->{'apparentTemperature'} . '°C';
	}*/

	$ret[] = ( $data->{'cloudCover'}*100 ) . '% cloud cover (vis. ' . $data->{'visibility'} . 'mi)';

	//$ret[] = shippingForecastString($data);
	return $ret;
}

function vis2word( $visibility ) {
	if ($visibility > 5.8 ) return 'good';
	if ($visibility > 2.3 ) return 'moderate';
	if ($visibility > 0.6 ) return 'poor';
	return 'fog';
}

function shippingForecastString( $data ) {
	$str = array('Sheffield. ');

	$str[] =  compassBearing($data->{'windBearing'}) . ' ';

	$windSpeed = sfBeaufort( $data->{'windSpeed'} );
	$windGust= sfBeaufort( $data->{'windGust'} );
	$str[] = $windSpeed;

	if ( $windSpeed !== $windGust ) {
		$str[] = ' to ' . $windGust;
	}

	$str[] = ', ';
	if ( $data->{'precipIntensity'} !== 0) {
		$str[] = 'rain, ';
	}
	$str[] = vis2word($data->{'visibility'}) . '.';

	return implode($str, '');
}

function sfBeaufort($mph) {
	$beaufort = mph2beaufort( $mph );
	if ($beaufort > 5 ) {
		return mph2humanbeaufort( $beaufort );
	}
	return $beaufort;
}

function compassBearing( $deg ) {
	$points = array( 'Northeasterly', 'Easterly', 'Southeasterly', 'Southerly', 'Southwesterly', 'Westerly', 'Northwesterly' );
	if ($deg > 337.5 || $deg <= 22.5 ) {
		return 'Northerly';
	}
	return $points[floor((($deg-22.5)/45))];
}

function mph2beaufort( $mph ) {
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

function mph2humanbeaufort( $force ) {
	$forces =array(
		'calm',
		'light air',
		'light breeze',
		'gentle breeze',
		'moderate breeze',
		'fresh breeze',
		'strong breeze',
		'near gale',
		'strong gale',
		'storm',
		'violent storm',
		'hurricane'
	);
	return $forces[$force]; //. ' ' . str_repeat( '•', $force );
}
