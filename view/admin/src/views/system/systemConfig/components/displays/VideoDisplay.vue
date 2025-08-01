<template>
  <div class="video-display">
    <template v-if="value">
      <video
        :src="value"
        controls
        class="video-player"
        :poster="poster"
        @error="handleVideoError"
      >
        您的浏览器不支持视频播放
      </video>
      <div class="video-info">
        <el-button 
          type="text" 
          icon="Download" 
          @click="handleDownload"
          class="download-btn"
        >
          下载视频
        </el-button>
        <span class="video-name">{{ fileName }}</span>
      </div>
    </template>
    <div v-else>
      <el-empty description="无视频文件" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { ElMessage, ElEmpty } from 'element-plus';
import { Download } from '@element-plus/icons-vue';

const props = defineProps<{
  value: string; // 视频URL
}>();

// 视频封面图（使用视频URL的第一帧或默认图片）
const poster = computed(() => {
  // 实际项目中可以使用视频截图服务
  return props.value ? `https://picsum.photos/800/450?random=${props.value.hashCode()}` : '';
});

// 从URL中提取文件名
const fileName = computed(() => {
  if (!props.value) return '';
  const urlParts = props.value.split('/');
  return urlParts[urlParts.length - 1].split('?')[0] || 'video.mp4';
});

// 处理视频加载错误
const handleVideoError = () => {
  ElMessage.error('视频加载失败，可能是文件不存在或格式不支持');
};

// 处理视频下载
const handleDownload = () => {
  if (!props.value) return;
  
  // 创建下载链接
  const link = document.createElement('a');
  link.href = props.value;
  link.download = fileName.value;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
};

// 简单的字符串哈希函数（用于生成随机封面图）
String.prototype.hashCode = function() {
  let hash = 0;
  for (let i = 0; i < this.length; i++) {
    const char = this.charCodeAt(i);
    hash = ((hash << 5) - hash) + char;
    hash = hash & hash; // 转换为32位整数
  }
  return Math.abs(hash);
};
</script>

<style scoped>
.video-display {
  border-radius: 4px;
  overflow: hidden;
}

.video-player {
  width: 100%;
  max-height: 400px;
  background-color: #000;
  border-radius: 4px;
}

.video-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 0;
  margin-top: 8px;
  border-top: 1px solid #e5e7eb;
}

.video-name {
  color: #666;
  font-size: 14px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
  margin-right: 16px;
}

.download-btn {
  color: #409eff;
}
</style>
