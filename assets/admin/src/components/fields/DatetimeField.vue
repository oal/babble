<template>
    <div class="fields">
        <div class="field">
            <label>{{ label }}</label>
            <input type="date" v-bind:value="date" v-on:input="onInput" ref="date">
        </div>
        <div class="field">
            <label>&nbsp;</label>
            <input type="time" v-bind:value="time" v-on:input="onInput" ref="time">
        </div>
    </div>
</template>

<script>
    import {get} from 'lodash';
    import moment from 'moment';

    const dateFormat = 'YYYY-MM-DD';
    const timeFormat = 'hh:mm';

    export default {
        props: [
            'value',
            'label'
        ],

        methods: {
            onInput(event) {
                let date = this.$refs.date.value;
                let time = this.$refs.time.value;
                this.$emit('input', `${date} ${time}`);
            }
        },

        computed: {
            datetime() {
                return moment(this.value, `${dateFormat} ${timeFormat}`);
            },
            date() {
                return this.datetime.format(dateFormat);
            },
            time() {
                return this.datetime.format(timeFormat);
            }
        }
    }
</script>