<template>
    <div>
        <div class="ui loading basic segment" v-if="loading"></div>
        <div v-else>
            <h1>
                <span v-if="isNew">Edit</span>
                <span v-else>New</span>
                {{ model.name }}
            </h1>

            <div class="ui form">
                <div class="field">
                    <label>ID</label>
                    <input type="text" v-model="changedId">
                </div>

                <div class="field" v-for="value, key in data">
                    <label>{{ getFieldName(key) }}</label>
                    <input type="text" v-bind:value="value" v-on:input="onFieldInput($event, key)">
                </div>

                <div class="ui green left labeled icon button" v-on:click="save">
                    <i class="save icon"></i>
                    Save
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'panel',

        props: [
            'modelType',
            'id'
        ],

        data () {
            return {
                isNew: !!this.id,
                changedId: this.id,
                loading: true,
                model: {},
                data: {},
            }
        },

        created: function () {
            this.fetchData();
        },

        watch: {
            '$route': 'fetchData'
        },

        methods: {
            fetchData() {
                this.loading = true;

                if (!this.id) {
                    this.changedId = null;
                    this.data = {};
                }

                let promises = [];

                let modelPromise = this.$http.options('/' + this.modelType).then(response => {
                    this.model = response.data;
                });
                promises.push(modelPromise);

                if (this.id) {
                    let dataPromise = this.$http.get('/' + this.modelType + '/' + this.id).then(response => {
                        this.data = response.data;
                    });
                    promises.push(dataPromise);
                }

                Promise.all(promises).then(() => {
                    this.model.fields.forEach(field => {
                        if (!this.data[field.key]) {
                            this.data[field.key] = '';
                        }
                    });
                    this.loading = false;
                });
            },
            save() {
                this.loading = true;
                let request;
                if (this.id) request = this.$http.put;
                else request = this.$http.post;

                let data = this.data;
                if (this.id !== this.changedId) {
                    data = Object.assign({}, this.data, {
                        '_old_id': this.id
                    });
                }

                request('/' + this.modelType + '/' + this.changedId, data).then(response => {
                    let location = {
                        name: 'Edit',
                        params: {
                            modelType: this.model.type,
                            id: this.changedId
                        }
                    };
                    this.$router.push(location);
                    this.loading = false;
                }).catch(_ => {
                    this.loading = false
                });
            },
            onFieldInput(event, key) {
                this.data[key] = event.target.value;
            },
            getFieldName(key) {
                return this.model.fields.filter(field => field.key === key)[0].name;
            }
        }
    }
</script>

<style>
</style>