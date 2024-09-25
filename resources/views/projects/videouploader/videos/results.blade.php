@table([
	'rows' => $videos,
	'columns' => [
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