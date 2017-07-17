<template>
    <div>
        <input type="text" v-if="!getOption('multiline')" v-bind:value="value" v-on:input="onInput">
        <textarea v-else cols="30" rows="10" v-bind:value="value" v-on:input="onInput"></textarea>
    </div>
</template>

<script>
    import {get} from 'lodash';
    export default {
        props: [
            'options',
            'value'
        ],

        data: function () {
            return {
                val: this.value
            }
        },

        created: function () {
        },

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