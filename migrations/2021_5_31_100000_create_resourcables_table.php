<?php

	use Hans\Alicia\Models\Resource;
	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration {
		/**
		 * Run the migrations.
		 *
		 * @return void
		 */
		public function up() {
			Schema::create( 'resourcables', function( Blueprint $table ) {
				$table->foreignIdFor( Resource::class )->constrained()->cascadeOnDelete();
				$table->morphs( 'resourcable' );

				$table->string( 'key' )->nullable();
				$table->timestamp( 'attached_at' )->useCurrent();
			} );
		}

		/**
		 * Reverse the migrations.
		 *
		 * @return void
		 */
		public function down() {
			Schema::dropIfExists( 'resourcables' );
		}
	};
