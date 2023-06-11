<?php

	use Illuminate\Contracts\Filesystem\Filesystem;
	use Illuminate\Support\Facades\Storage;
	use Illuminate\Support\Str;

	if ( ! function_exists( 'alicia_config' ) ) {
		/**
		 * Return alicia config
		 *
		 * @param string     $key
		 * @param mixed|null $default
		 *
		 * @return mixed
		 */
		function alicia_config( string $key, mixed $default = null ): mixed {
			return config( "alicia.{$key}", $default );
		}
	}

	if ( ! function_exists( 'alicia_storage' ) ) {
		/**
		 * Return alicia storage
		 *
		 * @return Filesystem
		 */
		function alicia_storage(): Filesystem {
			return Storage::disk( 'resources' );
		}
	}

	if ( ! function_exists( 'generate_file_name' ) ) {
		/**
		 * Generate random file name
		 *
		 * @param string|null $driver
		 * @param int         $length
		 *
		 * @return string
		 */
		function generate_file_name( string $driver = null, int $length = 16 ): string {
			return match ( $driver ? : alicia_config( 'naming' ) ) {
				'string' => Str::random( $length ),
				'digits' => substr( str_shuffle( '012345678901234567890123456789' ), 0, $length ),
				'string_digits' => substr(
					str_shuffle( '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' ),
					0,
					$length
				),
				'symbols' => substr( str_shuffle( '!@#$%^&*(){}><?~' ), 0, $length ),
				'hash' => substr( bcrypt( time() ), 0, $length ),
				default => Str::uuid()->toString(),
			};
		}
	}

	if ( ! function_exists( 'get_classified_folder' ) ) {
		/**
		 * Return classified folder name
		 *
		 * @return string
		 */
		function get_classified_folder(): string {
			if ( ! alicia_storage()->exists( $folder = alicia_config( 'classification' ) ) ) {
				alicia_storage()->makeDirectory( $folder );
			}

			return ltrim( $folder, '/' );
		}
	}
