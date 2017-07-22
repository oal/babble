<template>
    <div class="field">
        <label>{{ label }}</label>

        <file-manager v-if="!selection" @input="onSelectFile"></file-manager>
        <div v-else>
            <div class="field">
                <div class="ui fluid card">
                    <div class="image">
                        <img :src="croppedImage" v-if="croppedImage" class="ui image">
                        <image-cropper v-else :src="'/uploads/' + selection" :width="width"
                                       :height="height" @crop="onCrop"></image-cropper>
                    </div>
                    <div class="extra content" v-if="croppedImage">
                        <a class="right floated" @click="onDeselectFile">
                            <i class="folder icon"></i>
                            Choose another file
                        </a>
                        <a @click="onReCrop">
                            <i class="crop icon"></i>
                            Re-crop
                        </a>
                    </div>
                    <div class="extra content" v-else-if="selection">
                        <a class="right floated" @click="onDeselectFile">
                            <i class="folder icon"></i>
                            Choose another file
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import FileManager from '@/components/fields/helpers/FileManager';
    import ImageCropper from '@/components/fields/helpers/ImageCropper';

    export default {
        props: [
            'value',
            'name',
            'label',
            'options'
        ],

        components: {
            FileManager,
            ImageCropper
        },

        data() {
            let uncachedURL = null;
            if (this.value.url) {
                uncachedURL = this.value.url + '?' + ((Math.random() * 99999).toString(16));
            }
            return {
                'selection': this.value.filename || null,
                'croppedImage': uncachedURL,
            }
        },

        methods: {
            onSelectFile(file) {
                this.selection = file;
            },

            onDeselectFile() {
                this.selection = null;
                this.croppedImage = null;
            },

            onReCrop() {
                this.croppedImage = null;
            },

            onCrop(previewCanvas, cropData) {
                previewCanvas.toBlob(blob => {
                    let reader = new FileReader();
                    reader.onload = () => {
                        this.croppedImage = reader.result;
                        this.$emit('input', {
                            filename: this.selection,
                            crop: cropData
                        });
                    };
                    reader.readAsDataURL(blob);
                });
            }
        },

        computed: {
            width() {
                if (this.options && this.options.width) return this.options.width;
                return 100;
            },
            height() {
                if (this.options && this.options.height) return this.options.height;
                return 100;
            }
        }
    }
</script>