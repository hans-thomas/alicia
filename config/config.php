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
		| template folder
		|--------------------------------------------------------------------------
		|
		| the temporary folder is where files uploaded temporarily before
		| classification and optimization. false for disabling this feature
		|
		*/
		'temp'           => false,


		/*
		|--------------------------------------------------------------------------
		| classification
		|--------------------------------------------------------------------------
		|
		| the method to organization uploaded files
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
		| sized
		|--------------------------------------------------------------------------
		|
		| determine the allowed files size in MB
		|
		*/
		'sizes'          => [
			'image' => 5,
			'video' => 100,
			'audio' => 10,
			'file'  => 10
		],

		/*
		|--------------------------------------------------------------------------
		| validation
		|--------------------------------------------------------------------------
		|
		| add additional validation rule for uploading a file
		|
		*/
		'validation'     => [
			'image'    => [ 'file' ],
			'video'    => [ 'file' ],
			'file'     => [ 'file' ],
			'audio'    => [ 'file' ],
			'external' => [ 'url' ]
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
		| if you want to serve your files using Nginx server or anythings else
		| just enter the server's address
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
		| Only Published
		|--------------------------------------------------------------------------
		|
		| you can determine the Resources just return published files or not.
		| there is a PublishedOnlyScope that apply to the Resources Model
		|
		*/

		'onlyPublishedFiles' => true,

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
	];
