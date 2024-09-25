@table([
	'rows' => $videos,
	'columns' => [
		'created_at*' => [
			'label' => 'Date',
			'width' => '1%'
		], 
		'name*' => [
			'label' => 'Name',
			'width' => 'auto'
		],
		'composer*' => [
			'label' => 'Composer',
			'width' => 'auto'
		],
		'actions' => [
			'label' => '',
			'width' => '1%'
		]],
	'view' => 'projects.videouploader.videos.row'
])