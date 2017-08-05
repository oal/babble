<template>
    <div class="field">
        <label>{{ label }}</label>
        <div class="ui top attached menu">
            <div class="item" v-for="block in options.blocks" @click="addBlock(block.type)">
                <i class="add icon"></i> {{ block.name }}
            </div>
        </div>
        <div class="ui attached segment" v-for="(block, $index) in blocksWithFields">
            <strong>{{ getBlockName(block.type) }}</strong>
            <div class="ui tiny right floated red icon button">
                <i class="remove icon"></i>
            </div>

            <!--<div v-for="field in block.fields"-->
                 <!--:key="field.key">{{ field }}</div>-->

            <field :type="field.type"
                   :label="field.name"
                   :name="field.key"
                   :options="field.options"

                   :value="blocks[$index].value[field.key]"
                   @input="onFieldInput($index, field.key, $event)"

                   v-for="field in block.fields"
                   :key="field.key">
            </field>
        </div>
    </div>
</template>

<script>
    import {get} from 'lodash';

    import Field from '@/components/fields/Field';

    export default {
        props: [
            'value',
            'name',
            'label',
            'options'
        ],

        beforeCreate: function () {
            // Relevant: https://vuejs.org/v2/guide/components.html#Recursive-Components
            this.$options.components.Field = require('./Field.vue')
        },

        data() {
            return {
                blocks: []
            }
        },

        methods: {
            getBlock(blockType) {
                return this.options.blocks.filter(block => block.type === blockType)[0];
            },
            getBlockName(blockType) {
                return this.getBlock(blockType).name;
            },
            addBlock(type) {
                this.blocks.push({
                    type: type,
                    value: {},
                })
            },
            onFieldInput(blockIndex, key, value) {
                this.blocks[blockIndex].value[key] = value;
                this.$emit('input', [...this.blocks]);
            }
        },

        computed: {
            blocksWithFields() {
                return this.blocks.map(blockData => {
                    let block = this.getBlock(blockData.type);
                    return {...blockData, fields: block.fields};
                })
            }
        }
    }
</script>