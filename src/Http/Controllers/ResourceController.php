<?php


	namespace Hans\Alicia\Http\Controllers;


	use Hans\Alicia\Contracts\SignatureContract;
	use Hans\Alicia\Exceptions\AliciaErrorCode;
	use Hans\Alicia\Exceptions\AliciaException;
	use Hans\Alicia\Models\Resource as ResourceModel;
	use Illuminate\Routing\Controller;
	use Illuminate\Support\Arr;
	use Illuminate\Support\Facades\App;
	use Symfony\Component\HttpFoundation\Response as ResponseAlias;

	class ResourceController extends Controller {

		/**
		 * Serve the file if request is valid
		 *
		 * @throws AliciaException
		 */
		public function download( ResourceModel $resource, string $hash = '' ) {
			if ( $this->getConfig( 'signed' ) ) {
				if ( ! request()->hasValidSignature() ) {
					throw new AliciaException(
						'Your link in not valid!', AliciaErrorCode::LINK_IS_INVALID,
						ResponseAlias::HTTP_BAD_REQUEST
					);
				}
				if ( App::make( SignatureContract::class )->isNotValid( $hash ) ) {
					throw new AliciaException(
						'You\'re not allow to download this file!',
						AliciaErrorCode::NOT_ALLOWED_TO_DOWNLOAD,
						ResponseAlias::HTTP_UNAUTHORIZED
					);
				}
			}

			return $resource->isExternal() ?
				response( file_get_contents( $resource->link ), headers: [
					'Content-Type'   => $resource->getOptions()[ 'mimeType' ],
					'Content-Length' => $resource->getOptions()[ 'size' ],
				] ) :
				alicia_storage()
					->response(
						$resource->address,
						$resource->title . $resource->extension
					);
		}

		private function getConfig( string $key, $default = null ) {
			return Arr::get( config( 'alicia' ), $key, $default );
		}
	}
