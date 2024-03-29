<?php

namespace Hans\Alicia\Jobs;

use FFMpeg\Format\Video\X264;
use Hans\Alicia\Facades\Alicia;
use Hans\Alicia\Models\Resource as ResourceModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OptimizeVideoJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param ResourceModel $model
     */
    public function __construct(
        protected ResourceModel $model
    ) {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): void
    {
        $oldFile = $this->model->path;
        $this->model->ffmpeg()
                    ->export()
                    ->inFormat(new X264())
                    ->save($this->model->directory.'/'.$newFile = generate_file_name().'.'.$this->model->extension);

        $this->model->update([
            'file' => $newFile,
        ]);

        $this->model->updateOptions(['size' => alicia_storage()->size($this->model->path)]);

        Alicia::deleteFile($oldFile);
    }
}
