import Vue from 'vue';
import Router from 'vue-router';
import axios from 'axios';

import Panel from '@/components/Panel';
import List from '@/components/List';

Vue.use(Router);
Vue.prototype.$http = axios.create({
    baseURL: '/api'
});

export default new Router({
    routes: [
        {
            path: '/',
            name: 'Panel',
            component: Panel,
            children: [
                {
                    path: '/model/:modelType',
                    name: 'List',
                    component: List,
                    props: true
                }
            ]
        }
    ]
});
