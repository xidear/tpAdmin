<template>
  <el-tag :type="value ? 'success' : 'danger'" size="small">
    {{ value ? '开启' : '关闭' }}
  </el-tag>
</template>

<script setup lang="ts">
import { getEnumLabelByValue } from '@/api/modules/enum';
import { computed } from 'vue';

const props = defineProps<{
  value: any;
}>();
</script>
