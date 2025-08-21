<template>
  <div class="file-upload">
    <el-button @click="showUploadDialog = true" type="success">
      <i class="el-icon-upload"></i>
      上传{{ getFileTypeLabel() }}
    </el-button>

    <el-dialog
      v-model="showUploadDialog"
      title="上传{{ getFileTypeLabel() }}"
      width="600px"
      :close-on-click-modal="false"
    >
      <div class="upload-form">
        <el-form :model="uploadForm" label-width="80px">
          <el-form-item label="分类">
            <el-select
              v-model="uploadForm.categoryId"
              placeholder="选择分类（可选）"
              clearable
              style="width: 100%"
            >
              <el-option
                v-for="category in categories"
                :key="category.category_id"
                :label="category.name"
                :value="category.category_id"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="权限设置">
            <el-select v-model="uploadForm.permission" style="width: 100%">
              <el-option label="公共（所有人可见）" value="public" />
              <el-option label="私有（仅自己可见）" value="private" />
              <el-option label="共享（指定用户可见）" value="shared" />
            </el-select>
            <div class="permission-tip">
              <small>
                <strong>公共</strong>：所有用户都可以查看和使用<br>
                <strong>私有</strong>：只有上传者可以查看和使用<br>
                <strong>共享</strong>：可以指定特定用户或角色访问（功能开发中）
              </small>
            </div>
          </el-form-item>

          <el-form-item label="选择文件">
            <el-upload
              ref="uploadRef"
              class="upload-demo"
              drag
              :action="uploadUrl"
              :headers="uploadHeaders"
              :data="uploadData"
              :accept="acceptTypes"
              :multiple="true"
              :auto-upload="false"
              :on-change="handleFileChange"
              :on-success="handleUploadSuccess"
              :on-error="handleUploadError"
              :before-upload="beforeUpload"
              v-model:file-list="fileList"
            >
              <i class="el-icon-upload"></i>
              <div class="el-upload__text">
                将{{ getFileTypeLabel() }}拖到此处，或<em>点击上传</em>
              </div>
              <div class="el-upload__tip" slot="tip">
                {{ getUploadTip() }}
              </div>
            </el-upload>
          </el-form-item>
        </el-form>
      </div>

      <template #footer>
        <span class="dialog-footer">
          <el-button @click="showUploadDialog = false">取消</el-button>
          <el-button type="primary" @click="submitUpload" :loading="uploading" :disabled="fileList.length === 0">
            开始上传 ({{ fileList.length }})
          </el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { ElMessage } from 'element-plus';
import { useUserStore } from '@/stores/modules/user';

interface CategoryItem {
  category_id: number;
  name: string;
  parent_id: number | null;
}

const props = defineProps<{
  fileType?: string; // 限制文件类型: image, video, document, pdf, all
  categories?: CategoryItem[]; // 从父组件传入分类列表
}>();

const emit = defineEmits<{
  uploadSuccess: [];
}>();

const showUploadDialog = ref(false);
const uploading = ref(false);
const categories = ref<CategoryItem[]>([]);
const uploadRef = ref();
const fileList = ref<any[]>([]);

const uploadForm = ref({
  categoryId: undefined as number | undefined,
  permission: 'public' as 'private' | 'public' | 'shared', // 默认公共
});

const acceptTypes = ref('');

// 上传配置 - 根据文件类型选择正确的接口
const uploadUrl = computed(() => {
  const baseUrl = import.meta.env.VITE_API_URL || 'http://localhost:8848';
  
  switch (props.fileType) {
    case 'image':
      return `${baseUrl}/upload/image`;  // 图片专用接口
    case 'video':
      return `${baseUrl}/upload/video`;  // 视频专用接口
    case 'document':
      return `${baseUrl}/upload/file`;   // 文档用通用接口
    case 'pdf':
      return `${baseUrl}/upload/file`;   // PDF用通用接口
    default:
      return `${baseUrl}/upload/file`;   // 其他类型用通用接口
  }
});
const uploadHeaders = computed(() => {
  const userStore = useUserStore();
  return {
    'Authorization': `Bearer ${userStore.token}`,
  };
});

const uploadData = computed(() => ({
  category_id: uploadForm.value.categoryId || undefined,
  storage_permission: uploadForm.value.permission,
  file_type: props.fileType || 'all', // 传递文件类型参数
}));

