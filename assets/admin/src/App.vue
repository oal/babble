<template>
    <div id="app">
        <router-view></router-view>
    </div>
</template>

<script>
    export default {
        name: 'app'
    }
</script>

<style>
    html, body {
        padding: 0;
        margin: 0;
        display: flex;
        min-height: 100%;
        height: auto;
    }

    body {
        flex-grow: 1;
    }

    #app {
        font-family: 'Avenir', Helvetica, Arial, sans-serif;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;

        display: flex;
        flex-grow: 1;
    }
</style>
