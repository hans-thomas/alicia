<?php

	namespace Hans\Alicia\Facades;

	use Illuminate\Support\Facades\Facade;

	class Signature extends Facade {

		/**
		 * Get the registered name of the component.
		 *
		 * @return string
		 *
		 * @throws \RuntimeException
		 */
		protected static function getFacadeAccessor() {
			return 'signature-service';
		}

	}