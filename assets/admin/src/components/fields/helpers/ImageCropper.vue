<template>
    <div>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/0.8.1/cropper.css">
        <img ref="image" :src="src">

        <div class="ui basic segment">
            <div class="ui menu">
                <a class="item" @click="onConfirmCrop">
                    <i class="crop icon"></i>
                    Crop
                </a>
            </div>
        </div>
    </div>
</template>

<script>
    import Cropper from 'cropperjs';

    export default {
        props: [
            'src',
            'width',
            'height'
        ],

        data() {
            return {
                cropper: null,
            }
        },

        mounted() {
            // setTimeout so that the cropper gets initialized to the correct size.
            setTimeout(() => {
                this.cropper = new Cropper(this.$refs.image, {
                    rotatable: false,
                    zoomable: false,
                    scalable: false,
                    viewMode: 1,
                    aspectRatio: this.aspectRatio
                });
            }, 0);
        },

        beforeDestroy() {
            this.cropper.destroy();
        },

        methods: {
            onConfirmCrop() {
                let canvas = this.cropper.getCroppedCanvas({
                    width: this.width,
                    height: this.height
                });
                this.$emit('crop', canvas, this.cropper.getData());
            }
        },

        computed: {
            aspectRatio() {
                return this.width / this.height;
            }
        }
    }
</script>

<style scoped>
    .segment {
        margin-top: 0;
    }
</style>