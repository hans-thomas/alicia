<?php

	namespace Hans\Alicia\Contracts;

	use Hans\Alicia\Exceptions\AliciaErrorCode;
	use Hans\Alicia\Exceptions\AliciaException;
	use Hans\Alicia\Jobs\GenerateHLSJob;
	use Hans\Alicia\Jobs\OptimizePictureJob;
	use Hans\Alicia\Jobs\OptimizeVideoJob;
	use Hans\Alicia\Models\Resource;
	use Hans\Alicia\Models\Resource as ResourceModel;
	use Illuminate\Http\UploadedFile;
	use Illuminate\Support\Arr;
	use Illuminate\Support\Collection;
	use Illuminate\Support\Facades\Bus;
	use Illuminate\Support\Str;
	use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
	use Symfony\Component\HttpFoundation\Response as ResponseAlias;
	use Throwable;

	abstract class Actions {

		abstract public function run(): Resource|Collection;

		/**
		 * Save and return file as a eloquent model
		 *
		 * @param array $data
		 *
		 * @return Resource
		 */
		protected function storeOnDB( array $data ): Resource {
			return Resource::query()->create( $data )->refresh();
		}


		/**
		 * @throws AliciaException
		 */
		protected function makeFileTitle( UploadedFile|string $file ): string {
			return Str::of( $this->getFileName( $file ) )->camel()->snake()->toString();
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
					return generate_file_name() . $this->getExtension( $file, '.' );
			}
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
		protected function getExtension( UploadedFile|string $file, string $prefix = '' ): string {
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
		 * Get the uploaded file's details
		 *
		 * @param UploadedFile $file
		 *
		 * @return array
		 * @throws AliciaException
		 */
		protected function getOptions( UploadedFile $file ): array {
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
					$tempFile = alicia_storage()->putFile( get_classified_folder(), $file );
					FFMpeg::fromFilesystem( alicia_storage() )
					      ->open( $tempFile )
					      ->getFrameFromSeconds( 1 )
					      ->export()
					      ->save( $tempFrame = generate_file_name() . '.png' );
					if ( alicia_storage()->exists( $tempFrame ) ) {
						if ( $dimensions = getimagesize( alicia_storage()->path( $tempFrame ) ) ) {
							$data[ 'width' ]  = $dimensions[ 0 ];
							$data[ 'height' ] = $dimensions[ 1 ];
						}
						alicia_storage()->delete( $tempFrame );
					}
					$data[ 'duration' ] = FFMpeg::fromFilesystem( alicia_storage() )
					                            ->open( $tempFile )
					                            ->getDurationInSeconds();
					alicia_storage()->delete( $tempFile );
				} catch ( Throwable $e ) {
					// TODO: taking a frame from video failed
				}
			}

			return $data;
		}

		/**
		 * Store the uploaded file in defined folder
		 *
		 * @param ResourceModel $model
		 * @param UploadedFile  $file
		 *
		 * @return string
		 */
		protected function storeOnDisk( Resource $model, UploadedFile $file ): string {
			return alicia_storage()->putFileAs( $model->path, $file, $model->file );
		}

		/**
		 * Apply related jobs on model
		 *
		 * @param ResourceModel $model
		 *
		 * @return void
		 * @throws AliciaException
		 */
		protected function processModel( ResourceModel $model ): void {
			try {
				if (
					alicia_config( 'optimization.images' ) and
					in_array( $model->extension, alicia_config( 'extensions.images' ) )
				) {
					OptimizePictureJob::dispatch( $model );
				} else if (
					in_array( $model->extension, alicia_config( 'extensions.videos' ) )
				) {
					if ( alicia_config( 'optimization.videos' ) ) {
						$jobs[] = new OptimizeVideoJob( $model );
					}
					if ( alicia_config( 'hls.enable' ) ) {
						$jobs[] = new GenerateHLSJob( $model );
					}


					! isset( $jobs ) ? : Bus::chain( $jobs )->dispatch();
				}
			} catch ( Throwable $e ) {
				throw new AliciaException(
					'Failed to process the model! ' . $e->getMessage(),
					AliciaErrorCode::FAILED_TO_PROCESS_MODEL,
					ResponseAlias::HTTP_INTERNAL_SERVER_ERROR
				);
			}
		}

	}