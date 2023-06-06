<?php


	namespace Hans\Alicia\Traits;


	use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

	trait FFMpegPreConfig {
		public function ffmpeg() {
			return FFMpeg::fromDisk( alicia_storage() )->open( $this->address );
		}
	}
