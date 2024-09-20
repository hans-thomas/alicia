<?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class() extends Migration {
        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
        {
            Schema::create('resources', function (Blueprint $table) {
                $table->id();

                $table->foreignId('parent_id')->nullable()->constrained('resources')->cascadeOnDelete();

                $table->string('title');
                $table->string('directory')->nullable();
                $table->string('file')->nullable();
                $table->string('hls')->nullable();
                $table->string('link')->nullable();
                $table->string('extension', 50);
                $table->text('options')->nullable();
                $table->boolean('external')->default(false);

                $table->timestamps();
            });
        }

        /**
         * Reverse the migrations.
         *
         * @return void
         */
        public function down()
        {
            Schema::dropIfExists('resources');
        }
    };
