<template>
    <div class="field">
        <component :is="type + '-field'" :value="value"
                   @input="onFieldInput"
                   :label="label"
                   :name="name"
                   :options="options" v-if="hasTypeComponent()"></component>
        <div class="field" v-else>
            <label>{{ field.name }}</label>
            <div class="ui visible error message">
                No component registered for field type "{{ type }}".
            </div>
        </div>
    </div>
</template>

<script>
    import {camelCase, upperFirst} from 'lodash';

    import TextField from '@/components/fields/TextField';
    import BooleanField from '@/components/fields/BooleanField';
    import DatetimeField from '@/components/fields/DatetimeField';
    import FileField from '@/components/fields/FileField';
    import ImageField from '@/components/fields/ImageField';
    import PasswordField from '@/components/fields/PasswordField';
    import ListField from '@/components/fields/ListField';

    export default {
        name: 'field',

        props: [
            'type',
            'label',
            'name',
            'value',
            'options'
        ],

        components: {
            BooleanField,
            DatetimeField,
            TextField,
            FileField,
            ImageField,
            PasswordField,
            ListField
        },

        methods: {
            hasTypeComponent() {
                let componentName = upperFirst(`${camelCase(this.type)}Field`);
                return !!this.$options.components[componentName];
            },
            onFieldInput(value) {
                this.$emit('input', value);
            },
        }
    }
</script>