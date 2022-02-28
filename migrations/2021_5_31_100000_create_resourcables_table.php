<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	class CreateResourcablesTable extends Migration {
		/**
		 * Run the migrations.
		 *
		 * @return void
		 */
		public function up() {
			Schema::create( 'resourcables', function( Blueprint $table ) {
				$table->foreignId( 'resource_id' )->constrained();
				$table->unsignedBigInteger( 'resourcable_id' );
				$table->string( 'resourcable_type' );

				$table->string( 'key' )->nullable();
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
	}
