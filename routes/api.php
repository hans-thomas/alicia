<?php

	use Hans\Alicia\Http\Controllers\ResourceController;
	use Illuminate\Support\Facades\Route;

	Route::get( config( 'alicia.link' ), [ ResourceController::class, 'download' ] )->name( 'alicia.download' );
