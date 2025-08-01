<template>
  <div class="datetime-display">
    <el-tag type="info" size="small">
      <Clock />
      <span class="ml-2">{{ value || '-' }}</span>
    </el-tag>
  </div>
</template>

<script setup lang="ts">
import { Clock } from '@element-plus/icons-vue';

const props = defineProps<{
  value: string; // 日期时间格式：YYYY-MM-DD HH:MM:SS
}>();
</script>

<style scoped>
.datetime-display {
  display: flex;
  align-items: center;
}

.ml-2 {
  margin-left: 6px;
}
</style>
