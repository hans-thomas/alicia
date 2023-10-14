<?php

namespace Hans\Alicia\Services;

use Hans\Alicia\Exceptions\AliciaException;
use Hans\Alicia\Models\Resource;
use Hans\Alicia\Services\Actions\BatchUpload;
use Hans\Alicia\Services\Actions\Delete;
use Hans\Alicia\Services\Actions\Export;
use Hans\Alicia\Services\Actions\External;
use Hans\Alicia\Services\Actions\FromFile;
use Hans\Alicia\Services\Actions\HlsExport;
use Hans\Alicia\Services\Actions\MakeExternal;
use Hans\Alicia\Services\Actions\Upload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Spatie\Image\Exceptions\InvalidManipulation;

class AliciaService
{
    /**
     * Store created data by actions.
     *
     * @var Collection
     */
    private Collection $data;

    public function __construct()
    {
        $this->data = collect();
    }

    /**
     * Store the given files and links.
     *
     * @param  array  $files
     *
     * @return self
     * @throws AliciaException
     *
     */
    public function batch(array $files): self
    {
        $this->data = (new BatchUpload($files))->run();

        return $this;
    }

    /**
     * Upload and store given file.
     *
     * @param  UploadedFile  $file
     *
     * @return self
     * @throws AliciaException ()
     *
     */
    public function upload(UploadedFile $file): self
    {
        $this->data->push((new Upload($file))->run());

        return $this;
    }

    /**
     * Store a external link.
     *
     * @param  string  $file
     *
     * @return self
     * @throws AliciaException ()
     *
     */
    public function external(string $file): self
    {
        $this->data->push((new External($file))->run());

        return $this;
    }

    /**
     * Create different version of uploaded image.
     *
     * @param  array|null  $resolutions
     *
     * @return AliciaService
     * @throws AliciaException|InvalidManipulation
     *
     */
    public function export(array $resolutions = null): self
    {
        $exports = collect();
        foreach ($this->data as $model) {
            $exports->push((new Export($model, $resolutions))->run());
        }

        $this->data = $exports->merge($this->data)
                              ->flatten(1)
                              ->filter()
                              ->groupBy(
                                  fn ($item, $key) => $item['parent_id'] ?
                                      $item['parent_id'].'-children' :
                                      'parents'
                              );

        return $this;
    }

    /**
     * Make a internal resource to external using given link.
     *
     * @param  resource  $model
     * @param  string    $url
     *
     * @return $this
     * @throws AliciaException
     *
     */
    public function makeExternal(Resource $model, string $url): self
    {
        $this->data->push((new MakeExternal($model, $url))->run());

        return $this;
    }

    /**
     * Store resource using given file.
     *
     * @param  string  $path
     *
     * @return self
     * @throws AliciaException
     *
     */
    public function fromFile(string $path): self
    {
        $this->data->push((new FromFile($path))->run());

        return $this;
    }

    /**
     * Generate Hls export of uploaded video files in the background.
     *
     * @return $this
     */
    public function HlsExport(): self
    {
        foreach ($this->data as $model) {
            (new HlsExport($model))->run();
        }

        return $this;
    }

    /**
     * Delete given resource and its file(s).
     *
     * @param  resource|int  $model
     *
     * @return bool
     * @throws AliciaException
     *
     */
    public function delete(Resource|int $model): bool
    {
        $model = $model instanceof Resource ?
            $model :
            Resource::query()
                    ->select(['id', 'directory', 'external'])
                    ->findOrFail($model);

        (new Delete($model))->run();

        return true;
    }

    /**
     * Delete resources in batch mode.
     *
     * @param  array  $ids
     *
     * @return array
     * @throws AliciaException
     *
     */
    public function batchDelete(array $ids): array
    {
        $results = collect();
        foreach ($ids as $id) {
            $key = $id instanceof Resource ?
                $id->id :
                $id;
            $results->put($key, $this->delete($id));
        }

        return $results->toArray();
    }

    /**
     * Delete a specific file in alicia disk.
     *
     * @param  string  $path
     *
     * @return bool
     */
    public function deleteFile(string $path): bool
    {
        if (alicia_storage()->exists($path)) {
            return alicia_storage()->delete($path);
        }

        return false;
    }

    /**
     * Return created Model(s).
     *
     * @return resource|Collection|null
     */
    public function getData(): Resource|Collection|null
    {
        if ($this->data->isEmpty()) {
            return null;
        }

        $result = $this->data->count() == 1 ?
            $this->data->first() :
            $this->data;

        $this->data = collect();

        return $result;
    }
}
