<?php


	namespace Hans\Alicia\Scopes;


	use Illuminate\Database\Eloquent\Builder;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Scope;

	class PublishedOnlyScope implements Scope {

		public function apply( Builder $builder, Model $model ) {
			return $builder->whereNotNull( 'published_at' );
		}
	}
