<template>
  <div class="rich-text-display">
    <div v-if="value" v-html="value" class="rich-text-content" />
    <span v-else>-</span>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  value: string;
}>();
</script>

<style scoped>
.rich-text-display {
  border-radius: 4px;
  overflow: hidden;
}

.rich-text-content {
  padding: 12px;
  background-color: #fff;
  border: 1px solid #e5e7eb;
  min-height: 100px;
}

.rich-text-content img {
  max-width: 100%;
  height: auto;
}

.rich-text-content p {
  margin: 8px 0;
}
</style>
