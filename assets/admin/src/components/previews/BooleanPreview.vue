<template>
    <div>
        <i class="green checkmark icon" v-if="value"></i>
        <i class="red remove icon" v-else></i>
    </div>
</template>

<script>
    export default {

        props: [
            'value'
        ]

    }
</script>