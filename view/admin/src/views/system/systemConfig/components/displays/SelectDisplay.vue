<template>
  <div class="select-display">
    <template v-if="displayValue">
      <el-tag size="small" v-for="item in Array.isArray(displayValue) ? displayValue : [displayValue]" :key="item">
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

// 获取显示文本
const displayValue = computed(() => {
  if (!props.options || !props.options.length) return props.value;

  // 如果是单选
  if (!Array.isArray(props.value)) {
    const option = props.options.find(item => item.value === props.value);
    return option ? option.label : props.value;
  }

  // 如果是多选
  return props.value.map((val: any) => {
    const option = props.options!.find(item => item.value === val);
    return option ? option.label : val;
  });
});
</script>

<style scoped>
.select-display {
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
}
</style>
