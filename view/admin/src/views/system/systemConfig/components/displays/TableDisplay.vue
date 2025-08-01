<template>
  <div class="table-display">
    <el-table
      :data="tableData"
      border
      size="small"
      v-if="tableData.length"
    >
      <el-table-column 
        v-for="(column, index) in columns" 
        :key="index"
        :prop="column.prop"
        :label="column.label"
        :width="column.width || 'auto'"
      />
    </el-table>
    <span v-else>-</span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  value: string | any[]; // 可以是JSON字符串或数组
  options?: { columns?: Array<{ label: string; prop: string; width?: number }> }; // 表格列配置
}>();

// 解析表格数据
const tableData = computed(() => {
  try {
    if (!props.value) return [];
    
    return typeof props.value === 'string' 
      ? JSON.parse(props.value) 
      : Array.isArray(props.value) 
        ? props.value 
        : [];
  } catch (e) {
    return [{ error: '解析错误', message: '无效的表格数据格式' }];
  }
});

// 获取表格列配置
const columns = computed(() => {
  // 如果有配置的列信息，使用配置
  if (props.options?.columns && props.options.columns.length) {
    return props.options.columns;
  }
  
  // 否则自动从数据中提取列
  if (tableData.value.length) {
    const firstRow = tableData.value[0];
    return Object.keys(firstRow).map(key => ({
      label: key,
      prop: key
    }));
  }
  
  return [];
});
</script>

<style scoped>
.table-display {
  max-height: 400px;
  overflow-y: auto;
}

:deep(.el-table) {
  width: 100%;
}
</style>
