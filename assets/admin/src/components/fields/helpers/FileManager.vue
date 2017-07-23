<template>
    <div class="ui fluid card" v-if="!selection">
        <div class="ui basic very padded loading segment" v-if="progress < 100"></div>
        <div class="content">
            <div class="ui breadcrumb">
                <a class="section" @click="popToDir(0)">Uploads</a>
                <span v-for="dir, $index in path">
                        <span class="divider">/</span>
                        <strong v-if="$index === path.length-1">
                            {{ dir }}
                            <a @click="onRenameDirectory($index)"><i class="pencil icon"></i></a>
                        </strong>
                        <a @click="popToDir($index+1)" v-else>{{ dir }}</a>
                    </span>
            </div>
        </div>

        <div class="content">
            <div class="ui cards files">
                <div class="ui card file" v-for="file in files">
                    <div class="preview content">
                        <div class="image" :style="'background-image: url(/uploads/' + getURL(file) + ')'"
                             v-if="file.type === 'file'"
                             @click="selectFile(file)"></div>
                        <div class="dir" v-else @click="goToDir(file)">
                            <span>
                                <i class="huge folder outline icon"></i>
                                {{ file.name }}
                            </span>
                        </div>
                    </div>
                </div>
                <em v-if="files.length === 0" style="padding: 1rem 1rem 3rem 1rem">No files found.</em>
            </div>
        </div>

        <div class="extra content">
            <a class="right floated" @click="onCreateDirectory">
                <i class="folder icon"></i>
                Create new directory
            </a>

            <input type="file" multiple :id="uploadId" @change="onUploadChange"
                   style="position: absolute; top: -99999px">
            <a>
                <label class="upload-label" :for="uploadId">
                    <i class="add icon"></i>
                    Upload file
                </label>
            </a>
        </div>
        <div class="ui bottom attached green progress" v-if="this.progress < 100">
            <div class="bar" :style="'width:' + this.progress + '%'"></div>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                'path': [],
                'files': [],
                'selection': null,
                'progress': 100
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
                this.loading = true;
                let path = '/files';
                if (this.path.length) {
                    path += '/' + this.path.join('/');
                }
                this.$http.get(path).then(response => {
                    this.files = response.data;
                    this.loading = false;
                });
            },

            getURL(file) {
                return [...this.path, file.name].join('/');
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
                for (let i = 0; i < files.length; i++) {
                    formData.append('file-' + i, files[i]);
                }

                let apiPath = ['/files', ...this.path].join('/');
                this.$http.post(apiPath, formData, {
                    onUploadProgress: event => {
                        this.progress = (event.loaded / event.total) * 100;
                    }
                }).then(response => {
                    event.target.value = '';
                    this.loadFiles();
                }).catch(response => {
                    console.log('fail');
                });
            },

            onCreateDirectory() {
                let name = prompt('Directory name:');
                let apiPath = ['/files', ...this.path].join('/');

                this.$http.post(apiPath, {directory: name}).then(response => {
                    this.loadFiles();
                }).catch(response => {
                    console.log('fail');
                });
            },

            onRenameDirectory(index) {
                let name = prompt('New directory name:');
                let apiPath = ['/files', ...this.path.slice(0, index+1)].join('/');

                this.$http.put(apiPath, {directory: name}).then(response => {
                    this.path = this.path.slice(0, index); // Move up to the directory that contains the renamed directory.
                    this.loadFiles();
                }).catch(response => {
                    console.log('fail');
                });
                console.log('Rename ', this.path[index], apiPath);
            }
        },

        computed: {
            uploadId() {
                return 'upload-' + ((Math.random() * 99999) | 0).toString(16);
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
        border-radius: 2px;
        cursor: pointer;

        .preview {
            display: flex;

            &.content {
                padding: 0;
            }
        }

        .image, .dir {
            flex-grow: 1;
            border-radius: 4px;
        }

        .image {
            background-size: cover;
            background-position: center;
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

    .card {
        position: relative;
    }

    .loading {
        position: absolute !important;
        height: 100%;
        width: 100%;
    }
</style>