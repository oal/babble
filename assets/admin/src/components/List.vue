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
                    <th v-for="column in options.list_display">
                        {{ getColumnName(column) }}
                    </th>
                    <th>&nbsp;</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="instance in models">
                    <td v-for="column in options.list_display">
                        {{ getColumn(column, instance) }}
                    </td>

                    <td class="collapsing">
                        <router-link v-bind:to="{name: 'Edit', params: {modelType: model.type, id: instance.id}}"
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
    export default {
        name: 'panel',

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
                let instancesPromise = this.$http.get('/' + this.modelType).then(response => {
                    this.models = response.data;
                });

                Promise.all([modelPromise, instancesPromise]).then(() => {
                    this.loading = false;
                });
            },
            getColumnName(column) {
                let name = this.model.fields.filter(field => field.key === column)[0].name;
                return name;
            },
            getColumn(column, modelInstance) {
                return modelInstance[column];
            }
        },

        computed: {
            options: function () {
                let options = this.model.options.admin || {};
                if (!options.list_display) options.list_display = [this.model.fields[0].key];

                return options;
            }
        }
    }
</script>

<style>
</style>