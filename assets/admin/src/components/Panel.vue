<template>
    <div id="panel">
        <header id="top">Babble CMS Admin</header>
        <div id="main">
            <aside id="sidebar">
                <ul>
                    <li v-for="post in posts">{{ post.title }}</li>
                </ul>
            </aside>
            <article id="content"></article>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'panel',
        mounted: function() {
            this.$http.get('/Post').then(response => {
                console.log(response);
                this.posts = response.data;
            });
        },
        data () {
            return {
                posts: [],
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