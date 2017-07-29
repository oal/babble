import Vue from 'vue';
import Router from 'vue-router';
import axios from 'axios';

import Login from '@/components/Login';
import Panel from '@/components/Panel';
import Index from '@/components/Index';
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
            path: '/login',
            name: 'Login',
            component: Login
        },
        {
            path: '/',
            name: 'Panel',
            component: Panel,
            children: [
                {
                    path: '/',
                    name: 'Index',
                    component: Index,
                    props: true
                },
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
