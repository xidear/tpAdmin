<template>
  <div class="time-display">
    <el-tag type="info" size="small">
      <Watch />
      <span class="ml-2">{{ value || '-' }}</span>
    </el-tag>
  </div>
</template>

<script setup lang="ts">
import { Watch } from '@element-plus/icons-vue';

const props = defineProps<{
  value: string; // 时间格式：HH:MM:SS
}>();
</script>

<style scoped>
.time-display {
  display: flex;
  align-items: center;
}

.ml-2 {
  margin-left: 6px;
}
</style>
