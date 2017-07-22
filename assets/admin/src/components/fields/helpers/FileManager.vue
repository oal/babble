<template>
    <div class="ui fluid card" v-if="!selection">
        <div class="content">
            <div class="ui breadcrumb">
                <a class="section" @click="popToDir(0)">Uploads</a>
                <span v-for="dir, $index in path">
                        <span class="divider">/</span>
                        <a class="section" @click="popToDir($index+1)">{{ dir }}</a>
                    </span>
            </div>
        </div>

        <div class="content">
            <div class="files">
                <div class="file" v-for="file in files">
                    <img :src="'/uploads/' + getURL(file)" alt="" v-if="file.type === 'file'"
                         @click="selectFile(file)">
                    <div class="dir" v-else @click="goToDir(file)">
                        <span>
                            <i class="huge folder outline icon"></i>
                            {{ file.name }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="extra content">
            <a class="right floated">
                <i class="folder icon"></i>
                Create new directory
            </a>
            <input type="file" id="upload" @change="onUploadChange">
            <a>
                <label class="upload-label" for="upload">
                    <i class="add icon"></i>
                    Upload file
                </label>
            </a>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                'path': [],
                'files': [],
                'selection': null
            }
        },

        created() {
            this.loadFiles();
        },

        watch: {
            path() {
                this.files = [];
                this.loadFiles();
            }
        },

        methods: {
            loadFiles() {
                let path = '/files';
                if (this.path.length) {
                    path += '/' + this.path.join('/');
                }
                this.$http.get(path).then(response => {
                    this.files = response.data;
                });
            },
            getURL(file) {
                let relativePath = [...this.path, file.name].join('/');
                return relativePath;
            },

            popToDir(index) {
                if (index === this.path.length) return; // No change.

                this.path = this.path.slice(0, index);
            },

            goToDir(dir) {
                if (dir.type !== 'dir') return false;
                this.path.push(dir.name);
            },

            selectFile(file) {
                if (file.type !== 'file') return false;
                this.selection = this.getURL(file);
                this.$emit('input', this.selection);
            },

            onUploadChange(event) {
                let files = event.target.files;

                let formData = new FormData();
                formData.append('file', files[0]);

                let apiPath = ['/files', ...this.path].join('/');
                this.$http.post(apiPath, formData).then(response => {
                    console.log('success');
                }).catch(response => {
                    console.log('fail');
                });
            }
        }
    }
</script>


<style lang="scss" type="text/scss" scoped>
    .files {
        display: flex;
        flex-wrap: wrap;
        overflow-y: auto;
        max-height: 400px;
    }

    .file {
        flex: 0 1 175px;
        height: 175px;
        border: 1px solid #eee;
        background-color: #fafafa;
        margin: 0.5rem;
        border-radius: 2px;
        display: flex;
        cursor: pointer;

        img {
            object-fit: cover;
            width: 100%;
            height: 100%;
            border-radius: 2px;
        }

        .dir {
            display: flex;
            width: 100%;
            color: #555;
            text-shadow: 0 2px 0 #fff, 0 5px 10px rgba(#000, 0.3);
            transition: all 0.2s;

            span {
                margin: auto;
                font-weight: bold;
                font-size: 120%;
                text-align: center;
            }

            .icon {
                display: block;
            }

            &:hover {
                color: #000;
            }
        }
    }

    .upload-label {
        cursor: pointer;
    }

    #upload {
        position: absolute;
        top: -9999px;
    }
</style>