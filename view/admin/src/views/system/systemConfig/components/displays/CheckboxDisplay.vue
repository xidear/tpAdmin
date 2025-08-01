<template>
  <div class="checkbox-display">
    <template v-if="displayValues.length">
      <el-tag 
        size="small" 
        v-for="(item, index) in displayValues" 
        :key="index"
        class="checkbox-tag"
      >
        {{ item }}
      </el-tag>
    </template>
    <span v-else>-</span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  value: any;
  options?: Array<{label: string; value: any}>;
}>();

// 获取显示文本列表
const displayValues = computed(() => {
  if (!props.options || !props.options.length || !props.value) return [];
  
  // 确保值是数组
  const values = Array.isArray(props.value) ? props.value : [props.value];
  
  // 匹配选项文本
  return values.map(val => {
    const option = props.options.find(item => item.value === val);
    return option ? option.label : String(val);
  });
});
</script>

<style scoped>
.checkbox-display {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.checkbox-tag {
  background-color: #f0f9eb;
  color: #52c41a;
  border-color: #b7eb8f;
}
</style>
