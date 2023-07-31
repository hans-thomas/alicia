<?php

namespace Hans\Alicia\Traits;

    use Hans\Alicia\Facades\Alicia;
    use Hans\Alicia\Models\Resource;
    use Illuminate\Database\Eloquent\Relations\MorphToMany;

    trait AliciaHandler
    {
        /**
         * Definition of attachments relationship to resource.
         *
         * @return MorphToMany
         */
        public function attachments(): MorphToMany
        {
            return $this->morphToMany(Resource::class, 'resourcable')
                        ->orderByPivot('attached_at')
                        ->withPivot('key', 'attached_at');
        }

        /**
         * Returns oldest attachment.
         *
         * @return resource|null
         */
        public function attachment(): ?Resource
        {
            return $this->attachments()->limit(1)->first();
        }

        /**
         * Delete all attached attachments and their file(s).
         *
         * @return array
         */
        public function deleteAttachments(): array
        {
            $ids = $this->attachments()->select(['id', 'directory', 'external'])->pluck('id')->toArray();
            $this->attachments()->detach($ids);

            return Alicia::batchDelete($ids);
        }

        /**
         * Attach a resource.
         *
         * @param resource    $resource
         * @param string|null $key
         *
         * @return array
         */
        public function attachTo(Resource $resource, string $key = null): array
        {
            $data = $key ?
                [$resource->id => ['key' => $key]] :
                [$resource->id];

            return $this->attachments()->syncWithoutDetaching($data);
        }

        /**
         * Attach many resources at once.
         *
         * @param array $ids
         *
         * @return array
         */
        public function attachManyTo(array $ids): array
        {
            return $this->attachments()->syncWithoutDetaching($ids);
        }
    }
