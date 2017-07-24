<template>
    <div class="field">
        <div class="two fields">
            <div class="field">
                <label>{{ label }}</label>
                <input type="password" v-bind:value="value" v-on:input="onInput">
            </div>
            <div class="field">
                <label>Confirm</label>
                <input type="password" v-bind:value="confirmValue">
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: [
            'label'
        ],

        data() {
            return {
                value: '',
                confirmValue: ''
            }
        },

        methods: {
            onInput(event) {
                let value = event.target.value;
                this.$emit('input', value);
            }
        }
    }
</script>