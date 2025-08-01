<template>
  <div class="text-display">
    <pre v-if="isMultiLine">{{ value }}</pre>
    <span v-else>{{ value ?? '-' }}</span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  value: any;
  type: number;
}>();

// 判断是否为多行文本
const isMultiLine = computed(() => {
  return props.type === 2; // TEXTAREA类型
});
</script>

<style scoped>
.text-display {
  white-space: pre-wrap;
  word-break: break-all;
}

pre {
  margin: 0;
  padding: 8px;
  background-color: #f5f7fa;
  border-radius: 4px;
  overflow-x: auto;
}
</style>
