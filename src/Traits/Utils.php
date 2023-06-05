<?php


	namespace Hans\Alicia\Traits;


	use Hans\Alicia\Exceptions\AliciaErrorCode;
	use Hans\Alicia\Exceptions\AliciaException;
	use Hans\Alicia\Jobs\GenerateHLSJob;
	use Hans\Alicia\Jobs\OptimizePictureJob;
	use Hans\Alicia\Jobs\OptimizeVideoJob;
	use Hans\Alicia\Models\Resource as ResourceModel;
	use Illuminate\Http\UploadedFile;
	use Illuminate\Support\Arr;
	use Illuminate\Support\Str;
	use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
	use Symfony\Component\HttpFoundation\Response as ResponseAlias;
	use Throwable;

	trait Utils {

		/**
		 * Get the uploaded file's details
		 *
		 * @param UploadedFile $file
		 *
		 * @return array
		 * @throws AliciaException
		 */
		public function getOptions( UploadedFile $file ): array {
			$data = [
				'size'     => $file->getSize(),
				'mimeType' => $file->getMimeType()
			];
			if ( ( $type = $this->getFileType( $file ) ) == 'image' ) {
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
		 * Determine the file type by the file's extension
		 *
		 * @param UploadedFile|string $file
		 *
		 * @return string
		 * @throws AliciaException
		 */
		private function getFileType( UploadedFile|string $file ): string {
			$extension = $this->getExtension( $file );

			return match ( true ) {
				in_array( $extension, alicia_config( 'extensions.images' ) ) => 'image',
				in_array( $extension, alicia_config( 'extensions.videos' ) ) => 'video',
				in_array( $extension, alicia_config( 'extensions.audios' ) ) => 'audio',
				in_array( $extension, alicia_config( 'extensions.files' ) ) => 'file',
				default => throw new AliciaException(
					'Unknown file type! the file extension is not in the extensions list.',
					AliciaErrorCode::UNKNOWN_FILE_TYPE,
					ResponseAlias::HTTP_BAD_REQUEST
				)
			};
		}

		/**
		 * Get the file's extension based-on request type
		 *
		 * @param UploadedFile|string $file
		 * @param string              $prefix
		 *
		 * @return string
		 * @throws AliciaException
		 */
		private function getExtension( UploadedFile|string $file, string $prefix = '' ): string {
			$type = $this->getFieldType( $file );

			return match ( true ) {
				$type == 'file' => $prefix . $file->getClientOriginalExtension(),
				$type == 'link' => $prefix . $this->getUrlExtension( $file ),
				default => throw new AliciaException(
					'Unknown Extension!',
					AliciaErrorCode::UNKNOWN_EXTENSION,
					ResponseAlias::HTTP_BAD_REQUEST
				)
			};
		}

		/**
		 * Determine that the field's data is what
		 *
		 * @param UploadedFile|string $file
		 *
		 * @return string
		 * @throws AliciaException
		 */
		private function getFieldType( UploadedFile|string $file ): string {
			return match ( true ) {
				$file instanceof UploadedFile => 'file',
				is_string( $file ) => 'link',
				default => throw new AliciaException(
					'Unknown field type! supported field types: file, string',
					AliciaErrorCode::UNKNOWN_FIELD_TYPE,
					ResponseAlias::HTTP_BAD_REQUEST
				)
			};
		}

		/**
		 * Get the target file's extension from the link
		 *
		 * @param string $file
		 *
		 * @return string
		 */
		private function getUrlExtension( string $file ): string {
			$fileName  = Arr::last( explode( '/', $file ) );
			$extension = Arr::last( explode( '.', $fileName ) );
			if ( str_contains( $extension, '?' ) ) {
				$extension = substr( $extension, 0, strpos( $extension, '?' ) );
			}

			return $extension;
		}

		/**
		 * Get the file name ( file or link )
		 *
		 * @param UploadedFile|string $file
		 *
		 * @return string
		 * @throws AliciaException
		 */
		private function getFileName( UploadedFile|string $file ): string {
			$fieldType = $this->getFieldType( $file );
			switch ( $fieldType ) {
				case 'file' :
					$fileName = str_replace(
						'.' . $file->getClientOriginalExtension(),
						'',
						$file->getClientOriginalName()
					);

					return str_replace( '.', '-', $fileName );
				case 'link' :
					$url      = explode( '/', $file );
					$fileName = end( $url );

					return str_replace(
						$this->getExtension( $file, '.' ),
						'',
						str_contains( $fileName, '?' ) ?
							substr( $fileName, 0, strpos( $fileName, '?' ) ) :
							$fileName
					);
				default:
					return $this->generateName() . $this->getExtension( $file, '.' );
			}
		}

		/**
		 * Get maximum size defined for a specific file type
		 *
		 * @param UploadedFile|string $file
		 *
		 * @return string
		 * @throws AliciaException
		 */
		private function getMaxSize( UploadedFile|string $file ): string {
			return alicia_config( 'sizes.' . $this->getFileType( $file ) ) * 1024;
		}

		/**
		 * Store the uploaded file in defined folder
		 *
		 * @param UploadedFile $file
		 *
		 * @return string
		 */
		private function storeOnDisk( UploadedFile $file ): string {
			return $this->storage->putFileAs( $this->model->path, $file, $this->model->file );
		}

		/**
		 * Save and return file as a eloquent model
		 *
		 * @param array $data
		 *
		 * @return ResourceModel
		 */
		private function makeModel( array $data ): ResourceModel {
			return $this->model = ResourceModel::query()->create( $data )->refresh();
		}

		/**
		 * @throws AliciaException
		 */
		private function makeFileTitle( UploadedFile|string $file ): string {
			return Str::of( $this->getFileName( $file ) )->camel()->snake()->toString();
		}

		/**
		 * Apply related jobs on model
		 *
		 * @param ResourceModel $model
		 *
		 * @return void
		 * @throws AliciaException
		 */
		private function processModel( ResourceModel $model ): void {
			try {
				if ( in_array( $model->extension, alicia_config( 'extensions.images' ) ) ) {
					OptimizePictureJob::dispatchIf( alicia_config( 'optimization.images' ), $model->id );
				} else if (
					alicia_config( 'optimization.videos' ) and
					in_array( $model->extension, alicia_config( 'extensions.videos' ) )
				) {
					OptimizeVideoJob::withChain( [
						new GenerateHLSJob( $model )
					] )->dispatch( $model );
				}
			} catch ( Throwable $e ) {
				throw new AliciaException(
					'Failed to process the model! ' . $e->getMessage(),
					AliciaErrorCode::FAILED_TO_PROCESS_MODEL,
					ResponseAlias::HTTP_INTERNAL_SERVER_ERROR
				);
			}
		}

		/**
		 * Return created model instance
		 *
		 * @return ResourceModel
		 */
		private function getModel(): ResourceModel {
			return $this->model->refresh();
		}
	}
