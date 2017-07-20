<template>
    <div class="field">
        <label>{{ label }}</label>
        <div class="ui left labeled primary icon button">
            <i class="grid layout icon"></i>
            Choose image
        </div>

        <div class="ui left labeled green icon button">
            <i class="upload icon"></i>
            Upload image
        </div>

        <div class="ui segment">
            <div class="files-top">
                <div class="ui breadcrumb">
                    <a class="section" @click="popToDir(0)">Uploads</a>
                    <span v-for="dir, $index in path">
                        <span class="divider">/</span>
                        <a class="section" @click="popToDir($index+1)">{{ dir }}</a>
                    </span>
                </div>
                <i class="ui large grey window close icon"></i>
            </div>

            <div class="files">
                <div class="file" v-for="file in files">
                    <img :src="getURL(file)" alt="" v-if="file.type === 'file'">
                    <div class="dir" v-else @click="goToDir(file)">
                        <span>
                            <i class="huge folder outline icon"></i>
                            {{ file.name }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: [
            'value',
            'name',
            'label'
        ],

        data() {
            return {
                'path': [],
                'files': []
            }
        },

        created() {
            this.loadFiles();
        },

        watch: {
            path() {
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
                return 'http://localhost:8000/uploads/' + this.path.join('/') + '/' + file.name;
            },

            goToDir(dir) {
                if (dir.type !== 'dir') return false;
                this.path.push(dir.name);
            },

            popToDir(index) {
                if (index === this.path.length) return; // No change.

                this.path = this.path.slice(0, index);
            }
        },
    }
</script>

<style lang="scss" type="text/scss" scoped>
    .segment {
        padding-bottom: 7px;
    }
    .files-top {
        padding: 0.5rem 0.5rem 0 0.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .files {
        display: flex;
        flex-wrap: wrap;
        margin-top: 1rem;
        padding-top: 1rem;
        overflow-y: auto;
        max-height: 400px;
        border-top: 1px solid #eee;
    }

    .file {
        flex: 0 1 175px;
        height: 175px;
        border: 1px solid #eee;
        background-color: #fafafa;
        margin: 0 0.5rem 1rem 0.5rem;
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
</style>