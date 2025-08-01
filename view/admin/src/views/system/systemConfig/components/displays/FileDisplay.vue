<template>
  <div class="file-display">
    <template v-if="value">
      <el-link 
        :href="value" 
        target="_blank" 
        class="file-link"
        @click.prevent="handleFileClick"
      >
        <el-icon :class="fileIconClass" class="file-icon" />
        <span class="file-name">{{ fileName }}</span>
      </el-link>
      <el-button 
        type="text" 
        icon="Download" 
        size="small" 
        @click="handleDownload"
        class="download-btn"
      />
    </template>
    <span v-else>-</span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import {
  Document
} from '@element-plus/icons-vue';

const props = defineProps<{
  value: string; // 文件URL
}>();

// 从URL中提取文件名
const fileName = computed(() => {
  if (!props.value) return '';
  const urlParts = props.value.split('/');
  return urlParts[urlParts.length - 1].split('?')[0] || 'file';
});

// 获取文件扩展名
const fileExt = computed(() => {
  if (!fileName.value) return '';
  const parts = fileName.value.split('.');
  return parts.length > 1 ? parts.pop()?.toLowerCase() : '';
});

// 根据文件类型选择图标
const fileIcon = computed(() => {
  return Document;
  switch (fileExt.value) {
    case 'pdf':
      return Pdf;
    case 'doc':
    case 'docx':
      return Document;
    case 'xls':
    case 'xlsx':
      return Excel;
    case 'ppt':
    case 'pptx':
      return Powerpoint;
    case 'zip':
    case 'rar':
    case '7z':
      return Zip;
    case 'jpg':
    case 'jpeg':
    case 'png':
    case 'gif':
    case 'bmp':
      return ImageIcon;
    default:
      return Document;
  }
});

// 文件图标样式
const fileIconClass = computed(() => {
  switch (fileExt.value) {
    case 'pdf':
      return 'text-red-500';
    case 'doc':
    case 'docx':
      return 'text-blue-500';
    case 'xls':
    case 'xlsx':
      return 'text-green-500';
    case 'ppt':
    case 'pptx':
      return 'text-orange-500';
    default:
      return 'text-gray-500';
  }
});

// 处理文件点击
const handleFileClick = () => {
  // 对于可在线预览的文件类型，在这里处理预览逻辑
  window.open(props.value, '_blank');
};

// 处理文件下载
const handleDownload = () => {
  if (!props.value) return;
  
  const link = document.createElement('a');
  link.href = props.value;
  link.download = fileName.value;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};
</script>

<style scoped>
.file-display {
  display: flex;
  align-items: center;
  gap: 8px;
}

.file-link {
  display: flex;
  align-items: center;
  gap: 8px;
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.file-icon {
  font-size: 18px;
}

.file-name {
  line-height: 1;
}

.download-btn {
  padding: 0 6px;
  color: #666;
}
</style>
