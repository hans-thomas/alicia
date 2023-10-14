<?php

namespace Hans\Alicia\Services\Actions;

use Hans\Alicia\Contracts\Actions;
use Hans\Alicia\Exceptions\AliciaErrorCode;
use Hans\Alicia\Exceptions\AliciaException;
use Hans\Alicia\Models\Resource;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Image\Exceptions\InvalidManipulation;
use Spatie\Image\Image;

class Export extends Actions
{
    public function __construct(
        protected readonly Resource $model,
        protected readonly ?array $resolutions = null,
    ) {
    }

    /**
     * Contain action's logic.
     *
     * @throws InvalidManipulation
     *
     * @return Collection
     */
    public function run(): Collection
    {
        $data = collect();
        if (
            $this->model->isExternal() or
            !in_array($this->model->extension, alicia_config('extensions.images'))
        ) {
            return $data;
        }

        foreach ($this->resolutions ?: alicia_config('export') as $height => $width) {
            $fileName = Str::remove('.'.$this->model->extension, $this->model->file).
                        "-{$height}x{$width}.".$this->model->extension;
            $filePath = alicia_storage()->path($this->model->directory.'/'.$fileName);
            Image::load(alicia_storage()->path($this->model->path))
                 ->optimize()
                 ->height($height)
                 ->width($width)
                 ->save($filePath);
            $child = $this->storeOnDB([
                'title'     => $this->model->title."-{$height}x{$width}",
                'directory' => $this->model->directory,
                'file'      => $fileName,
                'extension' => $this->model->extension,
                'options'   => array_merge(
                    $this->model->options,
                    ['size' => filesize($filePath), 'width' => $width, 'height' => $height]
                ),
                'external'  => $this->model->external,
            ]);
            $child->parent()->associate($this->model)->save();
            $data->push($child->withoutRelations());
        }

        return $data;
    }
}
