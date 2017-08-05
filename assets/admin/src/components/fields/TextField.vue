<template>
    <div class="field">
        <label>{{ label }}</label>
        <input type="text" v-if="!getOption('multiline')" v-bind:value="value" v-on:input="onInput">
        <textarea v-else cols="30" rows="10" v-bind:value="value" v-on:input="onInput"></textarea>
    </div>
</template>

<script>
    import {get} from 'lodash';
    export default {
        name: 'text-field',

        props: [
            'options',
            'value',
            'label'
        ],

        methods: {
            getOption(path) {
                return get(this.options, path, null)
            },
            onInput(event) {
                let value = event.target.value;
                this.$emit('input', value);
            }
        }
    }
</script>