<template>
  <div class="unknown-display">
    <span class="text-gray-500">{{ value ?? '无数据' }}</span>
    <el-tag type="info"  class="ml-2">
      未知类型({{ type }})
    </el-tag>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  value: any;
  type: number;
}>();
</script>

<style scoped>
.unknown-display {
  display: flex;
  align-items: center;
}

.text-gray-500 {
  color: #666;
}

.ml-2 {
  margin-left: 8px;
}
</style>
