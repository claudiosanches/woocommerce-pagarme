<?php
/**
 * Pagar.me API Auxiliary file to setup company temporary
 *
 * @package bin/bash
 */

/**
 * Creates and retrieve data from company temporary route
 *
 * @return stdClass
 * @throws Exception Emits Exception in case of an error in Datetime.
 */
function get_company_temporary() {
	$ch = curl_init();
	curl_setopt(
		$ch,
		CURLOPT_URL,
		'https://api.pagar.me/1/companies/temporary'
	);
	$date   = new DateTime( 'now', new DateTimeZone( 'America/New_York' ) );
	$params = sprintf(
		'name=acceptance_test_company&email=%s@woocoomercesuite.com&password=password',
		$date->format( 'YmdHis' )
	);
	curl_setopt( $ch, CURLOPT_POST, 1 );
	curl_setopt(
		$ch,
		CURLOPT_POSTFIELDS,
		$params
	);
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	$result       = curl_exec( $ch );
	$company_data = json_decode( $result );
	curl_close( $ch );

	return $company_data;
}

$file_name      = '.env.local';
$api_key        = getenv( 'API_KEY' );
$encryption_key = getenv( 'ENCRYPTION_KEY' );
$env_file       = file_exists( $file_name ) ? file_get_contents( $file_name ) : '';

if ( ( ! $api_key && ! $encryption_key ) && strpos( $env_file, 'API_KEY=' ) === false ) {
	$company_data   = get_company_temporary();
	$api_key        = $company_data->api_key->test;
	$encryption_key = $company_data->encryption_key->test;

	echo "Create file .env.local\n";
	$command = sprintf(
		"API_KEY=%s\nENCRYPTION_KEY=%s\nCYPRESS_API_KEY=%s\nCYPRESS_ENCRYPTION_KEY=%s",
		$api_key,
		$encryption_key,
		$api_key,
		$encryption_key
	);
	shell_exec( "echo $'{$command}' > .env.local" );
}
