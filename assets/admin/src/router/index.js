import Vue from 'vue';
import Router from 'vue-router';
import axios from 'axios';

import Panel from '@/components/Panel';

Vue.use(Router);
Vue.prototype.$http = axios.create({
    baseURL: '/api/'
});

export default new Router({
    routes: [
        {
            path: '/',
            name: 'Panel',
            component: Panel
        }
    ]
});
