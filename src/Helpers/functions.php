<?php

	if ( ! function_exists( 'alicia_config' ) ) {
		function alicia_config( string $key, mixed $default = null ) {
			return config( "alicia.{$key}", $default );
		}
	}
