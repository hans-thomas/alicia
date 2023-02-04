<?php


	namespace Hans\Alicia\Traits;


	use Hans\Alicia\Exceptions\AliciaErrorCode;
	use Hans\Alicia\Exceptions\AliciaException;
	use Hans\Alicia\Jobs\ClassificationJob;
	use Hans\Alicia\Jobs\GenerateHLSJob;
	use Hans\Alicia\Jobs\OptimizePictureJob;
	use Hans\Alicia\Jobs\OptimizeVideoJob;
	use Hans\Alicia\Models\Resource as ResourceModel;
	use Illuminate\Http\UploadedFile;
	use Illuminate\Support\Arr;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Validator;
	use Illuminate\Support\Str;
	use Illuminate\Validation\ValidationException;
	use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
	use Symfony\Component\HttpFoundation\Response as ResponseAlias;
	use Throwable;

	trait Utils {
		private array $requireData = [];

		/**
		 * Get the uploaded file's details
		 *
		 * @param string $field
		 *
		 * @return array
		 * @throws AliciaException
		 */
		public function getOptions( string $field ): array {
			$data = [
				'size'     => ( $file = $this->getFromRequest( $field ) )->getSize(),
				'mimeType' => $file->getMimeType()
			];
			if ( ( $type = $this->getFileType( $field ) ) == 'image' ) {
				if ( $dimensions = getimagesize( $file->getRealPath() ) ) {
					$data[ 'width' ]  = $dimensions[ 0 ];
					$data[ 'height' ] = $dimensions[ 1 ];
				}
			} elseif ( $type == 'video' ) {
				try {
					$tempFile = $this->storage->putFile( $this->generateFolder(), $file );
					FFMpeg::fromFilesystem( $this->storage )
					      ->open( $tempFile )
					      ->getFrameFromSeconds( 1 )
					      ->export()
					      ->save( $tempFrame = $this->generateName() . '.png' );
					if ( $this->storage->exists( $tempFrame ) ) {
						if ( $dimensions = getimagesize( $this->storage->path( $tempFrame ) ) ) {
							$data[ 'width' ]  = $dimensions[ 0 ];
							$data[ 'height' ] = $dimensions[ 1 ];
						}
						$this->storage->delete( $tempFrame );
					}
					$data[ 'duration' ] = FFMpeg::fromFilesystem( $this->storage )
					                            ->open( $tempFile )
					                            ->getDurationInSeconds();
				} catch ( Throwable $e ) {
					// TODO: taking a frame from video failed
				}
			}

			return $data;
		}

		/**
		 * Get a specific field from request
		 *
		 * @param string $field
		 *
		 * @return mixed
		 */
		private function getFromRequest( string $field ): mixed {
			$key = Str::contains( $field, '.' ) ? Str::after( $field, '.' ) : 0;

			return isset( $this->getRequest( $field )[ $field ][ $key ] ) ? $this->getRequest( $field )[ $field ][ $key ] : null;
		}

		/**
		 * Get all file and input request and merge them into one array
		 *
		 * @param string $field
		 *
		 * @return array
		 */
		private function getRequest( string $field ): array {
			// parse the field
			if ( Str::contains( $field, '.' ) ) {
				$fieldName = Str::before( Str::remove( '\\', $field ), '.' );
			} else {
				$fieldName = $field;
			}
			// return if exists
			if ( isset( $this->requireData[ $fieldName ] ) ) {
				return [ $field => $this->requireData[ $fieldName ] ];
			}

			$data = collect();
			if ( request()->hasFile( $fieldName ) ) {
				$data->push( request()->file( $fieldName ) );
			}
			if ( request()->hasAny( $fieldName ) ) {
				$data->push( request()->input( $fieldName ) );
			}

			$this->requireData[ $fieldName ] = $data->flatten()->toArray();

			return [ $fieldName => $this->requireData[ $fieldName ] ];
		}

		/**
		 * Determine the file type by the file's extension
		 *
		 * @param string $field
		 *
		 * @return string
		 * @throws AliciaException
		 */
		private function getFileType( string $field ): string {
			$extension = $this->getExtension( $field );

			return match ( true ) {
				in_array( $extension, $this->getConfig( 'extensions.images' ) ) => 'image',
				in_array( $extension, $this->getConfig( 'extensions.videos' ) ) => 'video',
				in_array( $extension, $this->getConfig( 'extensions.audios' ) ) => 'audio',
				in_array( $extension, $this->getConfig( 'extensions.files' ) ) => 'file',
				default => throw new AliciaException( 'Unknown file type! the file extension is not in the extensions list.',
					AliciaErrorCode::UNKNOWN_FILE_TYPE, ResponseAlias::HTTP_BAD_REQUEST )
			};
		}

		/**
		 * Get the file's extension based-on request type
		 *
		 * @param        $field
		 * @param string $prefix
		 *
		 * @return string
		 * @throws AliciaException
		 */
		private function getExtension( $field, string $prefix = '' ): string {
			$type = $this->getFieldType( $field );

			return match ( true ) {
				$type == 'file' => $prefix . $this->getFileExtension( $field ),
				$type == 'link' => $prefix . $this->getUrlExtension( $field ),
				default => throw new AliciaException( 'Unknown Extension!', AliciaErrorCode::UNKNOWN_EXTENSION,
					ResponseAlias::HTTP_BAD_REQUEST )
			};
		}

		/**
		 * Determine that the field's data is what
		 *
		 * @param string $field
		 *
		 * @return string
		 * @throws AliciaException
		 */
		private function getFieldType( string $field ): string {
			return match ( true ) {
				$this->getFromRequest( $field ) instanceof UploadedFile => 'file',
				is_string( $this->getFromRequest( $field ) ) => 'link',
				default => throw new AliciaException( 'Unknown field type! supported field types: file, string',
					AliciaErrorCode::UNKNOWN_FIELD_TYPE, ResponseAlias::HTTP_BAD_REQUEST )
			};
		}

		/**
		 * Get the uploaded file's extension
		 *
		 * @param string $field
		 *
		 * @return string
		 */
		private function getFileExtension( string $field ): string {
			return $this->getFromRequest( $field )->extension();
		}

		/**
		 * Get the target file's extension from the link
		 *
		 * @param string $field
		 *
		 * @return string
		 */
		private function getUrlExtension( string $field ): string {
			$file      = Arr::last( explode( '/', $this->getFromRequest( $field ) ) );
			$extension = Arr::last( explode( '.', $file ) );
			if ( str_contains( $extension, '?' ) ) {
				$extension = substr( $extension, 0, strpos( $extension, '?' ) );
			}

			return $extension;
		}

		/**
		 * Set a title for the file based-on the file name
		 *
		 * @param string $field
		 *
		 * @return string
		 * @throws AliciaException
		 */
		public function setTitle( string $field ): string {
			$title = explode( '.', $this->getFileName( $field ) );
			$name  = '';
			foreach ( $title as $item ) {
				$name .= $item != end( $title ) ? '-' . $item : '';
			}

			return ltrim( $name, '-' );
		}

		/**
		 * Get the file name ( file or link )
		 *
		 * @param string $field
		 *
		 * @return string
		 * @throws AliciaException
		 */
		private function getFileName( string $field ): string {
			$fieldType = $this->getFieldType( $field );
			switch ( $fieldType ) {
				case 'file' :
					return $this->getFromRequest( $field )->getClientOriginalName();
				case 'link' :
					$url      = explode( '/', $this->getFromRequest( $field ) );
					$filename = end( $url );

					return str_contains( $filename, '?' ) ? substr( $filename, 0,
						strpos( $filename, '?' ) ) : $filename;
				default:
					return $this->generateName() . $this->getExtension( $field, '.' );
			}
		}

		/**
		 * Validate the coming request
		 *
		 * @throws ValidationException|AliciaException if validation is failed
		 */
		private function validate( string $field, array $additional = [], string $type = null ): array {
			$fieldName = Str::before( Str::remove( '\\', $field ), '.' );
			$validator = Validator::make( [ $fieldName => $this->getFromRequest( $field ) ], [
				$fieldName => array_merge( $this->getConfig( 'validation.' . $type ? : $this->getFileType( $field ),
					[] ), $additional, [ 'bail' ] ),
			] )->after( function( \Illuminate\Validation\Validator $validator ) use ( $field, $type ) {
				if ( $type = ! 'external' ) {
					return;
				}

				if ( ! in_array( $this->getExtension( $field ), $extensions = $this->getAllowedExtensions( false ) ) ) {
					$validator->errors()
					          ->add( $field, 'The link must be a file of type: ' . implode( ', ', $extensions ) );
				}
			} );

			return $validator->validate();
		}

		/**
		 * Get a list of allowed extensions
		 *
		 * @param bool $implode
		 *
		 * @return array|string
		 */
		private function getAllowedExtensions( bool $implode = true ): array|string {
			$data = [];
			foreach ( $this->getConfig( 'extensions' ) as $extension ) {
				$data = array_merge( $data, $extension );
			}

			return $implode ? implode( ',', $data ) : $data;
		}

		/**
		 * Get maximum size defined for a specific file type
		 *
		 * @param string $field
		 *
		 * @return string
		 * @throws AliciaException
		 */
		private function getMaxSize( string $field ): string {
			return $this->getConfig( 'sizes.' . $this->getFileType( $field ) ) * 1024;
		}

		/**
		 * Store the uploaded file in defined folder
		 *
		 * @param UploadedFile $file
		 *
		 * @return string
		 */
		private function storeFile( UploadedFile $file ): string {
			return $this->storage->putFileAs( $this->model->path, $file, $this->model->file );
		}

		/**
		 * Save and return file as a eloquent model
		 *
		 * @param array $data
		 *
		 * @return ResourceModel
		 * @throws AliciaException
		 */
		private function save( array $data ): ResourceModel {
			DB::beginTransaction();
			try {
				$model = ResourceModel::query()->create( $data );
			} catch ( Throwable $e ) {
				DB::rollBack();
				throw new AliciaException( 'Failed to store the model on database! ' . $e->getMessage(),
					AliciaErrorCode::MODEL_STORE_FAILED, ResponseAlias::HTTP_INTERNAL_SERVER_ERROR );
			}
			DB::commit();

			return $model->fresh();
		}

		private function processModel( ResourceModel $model ): void {
			try {
				ClassificationJob::dispatchIf( $this->getConfig( 'temp' ), $model );
				if ( in_array( $model->extension, $this->getConfig( 'extensions.images' ) ) ) {
					OptimizePictureJob::dispatchIf( $this->getConfig( 'optimization.images' ), $model->id )
					                  ->afterCommit();
				} else if ( in_array( $model->extension, $this->getConfig( 'extensions.videos' ) ) ) {
					OptimizeVideoJob::withChain( [
						new GenerateHLSJob( $model )
					] )->dispatchIf( config( 'alicia.optimization.videos' ), $model );
				}
			} catch ( Throwable $e ) {
				throw new AliciaException( 'Failed to process the model! ' . $e->getMessage(),
					AliciaErrorCode::FAILED_TO_PROCESS_MODEL, ResponseAlias::HTTP_INTERNAL_SERVER_ERROR );
			}
		}

		private function getConfig( string $key, $default = null ) {
			return Arr::get( config( 'alicia' ), $key, $default );
		}

		/**
		 * After upload actions, you can get the related model
		 *
		 * @return ResourceModel
		 * @throws AliciaException
		 */
		private function getModel(): ResourceModel {
			return isset( $this->model ) ? $this->model->refresh() : throw new AliciaException( 'Cant access data before execute an action!',
				AliciaErrorCode::FAILED_TO_ACCESS_MODEL, ResponseAlias::HTTP_CONFLICT );
		}
	}
