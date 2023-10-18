@filters([
    'name' => 'filter',
    'include' => ['state'],
    'options' => [
        'All' => '',
        'Remote' => 'remote',
        'Test' => 'local'
    ]
])

@filters([
    'name' => 'state',
    'include' => ['filter'],
    'options' => [
        'All' => '',
        'Pending' => 'pending',
        'Completed' => 'completed',
        'Failed' => 'failed',
        'Abandoned' => 'abandoned'
    ]
])
