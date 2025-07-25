<template>
  <el-dialog 
    v-model="dialogVisible" 
    title="个人信息" 
    :width="dialogWidth" 
    draggable
    :close-on-click-modal="false"
  >
    <div class="info-container">
      <!-- 头像区域 -->
      <div class="avatar-section">
        <div class="avatar-wrapper" @click="showLargeAvatar = true">
          <img 
            :src="userInfo.avatar || defaultAvatar" 
            :alt="userInfo.nick_name || userInfo.username" 
            class="avatar-img"
          />
          <el-icon class="zoom-icon"><ZoomIn /></el-icon>
        </div>
        <div class="avatar-tip">点击查看大图</div>
      </div>
      
      <!-- 信息表格 -->
      <el-descriptions :column="1" border class="info-table">
        <el-descriptions-item label="账号">
          {{ userInfo.username }}
        </el-descriptions-item>
        <el-descriptions-item label="ID">
          {{ userInfo.admin_id }}
        </el-descriptions-item>
        <el-descriptions-item label="姓名">
          {{ userInfo.real_name || '未知' }}
        </el-descriptions-item>
        <el-descriptions-item label="昵称">
          {{ userInfo.nick_name || '未知' }}
        </el-descriptions-item>
        <el-descriptions-item label="角色">
          <span v-if="userInfo.role_name_list && userInfo.role_name_list.length">
            <el-tag 
              v-for="(role, index) in userInfo.role_name_list" 
              :key="index" 
              type="primary"
              size="small"
              class="role-tag"
            >
              {{ role }}
            </el-tag>
          </span>
          <span v-else>无角色</span>
        </el-descriptions-item>
        <el-descriptions-item label="创建时间">
          {{ userInfo.created_at || '未知' }}
        </el-descriptions-item>
        <el-descriptions-item label="是否超级管理员">
          <el-switch 
            v-model="isSuperAdmin" 
            :active-value="1" 
            :inactive-value="0" 
            disabled
          />
        </el-descriptions-item>
        <el-descriptions-item label="状态">
          <el-tag :type="userInfo.status === 1 ? 'success' : 'danger'">
            {{ userInfo.status === 1 ? '正常' : '禁用' }}
          </el-tag>
        </el-descriptions-item>
      </el-descriptions>
    </div>
    
    <template #footer>
      <span class="dialog-footer">
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="dialogVisible = false">确认</el-button>
      </span>
    </template>
  </el-dialog>
  
  <!-- 头像大图查看器 -->
  <el-dialog
    v-model="showLargeAvatar"
    title=" "
    width="80%"
    :border="false"
    :show-close="true"
    :close-on-click-modal="true"
    :header-style="{ padding: '15px', borderBottom: 'none' }"
    :body-style="{ padding: '20px', minHeight: '100px', margin: 0 }"
    :footer="null"
  >
    <!-- 新增容器用于居中图片 -->
    <div class="large-avatar-container">
      <img 
        :src="userInfo.avatar || defaultAvatar" 
        :alt="userInfo.nick_name || userInfo.username" 
        class="large-avatar"
        @load="handleImageLoad"
      />

      <div v-if="imageLoading" class="image-loading">
        <!-- 静态图标 + 文本 -->
        <el-icon class="loading-icon"><Loading /></el-icon>
        <span class="loading-text">加载中</span>
      </div>
    </div>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed, onMounted } from "vue";
import { useUserStore } from "@/stores/modules/user";
import {Loading, ZoomIn} from "@element-plus/icons-vue";

// 状态管理
const dialogVisible = ref(false);
const showLargeAvatar = ref(false);
const imageLoading = ref(false);
const userStore = useUserStore();
const defaultAvatar = "@/assets/images/avatar.gif";

// 响应式用户信息
const userInfo = ref({
  admin_id: '',
  username: '',
  created_at: '',
  updated_at: '',
  real_name: '',
  nick_name: '',
  status: 0,
  avatar: '',
  is_super: 0,
  role_name_list: []
});

// 计算属性
const isSuperAdmin = computed(() => userInfo.value.is_super);
const dialogWidth = computed(() => {
  return window.innerWidth < 768 ? '90%' : '600px';
});

// 打开弹窗
const openDialog = () => {
  dialogVisible.value = true;
  // 从store获取用户信息
  userInfo.value = { ...userStore.userInfo };
};

// 处理图片加载
const handleImageLoad = () => {
  imageLoading.value = false;
};

// 监听头像查看
watch(showLargeAvatar, (newVal) => {
  if (newVal) {
    imageLoading.value = true;
  }
});

// 监听用户信息变化
watch(
  () => userStore.userInfo,
  (newVal) => {
    userInfo.value = { ...newVal };
  },
  { deep: true }
);

// 组件挂载时初始化数据
onMounted(() => {
  userInfo.value = { ...userStore.userInfo };
});

defineExpose({ openDialog });
</script>

<style scoped lang="scss">
.info-container {
  padding: 10px 0;
}

.avatar-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 20px;
  padding: 10px;
}

.avatar-wrapper {
  position: relative;
  width: 120px;
  height: 120px;
  border-radius: 50%;
  overflow: hidden;
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  transition: transform 0.3s ease;
  
  &:hover {
    transform: scale(1.05);
  }
  
  .avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
  
  .zoom-icon {
    position: absolute;
    bottom: 0;
    right: 0;
    background-color: rgba(0, 0, 0, 0.5);
    color: white;
    padding: 4px;
    border-radius: 50%;
  }
}

.avatar-tip {
  margin-top: 8px;
  font-size: 12px;
  color: #666;
}

.info-table {
  margin-top: 15px;
  
  ::v-deep .el-descriptions__label {
    font-weight: 500;
    color: #606266;
    width: 120px;
  }
  
  ::v-deep .el-descriptions__content {
    color: #303133;
  }
}

.role-tag {
  margin-right: 5px;
  margin-bottom: 5px;
}

/* 大图查看器样式 - 重点修改区域 */
.large-avatar-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 200px; /* 确保小图片也能垂直居中 */
  width: 100%;
  position: relative;
}

.large-avatar {
  max-width: 100%;
  max-height: 80vh;
  object-fit: contain;
}

.image-loading {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

/* 修复弹窗默认样式影响居中 */
::v-deep .el-dialog__body {
  padding: 0 !important;
  margin: 0;
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>
