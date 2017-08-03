<template>
    <div id="login">
        <div class="ui basic padded segment">
            <h2 class="ui header center aligned">
                {{ $t('login') }}
            </h2>
            <div class="ui raised segments">
                <div class="ui padded segment">
                    <div class="ui form" @keypress.enter="login">
                        <div class="field">
                            <label for="username">{{ $t('username') }}</label>
                            <input id="username" name="username" v-model="username">
                        </div>
                        <div class="field">
                            <label for="password">{{ $t('password') }}</label>
                            <input id="password" type="password" name="password" v-model="password">
                        </div>
                    </div>
                </div>
                <div class="ui center aligned secondary segment">
                    <div class="ui right labeled primary icon button" @click="login" :class="{loading: loading}">
                        <i class="right arrow icon"></i>
                        {{ $t('login') }}
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
                    this.$root.user = response.data;
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