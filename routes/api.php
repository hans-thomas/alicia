<?php

	use Hans\Alicia\Http\Controllers\ResourceController;
	use Illuminate\Support\Facades\Route;

	Route::get( alicia_config( 'link' ), [ ResourceController::class, 'download' ] )->name( 'alicia.download' );
