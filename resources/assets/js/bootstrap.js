window._ = require('lodash');
import moment from 'moment';

try {
    window.$ = window.jQuery = require('jquery');
	require('bootstrap');
} catch (e) {
	console.log(e.message);
}
