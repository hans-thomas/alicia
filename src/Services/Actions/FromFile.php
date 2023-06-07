<?php

	namespace Hans\Alicia\Services\Actions;

	use Hans\Alicia\Contracts\Actions;
	use Hans\Alicia\Exceptions\AliciaErrorCode;
	use Hans\Alicia\Exceptions\AliciaException;
	use Hans\Alicia\Models\Resource;
	use Illuminate\Support\Collection;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Storage;
	use Illuminate\Support\Str;
	use Symfony\Component\HttpFoundation\File\File;
	use Throwable;

	class FromFile extends Actions {

		public function __construct(
			protected readonly string $path
		) {
		}

		public function run(): Resource {
			$fs        = Storage::build( Str::beforeLast( $this->path, '/' ) );
			$file      = Str::afterLast( $this->path, '/' );
			$extension = Str::afterLast( $this->path, '.' );
			if ( ! $fs->fileExists( $file ) ) {
				throw new AliciaException(
					'File does not exist!',
					AliciaErrorCode::FILE_DOEST_NOT_EXIST
				);
			}
			$options = $this->getOptions( new File( $fs->path( $file ) ) );
			try {
				DB::beginTransaction();
				$model = $this->storeOnDB( [
					'title'     => Str::beforeLast( $file, '.' ),
					'path'      => get_classified_folder() . '/' . generate_file_name( 'string', 8 ),
					'file'      => generate_file_name() . '.' . $extension,
					'extension' => $extension,
					'options'   => $options,
				] );
				alicia_storage()->put( $model->path . '/' . $model->file, $fs->read( $file ) );
				DB::commit();
			} catch ( Throwable $e ) {
				DB::rollBack();
				throw new AliciaException(
					'Making resource from file failed! ' . $e->getMessage(),
					AliciaErrorCode::FAILED_TO_MAKE_RESOURCE_FROM_FILE
				);
			}

			return $model;
		}


	}