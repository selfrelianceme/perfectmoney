<?php
return [
	/**
	 * The account id in perfect money
	 */
	'account_id'    => env('PM_ACCOUNT_ID', 'account_id'),
	/**
	 * Password for account
	 */
	'account_pass'  => env('PM_ACCOUNT_PASS', 'account_password'),
	/**
	 * Account for pay
	 */
	'payee_account' => env('PM_PAYEE_ACCOUNT', 'payee_account'),
	/**
	 * Alt solt for api
	 */
	'alt'          => env('PM_ALT', 'alt'),
	/**
	 * Name acount to show pay
	 */
	'account_name' => env('PM_ACCOUNT_NAME', 'account_name'),
	/**
	 * Afte pay to page redirect
	 */
	"to_account" => env('PERSONAL_LINK_CAB', '/personal')
];