import _ from 'lodash';
import * as Popper from '@popperjs/core';
import jquery from 'jquery';
import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window._ = _;

try {
    window.Popper = Popper;
    window.$ = window.jQuery = jquery;
} catch (e) {}

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.Pusher = Pusher;

const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY || '';
const pusherCluster = import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1';

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: pusherKey,
    cluster: pusherCluster,
    forceTLS: true,
});
