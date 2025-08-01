<template>
  <div class="markdown-display">
    <div v-if="value" class="markdown-content" v-html="renderedMarkdown" />
    <span v-else>-</span>
  </div>
</template>

<script setup lang="ts">
import {computed, onMounted, ref, watch} from 'vue';
import { marked } from 'marked';
import 'github-markdown-css';

const props = defineProps<{
  value: string;
}>();

const renderedMarkdown = ref('');

// 渲染Markdown内容
const renderMarkdown = () => {
  if (props.value) {
    renderedMarkdown.value = marked.parse(props.value);
  } else {
    renderedMarkdown.value = '';
  }
};

// 监听值变化重新渲染
onMounted(renderMarkdown);
watch(() => props.value, renderMarkdown);
</script>

<style scoped>
.markdown-display {
  border-radius: 4px;
  overflow: hidden;
}

.markdown-content {
  padding: 16px;
  background-color: #fff;
  border: 1px solid #e5e7eb;
}

/* 引入github-markdown-css后，为内容添加类名以应用样式 */
:deep(.markdown-content) {
  @apply markdown-body;
}

:deep(.markdown-content img) {
  max-width: 100%;
  height: auto;
}
</style>
