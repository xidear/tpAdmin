<template>
  <div class="key-value-display">
    <el-table
      :data="keyValueData"
      border
      size="small"
      :show-header="false"
      v-if="keyValueData.length"
    >
      <el-table-column prop="key" label="键" width="120" />
      <el-table-column prop="value" label="值" />
    </el-table>
    <span v-else>-</span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  value: string | object; // 可以是JSON字符串或对象
}>();

// 解析键值对数据
const keyValueData = computed(() => {
  try {
    const data = typeof props.value === 'string' 
      ? JSON.parse(props.value) 
      : props.value;
      
    // 转换为数组格式
    if (typeof data === 'object' && data !== null) {
      return Object.entries(data).map(([key, value]) => ({
        key,
        value: typeof value === 'object' ? JSON.stringify(value) : String(value)
      }));
    }
    return [];
  } catch (e) {
    return [{ key: '解析错误', value: '无效的键值对格式' }];
  }
});
</script>

<style scoped>
.key-value-display {
  max-height: 400px;
  overflow-y: auto;
}

:deep(.el-table) {
  width: 100%;
}

:deep(.el-table td) {
  padding: 6px 12px;
}
</style>
