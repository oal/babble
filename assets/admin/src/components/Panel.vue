<template>
    <div id="panel">
        <header id="top">
            <router-link :to="{name: 'Index'}">Babble CMS Admin</router-link>
            <ul class="top-right">
                <li>{{ $t('welcome') }}, {{ $root.user.id }}!</li>
                <li>
                    <a @click="logout">
                        {{ $t('logout') }}
                        <i class="sign out icon"></i>
                    </a>
                </li>
            </ul>
        </header>
        <div id="main">
            <aside id="sidebar">
                <div class="ui vertical text menu" v-for="model in models" v-bind:key="model.type">
                    <div class="header item">
                        <i class="icon" :class="model.options.admin.icon"
                           v-if="model.options && model.options.admin && model.options.admin.icon"></i>
                        {{ model.name_plural }}
                    </div>

                    <router-link v-bind:to="{name: 'List', params: {modelType: model.type}}" class="item">
                        {{ $t('allRecords') }} {{ model.name_plural }}
                    </router-link>

                    <router-link v-bind:to="{name: 'Create', params: {modelType: model.type}}" class="item">
                        {{ $t('addRecord') }}
                    </router-link>
                </div>

                <div class="ui vertical text menu">
                    <div class="header item">
                        <i class="file icon"></i>
                        {{ $t('fileManager') }}
                    </div>

                    <router-link v-bind:to="{name: 'Files'}" class="item">
                        {{ $t('allFiles') }}
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
            if (!this.$http.defaults.auth) {
                this.$router.push({name: 'Login'});
            }

            this.$http.options('/models').then(response => {
                this.models = response.data;
            });
        },

        data() {
            return {
                models: []
            }
        },

        methods: {
            logout() {
                this.$http.auth = null;
                this.$router.push({name: 'Login'});
            }
        }
    }
</script>

<style lang="scss" type="text/scss">
    a {
        cursor: pointer;
    }

    #panel {
        font-family: sans-serif;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    #top {
        background-color: #111;
        padding: 1rem 2rem;
        color: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;

        a {
            color: #fff;
            font-weight: bold;
            flex-grow: 1;
        }

        .top-right {
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            flex-grow: 1;
            justify-content: space-between;

            li {
                display: block;
                padding: 0;
                margin: 0 0 0 2rem;

                &:first-child {
                    margin-left: 0;
                }
            }
        }
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