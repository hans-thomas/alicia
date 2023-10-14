<?php

namespace Hans\Alicia\Services\Actions;

use Hans\Alicia\Contracts\Actions;
use Hans\Alicia\Jobs\GenerateHLSJob;
use Hans\Alicia\Models\Resource;
use Illuminate\Support\Collection;

class HlsExport extends Actions
{
    public function __construct(
        protected readonly Resource $model
    ) {
    }

    /**
     * Contain action's logic.
     *
     * @return resource|Collection
     */
    public function run(): Resource|Collection
    {
        if (in_array($this->model->extension, alicia_config('extensions.videos')) and !alicia_config('hls.enable')) {
            GenerateHLSJob::dispatch($this->model);
        }

        return $this->model;
    }
}
