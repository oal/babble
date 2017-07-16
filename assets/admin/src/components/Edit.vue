<template>
    <div>
        <div class="ui loading basic segment" v-if="loading"></div>
        <div v-else>
            <h1>
                <span v-if="id">Edit</span>
                <span v-else>New</span>
                {{ model.name }}
            </h1>

            <div class="ui form">
                <div class="field" v-for="value, key in data">
                    <label>{{ getFieldName(key) }}</label>
                    <input type="text" v-model="value">
                </div>

                <div class="ui green left labeled icon button">
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
                this.data = {};

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

            getFieldName(key) {
                return this.model.fields.filter(field => field.key === key)[0].name;
            }
        }
    }
</script>

<style>
</style>