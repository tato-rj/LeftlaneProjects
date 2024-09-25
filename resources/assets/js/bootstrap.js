window._ = require('lodash');
window.axios = require('axios').default;

// window.Popper = require('@popperjs/core').default;

import moment from 'moment';

try {
    window.$ = window.jQuery = require('jquery');
	require('bootstrap');
} catch (e) {
	console.log(e.message);
}