// 获取文件类型标签
const getFileTypeLabel = () => {
  switch (props.fileType) {
    case 'image': return '图片';
    case 'video': return '视频';
    case 'document': return '文档';
    case 'pdf': return 'PDF';
    default: return '文件';
  }
};

// 根据文件类型更新接受的文件格式
const updateAcceptTypes = () => {
  switch (props.fileType) {
    case 'image':
      acceptTypes.value = 'image/*';
      break;
    case 'video':
      acceptTypes.value = 'video/*';
      break;
    case 'document':
      acceptTypes.value = '.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt';
      break;
    case 'pdf':
      acceptTypes.value = '.pdf';
      break;
    default:
      acceptTypes.value = '*/*'; // 接受所有文件类型
  }
};

// 获取上传提示文本
const getUploadTip = () => {
  switch (props.fileType) {
    case 'image':
      return '支持 JPG、PNG、GIF、WebP 等图片格式，单个文件不超过 10MB';
    case 'video':
      return '支持 MP4、AVI、MOV、WMV 等视频格式，单个文件不超过 100MB';
    case 'document':
      return '支持 DOC、DOCX、XLS、XLSX、PPT、PPTX、TXT 等文档格式';
    case 'pdf':
      return '支持 PDF 格式文档，单个文件不超过 50MB';
    default:
      return '支持所有文件类型，请根据实际需要选择';
  }
};

// 文件选择变化处理
const handleFileChange = (file: any, uploadFileList: any[]) => {
  console.log('File changed:', file, uploadFileList);
  // 同步文件列表
  fileList.value = [...uploadFileList];
};

// 上传前检查
const beforeUpload = (file: File) => {
  // 如果指定了文件类型，进行类型检查
  if (props.fileType && props.fileType !== 'all') {
    const isValidType = validateFileType(file, props.fileType);
    if (!isValidType) {
      ElMessage.error(`请选择${getFileTypeLabel()}文件`);
      return false;
    }
  }
  return true;
};

// 验证文件类型
const validateFileType = (file: File, fileType: string): boolean => {
  switch (fileType) {
    case 'image':
      return file.type.startsWith('image/');
    case 'video':
      return file.type.startsWith('video/');
    case 'pdf':
      return file.type === 'application/pdf';
    case 'document':
      return file.type.includes('word') || file.type.includes('excel') || file.type.includes('powerpoint') || file.type.includes('text');
    default:
      return true;
  }
};

// 上传成功处理
const handleUploadSuccess = (response: any, file: any) => {
  ElMessage.success(`${getFileTypeLabel()}上传成功`);
  emit('uploadSuccess');
};

// 上传失败处理
const handleUploadError = (error: any, file: any) => {
  console.error('Upload error:', error);
  ElMessage.error(`${getFileTypeLabel()}上传失败`);
};

// 提交上传
const submitUpload = () => {
  const uploadInstance = uploadRef.value;
  if (!uploadInstance) {
    ElMessage.error('上传组件未初始化');
    return;
  }

  // 检查是否有文件被选择
  if (fileList.value.length === 0) {
    ElMessage.warning('请先选择要上传的文件');
    return;
  }

  uploading.value = true;
  
  try {
    uploadInstance.submit();
    
    // 模拟上传完成（实际应该在上传回调中处理）
    setTimeout(() => {
      uploading.value = false;
      showUploadDialog.value = false;
      uploadInstance.clearFiles();
      fileList.value = [];
    }, 2000);
  } catch (error) {
    console.error('Upload submit error:', error);
    ElMessage.error('上传提交失败');
    uploading.value = false;
  }
};

// 初始化
onMounted(() => {
  // 如果父组件传入了分类列表，直接使用
  if (props.categories && props.categories.length > 0) {
    categories.value = props.categories;
  }
  updateAcceptTypes();
});

// 监听 fileType 变化
watch(() => props.fileType, (newType) => {
  if (newType) {
    updateAcceptTypes();
  }
});

// 监听父组件传入的分类列表
watch(() => props.categories, (newCategories) => {
  if (newCategories && newCategories.length > 0) {
    categories.value = newCategories;
  }
}, { immediate: true });
</script>

<style lang="scss" scoped>
.file-upload {
  .upload-form {
    :deep(.el-upload-dragger) {
      width: 100%;
      height: 180px;
    }
    
    :deep(.el-upload__tip) {
      font-size: 12px;
      color: #666;
      margin-top: 8px;
    }

    .permission-tip {
      margin-top: 5px;
      color: #666;
      line-height: 1.4;
    }
  }
}
</style>