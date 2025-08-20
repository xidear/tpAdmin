<template>
  <div class="file-management">
    <!-- 页面标题 -->
    <div class="page-header">
      <h2>文件管理</h2>
      <p>管理系统中的文件资源，支持文件列表查看、上传和地址迁移</p>
    </div>

    <!-- 标签页 -->
    <el-tabs v-model="activeTab" type="border-card" class="file-tabs">
      <!-- 文件列表标签页 -->
      <el-tab-pane label="文件列表" name="list">
        <FileList />
      </el-tab-pane>
      
      <!-- 文件迁移标签页 - 使用v-auth控制显示 -->
      <el-tab-pane 
        v-auth="'migrateUrls'"
        label="文件迁移" 
        name="migration"
      >
        <FileMigration />
      </el-tab-pane>
    </el-tabs>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import FileList from './FileList.vue'
import FileMigration from './FileMigration.vue'

// 当前激活的标签页
const activeTab = ref('list')
</script>

<style scoped>
.file-management {
  padding: 20px;
}

.page-header {
  margin-bottom: 20px;
}

.page-header h2 {
  margin: 0 0 8px 0;
  font-size: 24px;
  font-weight: 600;
  color: #303133;
}

.page-header p {
  margin: 0;
  color: #909399;
  font-size: 14px;
}

.file-tabs {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
}
</style>
