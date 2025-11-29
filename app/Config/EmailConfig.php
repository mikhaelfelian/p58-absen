<?php
namespace Config;

class EmailConfig {
	
	public $provider;
	// public $provider = 'Google';
	// public $provider = 'AmazonSES';

	public $client;
	
	// Disesuaikan dengan konfigurasi username
	public $from;
	public $fromTitle;
	public $emailSupport;
	
	public function __construct() {
		// Read from .env file, fallback to defaults
		$this->provider = env('email.provider', 'Standard');
		
		$this->client = [
			'standard' => [
				'host' => env('email.smtp.host', 'berkahmitraabadi.com'),
				'username' => env('email.smtp.username', 'noreply@berkahmitraabadi.com'),
				'password' => env('email.smtp.password', 'Admin@123123'),
				'port' => env('email.smtp.port', 465),
				'secure' => env('email.smtp.secure', 'ssl')
			],
			'google' => [
				'client_id' => env('email.google.client_id', ''),
				'client_secret' => env('email.google.client_secret', ''),
				'refresh_token' => env('email.google.refresh_token', '')
			],
			'ses' => [
				'username' => env('email.ses.username', ''),
				'password' => env('email.ses.password', '')
			]
		];
		
		$this->from = env('email.from', 'noreply@berkahmitraabadi.com');
		$this->fromTitle = env('email.fromTitle', 'Berkah Mitra Abadi');
		$this->emailSupport = env('email.support', 'marketing@berkahmitraabadi.com');
	}
}