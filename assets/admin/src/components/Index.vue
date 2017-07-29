<template>
    <div>
        <h1 class="ui header">Manage website content</h1>
        <div class="ui cards">
            <div class="ui card" v-for="model in $parent.models">
                <div class="center aligned content" style="padding: 3rem 1rem">
                    <i class="huge icon" :class="model.options.admin.icon" v-if="model.options && model.options.admin && model.options.admin.icon"></i>
                </div>
                <div class="content">
                    <router-link :to="{name: 'Create', params: {modelType: model.type}}" class="right floated">
                        <i class="green add icon"></i>
                    </router-link>
                    <router-link :to="{name: 'List', params: {modelType: model.type}}" class="header">{{ model.name_plural }}</router-link>
                </div>
            </div>
            </div>
        </div>
    </div>
</template>