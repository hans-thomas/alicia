<?php

	namespace Hans\Alicia\Contracts;

	use Hans\Alicia\Models\Resource;
	use Illuminate\Support\Collection;

	interface AliciaContract {
		public function upload( string $field, array $rules = null ): self;

		public function external( string $field, array $rules = null ): self;

		public function batch( string $field, array $uploadRules = null, array $externalRules = null ): self;

		public function deleteFile( string $path ): bool;

		public function generateName( string $driver = null, int $length = 16 ): string;

		public function generateFolder(): string;

		public function getData(): Resource|Collection;

		public function delete( Resource|int $model ): bool;

		public function batchDelete( array $ids ): array;

		public function export( array $resolutions = null ): self;

		public function makeExternal( Resource $resource, string $url ): Resource;
	}
