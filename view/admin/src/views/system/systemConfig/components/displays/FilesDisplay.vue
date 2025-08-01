<template>
  <div class="files-display">
    <template v-if="files.length">
      <el-divider content="文件列表" />
      <el-list>
        <el-list-item 
          v-for="(file, index) in files" 
          :key="index"
          class="file-item"
        >
          <FileDisplay :value="file" />
        </el-list-item>
      </el-list>
    </template>
    <span v-else>-</span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import FileDisplay from './FileDisplay.vue';

const props = defineProps<{
  value: string | string[]; // 可以是JSON字符串或文件URL数组
}>();

// 解析文件列表
const files = computed(() => {
  try {
    if (!props.value) return [];
    
    if (typeof props.value === 'string') {
      // 尝试解析为JSON数组
      const parsed = JSON.parse(props.value);
      return Array.isArray(parsed) ? parsed : [props.value];
    } else if (Array.isArray(props.value)) {
      return props.value;
    }
    
    return [];
  } catch (e) {
    return [props.value]; // 解析失败时直接作为单个文件处理
  }
});
</script>

<style scoped>
.files-display {
  max-height: 400px;
  overflow-y: auto;
}

:deep(.el-divider__text) {
  font-size: 14px;
  color: #666;
  font-weight: normal;
}

.file-item {
  padding: 6px 0;
  border-bottom: 1px dashed #f0f0f0;
}

.file-item:last-child {
  border-bottom: none;
}
</style>
