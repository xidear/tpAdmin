<template>
  <div class="json-display">
    <pre>{{ formattedJson }}</pre>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  value: string | object;
}>();

// 格式化JSON
const formattedJson = computed(() => {
  try {
    const jsonData = typeof props.value === 'string'
      ? JSON.parse(props.value)
      : props.value;
    return JSON.stringify(jsonData, null, 2);
  } catch (e) {
    return props.value || 'Invalid JSON';
  }
});
</script>

<style scoped>
.json-display {
  background-color: #f5f7fa;
  border-radius: 4px;
}

pre {
  margin: 0;
  padding: 12px;
  overflow-x: auto;
  color: #333;
  font-family: monospace;
}
</style>
