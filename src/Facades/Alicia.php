<?php

	namespace Hans\Alicia\Facades;

	use Illuminate\Support\Facades\Facade;

	class Alicia extends Facade {

		/**
		 * Get the registered name of the component.
		 *
		 * @return string
		 *
		 * @throws \RuntimeException
		 */
		protected static function getFacadeAccessor() {
			return 'alicia-service';
		}

	}