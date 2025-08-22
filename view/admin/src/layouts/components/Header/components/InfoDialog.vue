<template>
  <el-dialog 
    v-model="dialogVisible" 
    title="个人信息" 
    :width="dialogWidth" 
    draggable
    :close-on-click-modal="false"
  >
    <div class="info-container">
      <!-- 编辑模式切换 -->
      <div class="edit-toggle">
        <el-button 
          type="primary" 
          @click="toggleEditMode"
          :icon="isEditMode ? 'Close' : 'Edit'"
        >
          {{ isEditMode ? '取消编辑' : '编辑信息' }}
        </el-button>
      </div>

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
        
        <!-- 编辑模式下的头像上传 -->
        <div v-if="isEditMode" class="avatar-upload">
          <el-upload
            class="avatar-uploader"
            :action="uploadUrl"
            :show-file-list="false"
            :on-success="handleAvatarSuccess"
            :before-upload="beforeAvatarUpload"
            accept="image/*"
          >
            <el-button size="small" type="primary">更换头像</el-button>
          </el-upload>
        </div>
      </div>
      
      <!-- 信息表格 -->
      <el-descriptions :column="1" border class="info-table">
        <el-descriptions-item label="账号">
          <template v-if="isEditMode">
            <el-input 
              v-model="editForm.username" 
              placeholder="请输入用户名"
              maxlength="20"
            />
          </template>
          <template v-else>
            {{ userInfo.username }}
          </template>
        </el-descriptions-item>
        <el-descriptions-item label="ID">
          {{ userInfo.admin_id }}
        </el-descriptions-item>
        <el-descriptions-item label="姓名">
          {{ userInfo.real_name || '未知' }}
        </el-descriptions-item>
        <el-descriptions-item label="昵称">
          <template v-if="isEditMode">
            <el-input 
              v-model="editForm.nick_name" 
              placeholder="请输入昵称"
              maxlength="20"
            />
          </template>
          <template v-else>
            {{ userInfo.nick_name || '未知' }}
          </template>
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
          <el-tag :type="isSuperAdmin ? 'danger' : 'info'" size="small">
            {{ isSuperAdmin ? '是' : '否' }}
          </el-tag>
        </el-descriptions-item>
      </el-descriptions>

      <!-- 编辑模式下的保存按钮 -->
      <div v-if="isEditMode" class="edit-actions">
        <el-button type="primary" @click="saveProfile" :loading="saving">
          保存修改
        </el-button>
        <el-button @click="cancelEdit">取消</el-button>
      </div>
    </div>

    <!-- 大图查看 -->
    <el-dialog
      v-model="showLargeAvatar"
      title="头像预览"
      width="400px"
      :close-on-click-modal="true"
      append-to-body
    >
      <div class="large-avatar-container">
        <img 
          :src="userInfo.avatar || defaultAvatar" 
          :alt="userInfo.nick_name || userInfo.username"
          class="large-avatar"
          @load="handleImageLoad"
        />
        <div v-if="imageLoading" class="image-loading">
          <el-icon class="is-loading"><Loading /></el-icon>
        </div>
      </div>
    </el-dialog>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import { useUserStore } from "@/stores/modules/user";
import { ElMessage, ElMessageBox } from "element-plus";
import { ZoomIn, Loading } from "@element-plus/icons-vue";
import { updateProfileApi } from "@/api/modules/base";

const dialogVisible = ref(false);
const showLargeAvatar = ref(false);
const imageLoading = ref(false);
const isEditMode = ref(false);
const saving = ref(false);
const userStore = useUserStore();
const defaultAvatar = "@/assets/images/avatar.gif";

// 编辑表单数据
const editForm = ref({
  username: '',
  nick_name: '',
  avatar: ''
});

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

// 上传URL
const uploadUrl = computed(() => {
  return `${import.meta.env.VITE_API_URL}/upload/image`;
});

// 打开弹窗
const openDialog = () => {
  dialogVisible.value = true;
  // 从store获取用户信息
  userInfo.value = { ...userStore.userInfo };
  // 初始化编辑表单
  editForm.value = {
    username: userInfo.value.username,
    nick_name: userInfo.value.nick_name,
    avatar: userInfo.value.avatar
  };
};

// 切换编辑模式
const toggleEditMode = () => {
  if (isEditMode.value) {
    cancelEdit();
  } else {
    isEditMode.value = true;
  }
};

// 取消编辑
const cancelEdit = () => {
  isEditMode.value = false;
  // 重置编辑表单
  editForm.value = {
    username: userInfo.value.username,
    nick_name: userInfo.value.nick_name,
    avatar: userInfo.value.avatar
  };
};

// 头像上传成功
const handleAvatarSuccess = (response: any) => {
  if (response.code === 200) {
    editForm.value.avatar = response.data.url;
    ElMessage.success('头像上传成功');
  } else {
    ElMessage.error(response.message || '头像上传失败');
  }
};

// 头像上传前验证
const beforeAvatarUpload = (file: File) => {
  const isImage = file.type.startsWith('image/');
  const isLt2M = file.size / 1024 / 1024 < 2;

  if (!isImage) {
    ElMessage.error('只能上传图片文件!');
    return false;
  }
  if (!isLt2M) {
    ElMessage.error('图片大小不能超过 2MB!');
    return false;
  }
  return true;
};

// 保存个人信息
const saveProfile = async () => {
  try {
    saving.value = true;
    
    // 检查是否有修改
    const hasChanges = editForm.value.username !== userInfo.value.username ||
                      editForm.value.nick_name !== userInfo.value.nick_name ||
                      editForm.value.avatar !== userInfo.value.avatar;
    
    if (!hasChanges) {
      ElMessage.warning('没有修改任何信息');
      return;
    }
    
    const response = await updateProfileApi(editForm.value);
    
    if (response.code === 200) {
      ElMessage.success('个人信息更新成功');
      
      // 更新本地数据
      userInfo.value = { ...userInfo.value, ...editForm.value };
      
      // 更新store中的用户信息
      userStore.setUserInfo({ ...userStore.userInfo, ...editForm.value });
      
      // 退出编辑模式
      isEditMode.value = false;
    } else {
      ElMessage.error(response.message || '更新失败');
    }
  } catch (error) {
    console.error('更新个人信息失败:', error);
    ElMessage.error('更新失败，请重试');
  } finally {
    saving.value = false;
  }
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

.edit-toggle {
  text-align: right;
  margin-bottom: 20px;
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

.avatar-upload {
  margin-top: 10px;
}

.info-table {
  margin-bottom: 20px;
  
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

.edit-actions {
  text-align: center;
  margin-top: 20px;
  
  .el-button {
    margin: 0 10px;
  }
}

.large-avatar-container {
  text-align: center;
  position: relative;
}

.large-avatar {
  max-width: 100%;
  max-height: 400px;
  border-radius: 8px;
}

.image-loading {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 24px;
  color: #409eff;
}
</style>
