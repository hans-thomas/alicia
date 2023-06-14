<?php

	use Illuminate\Support\Carbon;
	use Illuminate\Support\Str;

	return [

		/*
		|--------------------------------------------------------------------------
		| base folder
		|--------------------------------------------------------------------------
		|
		| the base folder is where Resources upload and classification files
		|
		*/
		'base'           => base_path( 'vault' ),

		/*
		|--------------------------------------------------------------------------
		| classification
		|--------------------------------------------------------------------------
		|
		| the method of organization uploaded files
		|
		*/
		'classification' => Str::slug( Carbon::now()->toFormattedDateString() ),


		/*
		|--------------------------------------------------------------------------
		| extensions
		|--------------------------------------------------------------------------
		|
		| determine the allowed extensions
		|
		*/
		'extensions'     => [
			'images' => [ 'jpg', 'jpeg', 'png' ],
			'videos' => [ 'mp4', 'mkv' ],
			'audios' => [ 'mp3' ],
			'files'  => [ 'zip', 'rar', 'pdf', 'csv', 'xlsx', 'docx' ]
		],

		/*
		|--------------------------------------------------------------------------
		| optimization
		|--------------------------------------------------------------------------
		|
		| images and videos optimization can enable/disable independently
		|
		*/
		'optimization'   => [
			'images' => true,
			'videos' => true,
		],

		/*
		|--------------------------------------------------------------------------
		| Naming files
		|--------------------------------------------------------------------------
		|
		| determine how to generate files name.
		|
		| supported drivers: [ 'uuid', 'string', 'digits', 'string_digits', 'symbols', 'hash' ]
		|
		*/
		'naming'         => 'uuid',

		/*
		|--------------------------------------------------------------------------
		| Download link
		|--------------------------------------------------------------------------
		|
		| if you want to customize the download url, you can update this without
		| manipulating parameters
		|
		*/
		'link'           => '/download/{resource}/{hash?}', // dont add or remove parameters

		/*
        |--------------------------------------------------------------------------
        | Signing download link
        |--------------------------------------------------------------------------
        |
        | if you want a simple and permanent link just set signed key to false,
        | however if you want to protect your links leave it true
        |
        */
		'signed'         => true,

		/*
		|--------------------------------------------------------------------------
		| Secret key
		|--------------------------------------------------------------------------
		|
		| the secret key that Signature class encode and decode hashes
		|
		*/
		'secret'         => 'resource_key',

		/*
		|--------------------------------------------------------------------------
		| Link Expiration
		|--------------------------------------------------------------------------
		|
		| determine the expiration time of each link in minutes
		|
		*/
		'expiration'     => 30,

		/*
		|--------------------------------------------------------------------------
		| Route attributes
		|--------------------------------------------------------------------------
		|
		| you can define your custom attributes, like prefix etc.
		|
		*/
		'attributes'     => [
			'middleware' => [ 'api' ]
		],

		/*
		|--------------------------------------------------------------------------
		| HLS settings
		|--------------------------------------------------------------------------
		|
		| some parameter to customize HLS export
		|
		*/
		'hls'                => [
			'enable'              => true,
			'bitrate'             => [ 250, 500, 1000 ],
			'setSegmentLength'    => 10,
			'setKeyFrameInterval' => 48,
		],

		/*
		|--------------------------------------------------------------------------
		| Image exports settings
		|--------------------------------------------------------------------------
		|
		| define your resolutions in [ $height => $width ] format,
		| you can set false for disabling this feature
		|
		*/
		'export'             => [
			960  => 540,
			1280 => 720,
			1920 => 1080,
		],
	];
