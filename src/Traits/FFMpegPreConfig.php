<?php

namespace Hans\Alicia\Traits;

use ProtoneMedia\LaravelFFMpeg\MediaOpener;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

trait FFMpegPreConfig
{
    /**
     * Return ffmpeg instance which configured with current file.
     *
     * @return MediaOpener
     */
    public function ffmpeg(): MediaOpener
    {
        return FFMpeg::fromDisk(alicia_storage())->open($this->path);
    }
}
