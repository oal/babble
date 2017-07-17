<template>
    <div>
        <div class="ui loading basic segment" v-if="loading"></div>
        <div v-else>
            <h1>
                {{ model.name_plural }}

                <router-link v-bind:to="{name: 'Create', params: {modelType: model.type}}"
                             class="ui right floated primary left labeled icon button">
                    <i class="add icon"></i>
                    New {{ model.name }}
                </router-link>
            </h1>
            <table class="ui table">
                <thead>
                <tr>
                    <th v-for="column in listDisplay">
                        {{ column.name }}
                    </th>
                    <th>&nbsp;</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="record in models">
                    <td v-for="column in listDisplay">
                        <component v-bind:is="column.type + '-preview'"
                                   v-bind:value="getColumnValue(column, record)"
                                   v-if="hasPreviewComponent(column)"></component>
                        <div v-else>{{ getColumnValue(column, record) }}</div>
                    </td>

                    <td class="collapsing">
                        <router-link v-bind:to="{name: 'Edit', params: {modelType: model.type, id: record.id}}"
                                     class="ui green left labeled icon button">
                            <i class="edit icon"></i>
                            Edit
                        </router-link>
                        <a href="#" class="ui red left labeled icon button">
                            <i class="remove icon"></i>
                            Delete
                        </a>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
    import {camelCase, upperFirst} from 'lodash';
    import BooleanPreview from '@/components/previews/BooleanPreview';
    import DatetimePreview from '@/components/previews/DatetimePreview';

    export default {
        name: 'panel',

        components: {
            BooleanPreview,
            DatetimePreview
        },

        props: [
            'modelType'
        ],

        data () {
            return {
                loading: true,
                model: {},
                models: [],
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

                let modelPromise = this.$http.options('/' + this.modelType).then(response => {
                    this.model = response.data;
                });
                let recordsPromise = this.$http.get('/' + this.modelType).then(response => {
                    this.models = response.data;
                });

                Promise.all([modelPromise, recordsPromise]).then(() => {
                    this.loading = false;
                });
            },
            getColumnValue(column, record) {
                return record[column.key];
            },
            hasPreviewComponent(column) {
                let componentName = upperFirst(`${camelCase(column.type)}Preview`);
                return !!this.$options.components[componentName];
            }
        },

        computed: {
            options: function () {
                let options = this.model.options.admin || {};
                if (!options.list_display) options.list_display = [this.model.fields[0].key];

                return options;
            },
            fieldsByKey() {
                let fields = {};
                this.model.fields.forEach(field => fields[field.key] = field);
                return fields;
            },
            listDisplay() {
                return this.options['list_display'].map(fieldKey => this.fieldsByKey[fieldKey]);
            },
        }
    }
</script>

<style>
</style>