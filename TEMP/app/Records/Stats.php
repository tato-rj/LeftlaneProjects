<?php

namespace App\Records;

class Stats extends AppStats
{
	public function gender()
	{
		$noGender = $this->model->whereNull('gender')->count();
		$male = $this->model->where('gender', 'male')->count();
		$female = $this->model->where('gender', 'female')->count();

		return [$this->percentage($noGender), $this->percentage($male), $this->percentage($female)];
	}

}
