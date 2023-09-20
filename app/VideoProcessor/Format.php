<?php

namespace App\VideoProcessor;

use FFMpeg\Format\Video\X264;

class Format
{
	protected $quality = 1500;

	public function getLowQuality()
	{
		return (new X264)->setKiloBitrate($this->quality);
	}
}