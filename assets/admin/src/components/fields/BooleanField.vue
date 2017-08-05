<template>
    <div class="field">
        <div class="ui checkbox" v-on:click="onInput">
            <input type="checkbox" v-bind:checked="value" v-on:input="onInput" v-bind:id="fieldId" ref="checkbox">
            <label v-bind:for="fieldId">{{ label }}</label>
        </div>
    </div>
</template>

<script>
    import {get} from 'lodash';
    export default {
        name: 'boolean-field',

        props: [
            'value',
            'name',
            'label'
        ],

        methods: {
            onInput() {
                let isChecked = this.$refs.checkbox.checked;
                console.log();
                this.$emit('input', isChecked);
            }
        },

        computed: {
            fieldId() {
                return `field_${this.name}`;
            }
        }
    }
</script>