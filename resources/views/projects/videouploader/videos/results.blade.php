@table([
	'rows' => $users,
	'columns' => [
		'created_at*' => [
			'label' => 'Date',
			'width' => '1%'
		], 
		'first_name*' => [
			'label' => 'Name',
			'width' => 'auto'
		],
		'email*' => [
			'label' => 'Email',
			'width' => 'auto'
		],
		'instruments*' => [
			'label' => 'Instrument(s)',
			'width' => 'auto'
		],
		'role*' => [
			'label' => 'Admin role',
			'width' => '1%'
		],
		'status*' => [
			'label' => 'Membership status',
			'width' => '1%'
		],
		'actions' => [
			'label' => '',
			'width' => '1%'
		]],
	'view' => 'users.row'
])