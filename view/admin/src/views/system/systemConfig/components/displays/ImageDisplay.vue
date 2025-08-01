<template>
  <div class="image-display">
    <el-image
      v-if="value"
      :src="value"
      :preview-src-list="[value]"
      fit="cover"
      lazy
      class="display-image"
    />
    <span v-else>-</span>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  value: string;
}>();
</script>

<style scoped>
.image-display {
  display: inline-block;
}

.display-image {
  width: 100px;
  height: 100px;
  border-radius: 4px;
  cursor: zoom-in;
}
</style>
