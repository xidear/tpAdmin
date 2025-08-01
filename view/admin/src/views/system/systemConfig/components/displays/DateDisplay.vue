<template>
  <div class="date-display">
    <el-tag type="info" size="small">
      <Calendar />
      <span class="ml-2">{{ value || '-' }}</span>
    </el-tag>
  </div>
</template>

<script setup lang="ts">
import { Calendar } from '@element-plus/icons-vue';

const props = defineProps<{
  value: string; // 日期格式：YYYY-MM-DD
}>();
</script>

<style scoped>
.date-display {
  display: flex;
  align-items: center;
}

.ml-2 {
  margin-left: 6px;
}
</style>
