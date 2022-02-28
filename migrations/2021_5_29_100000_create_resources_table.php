<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	class CreateResourcesTable extends Migration {
		/**
		 * Run the migrations.
		 *
		 * @return void
		 */
		public function up() {
			Schema::create( 'resources', function( Blueprint $table ) {
				$table->id();
				$table->string( 'title' );
				$table->string( 'path' );
				$table->string( 'file' )->nullable();
				$table->string( 'hls' )->nullable();
				$table->string( 'extension', 50 );
				$table->text( 'options' )->nullable();
				$table->boolean( 'external' )->default( false );
				$table->timestamp( 'published_at' )->nullable();

				$table->timestamps();
			} );
		}

		/**
		 * Reverse the migrations.
		 *
		 * @return void
		 */
		public function down() {
			Schema::dropIfExists( 'resources' );
		}
	}
