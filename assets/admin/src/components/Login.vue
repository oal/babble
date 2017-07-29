<template>
    <div id="login">
        <div class="ui basic padded segment">
            <h2 class="ui header center aligned">Log in</h2>
            <div class="ui raised segments">
                <div class="ui padded segment">
                    <form class="ui form">
                        <div class="field">
                            <label for="username">Username</label>
                            <input id="username" name="username" v-model="username">
                        </div>
                        <div class="field">
                            <label for="password">Password</label>
                            <input id="password" type="password" name="password" v-model="password">
                        </div>
                    </form>
                </div>
                <div class="ui center aligned secondary segment">
                    <div class="ui right labeled primary icon button" @click="login" :class="{loading: loading}">
                        <i class="right arrow icon"></i>
                        Log in
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                loading: false,
                username: '',
                password: ''
            }
        },

        methods: {
            login() {
                if (this.loading) return;

                this.loading = true;
                let auth = {
                    username: this.username,
                    password: this.password,
                };
                this.$http.get('/models/User/' + this.username, {
                    auth: auth
                }).then(response => {
                    this.$http.defaults.auth = auth;
                    this.$router.replace({name: 'Panel'});
                    this.loading = false;
                }).catch(response => {
                    console.error(response.data);
                    this.loading = false;
                });
            }
        }
    }
</script>

<style scoped>
    #login {
        display: flex;
        flex-grow: 1;
        background-color: #fcfcfc;
    }

    .segment {
        margin: auto;
        align-self: center;
    }
</style>