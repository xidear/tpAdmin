<template>
  <div class="color-display">
    <div 
      v-if="value" 
      class="color-preview" 
      :style="{ backgroundColor: value }"
      :title="value"
    />
    <span class="color-value">{{ value || '-' }}</span>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  value: string; // 16进制颜色值
}>();
</script>

<style scoped>
.color-display {
  display: flex;
  align-items: center;
  gap: 12px;
}

.color-preview {
  width: 32px;
  height: 32px;
  border-radius: 4px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.color-value {
  font-family: monospace;
  color: #333;
}
</style>
