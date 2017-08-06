<template>
    <div>
        <div class="ui loading basic segment" v-if="loading"></div>
        <div v-else>
            <h1>
                <span v-if="isNew">{{ $t('edit') }}</span>
                <span v-else>{{ $t('new') }}</span>
                {{ model.name }}
            </h1>

            <div class="ui form">
                <div class="ui grid">
                    <div class="sixteen wide field">
                        <label>ID</label>
                        <input v-model="changedId" pattern="[A-Za-z0-9-]+" required>
                    </div>

                    <field :type="field.type"
                           :label="field.name"
                           :name="field.key"
                           :options="field.options"
                           :class="field.classes"

                           :value="data[field.key]"
                           @input="onFieldInput(field.key, $event)"

                           v-for="field in processedFields"
                           :key="field.key">
                    </field>

                    <div class="sixteen wide column">
                        <div class="ui divider"></div>

                        <div class="ui green left labeled icon button" v-on:click="save">
                            <i class="save icon"></i>
                            {{ $t('save') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import {upperFirst, camelCase} from 'lodash';
    import Field from '@/components/fields/Field';

    const numberToString = (num) => {
        return [
            'zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight',
            'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen'][num];
    };

    export default {
        name: 'panel',

        components: {
            Field
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
            }
        },

        computed: {
            processedFields() {
                return this.model.fields.map(field => {
                    let adminOptions = field.options['admin'];
                    if (adminOptions && adminOptions['columns']) {
                        field.classes = numberToString(adminOptions['columns']) + ' wide';
                    } else {
                        field.classes = 'sixteen wide';
                    }
                    return field;
                });
            }
        }
    }
</script>

<style>
</style>