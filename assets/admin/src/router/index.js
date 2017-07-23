import Vue from 'vue';
import Router from 'vue-router';
import axios from 'axios';

import Panel from '@/components/Panel';
import List from '@/components/List';
import Edit from '@/components/Edit';
import Files from '@/components/Files';

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
                    path: '/models/:modelType',
                    name: 'List',
                    component: List,
                    props: true
                },
                {
                    path: '/models/:modelType/create',
                    name: 'Create',
                    component: Edit,
                    props: true
                },
                {
                    path: '/models/:modelType/edit/:id',
                    name: 'Edit',
                    component: Edit,
                    props: true
                },
                {
                    path: '/files',
                    name: 'Files',
                    component: Files,
                    props: true
                }
            ]
        }
    ]
});
