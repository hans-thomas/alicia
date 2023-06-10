<?php


	namespace Hans\Alicia\Http\Controllers;


	use Hans\Alicia\Contracts\SignatureContract;
	use Hans\Alicia\Exceptions\AliciaErrorCode;
	use Hans\Alicia\Exceptions\AliciaException;
	use Hans\Alicia\Facades\Signature;
	use Hans\Alicia\Models\Resource as ResourceModel;
	use Illuminate\Routing\Controller;
	use Symfony\Component\HttpFoundation\Response as ResponseAlias;

	class ResourceController extends Controller {

		/**
		 * Serve the file if request is valid
		 *
		 * @throws AliciaException
		 */
		public function download( ResourceModel $resource, string $hash = '' ) {
			if ( alicia_config( 'signed' ) ) {
				if ( ! request()->hasValidSignature() ) {
					throw new AliciaException(
						'Your link in not valid!', AliciaErrorCode::LINK_IS_INVALID,
						ResponseAlias::HTTP_BAD_REQUEST
					);
				}
				if ( Signature::isNotValid( $hash ) ) {
					throw new AliciaException(
						'You\'re not allow to download this file!',
						AliciaErrorCode::NOT_ALLOWED_TO_DOWNLOAD,
						ResponseAlias::HTTP_UNAUTHORIZED
					);
				}
			}

			if ( $resource->isExternal() ) {
				alicia_storage()->deleteDirectory( 'temp' );
				alicia_storage()->put(
					$tempFile = 'temp/' . generate_file_name() . ".$resource->extension",
					file_get_contents( $resource->link )
				);

				return response()
					->download(
						file: alicia_storage()->path( $tempFile ),
						name: "{$resource->title}.{$resource->extension}"
					);
			}

			return alicia_storage()
				->download(
					path: $resource->address,
					name: "{$resource->title}.{$resource->extension}",
				);
		}

	}
