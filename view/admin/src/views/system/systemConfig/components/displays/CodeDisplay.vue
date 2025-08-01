<template>
  <div class="code-display">
    <pre>
      <code :class="['language-' + language]">
        {{ value || '// 无代码内容' }}
      </code>
    </pre>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import 'prismjs';
import 'prismjs/themes/prism-tomorrow.css';
import 'prismjs/components/prism-javascript.js';
import 'prismjs/components/prism-css.js';
import 'prismjs/components/prism-json.js';
import 'prismjs/components/prism-php.js';
import 'prismjs/components/prism-jq.js';
import 'prismjs/components/prism-python.js';

const props = defineProps<{
  value: string;
  options?: { language?: string }; // 可选配置：指定代码语言
}>();

// 确定代码语言，默认使用javascript
const language = computed(() => {
  const supportedLanguages = ['javascript', 'html', 'css', 'json', 'php', 'python'];
  const lang = props.options?.language || 'javascript';
  return supportedLanguages.includes(lang) ? lang : 'javascript';
});
</script>

<style scoped>
.code-display {
  background-color: #2d2d2d;
  border-radius: 4px;
  overflow: hidden;
}

pre {
  margin: 0;
  padding: 16px;
  overflow-x: auto;
}

code {
  color: #ccc;
  font-family: 'Fira Code', monospace;
  font-size: 14px;
  line-height: 1.5;
}
</style>
