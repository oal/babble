<template>
    <div id="panel">
        <header id="top">Babble CMS Admin</header>
        <div id="main">
            <aside id="sidebar">
                <div class="ui vertical text menu" v-for="model in models" v-bind:key="model.type">
                    <div class="header item">{{ model.name_plural }}</div>

                    <router-link v-bind:to="{name: 'List', params: {modelType: model.type}}" class="item">
                        All {{ model.name_plural }}
                    </router-link>

                    <router-link v-bind:to="{name: 'Create', params: {modelType: model.type}}" class="item">
                        Add new
                    </router-link>
                </div>
            </aside>
            <article id="content">
                <router-view></router-view>
            </article>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'panel',
        created: function () {
            this.$http.options('').then(response => {
                this.models = response.data;
            });
        },
        data () {
            return {
                models: [],
                msg: 'Welcome to Babble CMS Admin'
            }
        }
    }
</script>

<style>
    #panel {
        font-family: sans-serif;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    #top {
        background-color: #111;
        padding: 1.2rem 2rem;
        color: #fff;
    }

    #main {
        display: flex;
        flex-grow: 1;
    }

    #sidebar {
        background-color: #eee;
        flex-basis: 300px;
        padding: 2rem;
    }

    #content {
        background-color: #fafafa;
        flex-grow: 1;
        padding: 2rem;
    }
</style>