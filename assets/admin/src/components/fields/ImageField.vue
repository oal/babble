<template>
    <div class="field">
        <label>{{ label }}</label>
        <div class="ui left labeled primary icon button">
            <i class="add icon"></i>
            Choose image
        </div>
        <div class="ui segment">
            <div class="files-top">
                <div class="ui breadcrumb">
                    <a class="section">Uploads</a>
                    <div class="divider"> /</div>
                    <a class="section">Store</a>
                    <div class="divider"> /</div>
                    <div class="active section">T-Shirt</div>
                </div>
                <div class="ui tiny icon button">
                    <i class="ui remove icon"></i>
                </div>
            </div>

            <div class="files">
                <img src="https://semantic-ui.com/images/wireframe/white-image.png" alt="" class="file"
                     v-for="image in images">
                <div class="add file"><i class="ui huge add icon"></i></div>
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
                'images': new Array(9)
            }
        }
    }
</script>

<style scoped>
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
        object-fit: contain;

        margin: 0 0.5rem 1rem 0.5rem;
        border: 1px solid #e2e2e2;
        border-radius: 2px;
        display: flex;
    }

    .add.file {
        background-color: #eee;
    }

    .file .icon {
        margin: auto;
        color: #d2d2d2;
        text-shadow: 0 1px 0 #bbb, 0 -1px 0 #bbb, -1px 0 0 #bbb, 1px 0 0 #bbb
    }
</style>