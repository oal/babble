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
                    <input v-model="changedId" pattern="[A-Za-z0-9-]+" required>
                </div>

                <div class="field" v-for="field in model.fields" v-bind:key="field.key">
                    <component v-bind:is="field.type + '-field'" v-bind:value="data[field.key]"
                               v-on:input="onFieldInput(field.key, $event)"
                               v-bind:name="field.key"
                               v-bind:label="field.name"
                               v-bind:options="field.options" v-if="hasFieldComponent(field)"></component>
                    <div class="field" v-else>
                        <label>{{ field.name }}</label>
                        <div class="ui visible error message">
                            No component registered for field type "{{ field.type }}".
                        </div>
                    </div>
                </div>

                <div class="ui divider"></div>

                <div class="ui green left labeled icon button" v-on:click="save">
                    <i class="save icon"></i>
                    Save
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import {upperFirst, camelCase} from 'lodash';
    import TextField from '@/components/fields/TextField';
    import BooleanField from '@/components/fields/BooleanField';
    import DatetimeField from '@/components/fields/DatetimeField';
    import ImageField from '@/components/fields/ImageField';
    import PasswordField from '@/components/fields/PasswordField';

    export default {
        name: 'panel',

        components: {
            BooleanField,
            DatetimeField,
            TextField,
            ImageField,
            PasswordField
        },

        props: [
            'modelType',
            'id'
        ],

        data() {
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

                let modelPromise = this.$http.options('/models/' + this.modelType).then(response => {
                    this.model = response.data;
                });
                promises.push(modelPromise);

                if (this.id) {
                    let dataPromise = this.$http.get('/models/' + this.modelType + '/' + this.id).then(response => {
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
                    data = {
                        ...this.data,
                        '_old_id': this.id
                    };
                }

                request('/models/' + this.modelType + '/' + this.changedId, data).then(response => {
                    // Redirect if page didn't already have an ID. Otherwise, update data.
                    if (this.id !== response.data.id) {
                        let location = {
                            name: 'Edit',
                            params: {
                                modelType: this.model.type,
                                id: this.changedId
                            }
                        };
                        this.$router.push(location);
                    } else {
                        Object.keys(this.data).forEach(field => {
                            this.data[field] = response.data[field];
                        });
                    }

                    this.loading = false;
                }).catch(_ => {
                    this.loading = false
                });
            },
            onFieldInput(key, value) {
                this.data[key] = value;
            },
            getFieldName(key) {
                return this.model.fields.filter(field => field.key === key)[0].name;
            },
            hasFieldComponent(field) {
                let componentName = upperFirst(`${camelCase(field.type)}Field`);
                return !!this.$options.components[componentName];
            }
        }
    }
</script>

<style>
</style>