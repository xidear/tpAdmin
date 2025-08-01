<template>
  <div class="tree-display">
    <el-tree
      :data="treeData"
      :props="treeProps"
      :expand-on-click-node="false"
      :default-expand-all="true"
      v-if="treeData.length"
    />
    <span v-else>-</span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  value: string | any[]; // 可以是JSON字符串或树形数组
  options?: { 
    labelKey?: string; // 标签字段名
    childrenKey?: string; // 子节点字段名
  };
}>();

// 树形结构配置
const treeProps = computed(() => ({
  label: props.options?.labelKey || 'label',
  children: props.options?.childrenKey || 'children'
}));

// 解析树形数据
const treeData = computed(() => {
  try {
    if (!props.value) return [];
    
    const data = typeof props.value === 'string' 
      ? JSON.parse(props.value) 
      : props.value;
      
    return Array.isArray(data) ? data : [data];
  } catch (e) {
    return [{ [treeProps.value.label]: '解析错误', children: [{ [treeProps.value.label]: '无效的树形结构格式' }] }];
  }
});
</script>

<style scoped>
.tree-display {
  max-height: 400px;
  overflow-y: auto;
  padding: 8px 0;
}

:deep(.el-tree-node__content) {
  padding: 4px 0;
}
</style>
