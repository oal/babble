<template>
    <div id="panel">
        <header id="top">
            <router-link :to="{name: 'Index'}">Babble CMS Admin</router-link>
        </header>
        <div id="main">
            <aside id="sidebar">
                <div class="ui vertical text menu" v-for="model in models" v-bind:key="model.type">
                    <div class="header item">
                        <i class="icon" :class="model.options.admin.icon" v-if="model.options && model.options.admin && model.options.admin.icon"></i>
                        {{ model.name_plural }}
                    </div>

                    <router-link v-bind:to="{name: 'List', params: {modelType: model.type}}" class="item">
                        All {{ model.name_plural }}
                    </router-link>

                    <router-link v-bind:to="{name: 'Create', params: {modelType: model.type}}" class="item">
                        Add new
                    </router-link>
                </div>

                <div class="ui vertical text menu">
                    <div class="header item">File manager</div>

                    <router-link v-bind:to="{name: 'Files'}" class="item">
                        All files
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
            if(!this.$http.defaults.auth) {
                this.$router.push({name: 'Login'});
            }

            this.$http.options('/models').then(response => {
                this.models = response.data;
            });
        },
        data() {
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

    #top a {
        color: #fff;
        font-weight: bold;
    }

    #main {
        display: flex;
        flex-grow: 1;
    }

    @media (max-width: 600px) {
        #main {
            flex-direction: column;
        }
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