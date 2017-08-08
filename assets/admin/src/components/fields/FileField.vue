<template>
    <div class="field">
        <label>{{ label }}</label>

        <div v-if="selection">
            <div class="field">
                <div class="ui buttons">
                    <a :href="'/uploads/' + selection" class="ui button" target="_blank">{{ selection }}</a>
                    <div class="ui primary icon button" @click="onOpenFileManager">
                        <i class="folder icon"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="ui primary left labeled icon button" v-else-if="!openFileManager" @click="onOpenFileManager">
            <i class="folder icon"></i>
            Choose file
        </div>

        <file-manager v-else @input="onSelectFile"></file-manager>
    </div>
</template>

<script>
    import FileManager from '@/components/fields/helpers/FileManager';

    export default {
        name: 'file-field',

        props: [
            'value',
            'name',
            'label',
            'options'
        ],

        components: {
            FileManager
        },

        data() {
            return {
                openFileManager: false,
                selection: this.value || null
            }
        },

        methods: {
            onOpenFileManager() {
                this.selection = null;
                this.openFileManager = true;
            },

            onSelectFile(file) {
                this.selection = file;
                this.$emit('input', this.selection);
            },

            onDeselectFile() {
                this.selection = null;
            },
        }
    }
</script>
