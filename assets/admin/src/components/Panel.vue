<template>
    <div id="panel">
        <header id="top">Babble CMS Admin</header>
        <div id="main">
            <aside id="sidebar">
                <div v-for="model in models">
                    <h3>{{ model.name_plural }}</h3>
                    <ul>
                        <li><a href="#">All {{ model.name_plural }}</a></li>
                        <li><a href="#">Add new</a></li>
                    </ul>
                </div>

            </aside>
            <article id="content"></article>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'panel',
        mounted: function() {
            this.$http.options('').then(response => {
                console.log(response);
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