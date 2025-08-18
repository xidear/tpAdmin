<template>
  <el-dialog
    v-model="visible"
    title="上传图片"
    width="600px"
    :close-on-click-modal="false"
    @close="handleClose"
  >
    <el-form :model="uploadForm" label-width="100px" @submit.prevent>
      <el-form-item label="图片分类" prop="category_id">
        <el-tree-select
          v-model="uploadForm.category_id"
          :data="categoryTree"
          :props="categoryProps"
          placeholder="请选择图片分类"
          clearable
          check-strictly
          :render-after-expand="false"
        />
        <div class="form-tip">
          <el-button type="text" size="small" @click="handleManageCategory">
            管理分类
          </el-button>
        </div>
      </el-form-item>
      
      <el-form-item label="图片标签" prop="tags">
        <el-select
          v-model="uploadForm.tags"
          multiple
          filterable
          allow-create
          default-first-option
          placeholder="请选择或创建标签"
          style="width: 100%"
        >
          <el-option
            v-for="tag in availableTags"
            :key="tag"
            :label="tag"
            :value="tag"
          />
        </el-select>
      </el-form-item>
      
      <el-form-item label="存储类型" prop="storage_type">
        <el-radio-group v-model="uploadForm.storage_type">
          <el-radio label="local">本地存储</el-radio>
          <el-radio label="aliyun_oss">阿里云OSS</el-radio>
          <el-radio label="qcloud_cos">腾讯云COS</el-radio>
          <el-radio label="aws_s3">AWS S3</el-radio>
        </el-radio-group>
      </el-form-item>
      
      <el-form-item label="访问权限" prop="storage_permission">
        <el-radio-group v-model="uploadForm.storage_permission">
          <el-radio label="public">公开访问</el-radio>
          <el-radio label="private">私有访问</el-radio>
          <el-radio label="admin_only">仅管理员</el-radio>
        </el-radio-group>
      </el-form-item>
      
      <el-form-item label="上传图片">
        <el-upload
          ref="uploadRef"
          :action="uploadAction"
          :headers="uploadHeaders"
          :data="uploadData"
          :multiple="true"
          :show-file-list="true"
          :file-list="fileList"
          :before-upload="beforeUpload"
          :on-success="onUploadSuccess"
          :on-error="onUploadError"
          :on-remove="onFileRemove"
          :on-exceed="onExceed"
          :limit="maxFiles"
          accept="image/*"
          drag
          list-type="picture-card"
        >
          <el-icon class="el-icon--upload"><UploadFilled /></el-icon>
          <div class="el-upload__text">
            将图片拖到此处，或<em>点击上传</em>
          </div>
          <template #tip>
            <div class="el-upload__tip">
              支持 jpg/png/gif/webp 格式，单个文件不超过 10MB
            </div>
          </template>
        </el-upload>
      </el-form-item>
    </el-form>
    
    <!-- 上传进度 -->
    <div v-if="uploadProgress.length > 0" class="upload-progress">
      <h4>上传进度</h4>
      <div
        v-for="(progress, index) in uploadProgress"
        :key="index"
        class="progress-item"
      >
        <div class="progress-info">
          <span class="filename">{{ progress.filename }}</span>
          <span class="status">{{ progress.status }}</span>
        </div>
        <el-progress
          v-if="progress.status === 'uploading'"
          :percentage="progress.percentage"
          :status="progress.status === 'error' ? 'exception' : undefined"
        />
        <div v-if="progress.status === 'success'" class="success-info">
          <el-tag type="success" size="small">上传成功</el-tag>
          <span class="url">{{ progress.url }}</span>
        </div>
        <div v-if="progress.status === 'error'" class="error-info">
          <el-tag type="danger" size="small">上传失败</el-tag>
          <span class="error-message">{{ progress.error }}</span>
        </div>
      </div>
    </div>
    
    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleClose">取消</el-button>
        <el-button
          type="primary"
          @click="handleConfirm"
          :loading="uploading"
          :disabled="fileList.length === 0"
        >
          确认上传
        </el-button>
      </div>
    </template>
    
    <!-- 分类管理对话框 -->
    <CategoryManager
      v-model:visible="categoryVisible"
      @success="handleCategorySuccess"
    />
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { UploadFilled } from '@element-plus/icons-vue'
import CategoryManager from './CategoryManager.vue'
import { uploadImage, getImageCategories } from '@/api/image'

// Props
interface Props {
  visible: boolean
  categoryId?: number
}

const props = withDefaults(defineProps<Props>(), {
  visible: false,
  categoryId: 0
})

// Emits
const emit = defineEmits<{
  'update:visible': [value: boolean]
  'success': [image: any]
}>()

// 响应式数据
const uploadRef = ref()
const categoryVisible = ref(false)
const uploading = ref(false)
const fileList = ref([])
const categoryTree = ref([])
const availableTags = ref(['产品图', 'banner', 'logo', '图标', '背景图', '装饰图'])

// 上传表单
const uploadForm = reactive({
  category_id: 0,
  tags: [],
  storage_type: 'local',
  storage_permission: 'public'
})

// 上传进度
const uploadProgress = ref([])

// 分类配置
const categoryProps = {
  children: 'children',
  label: 'name',
  value: 'category_id'
}

// 计算属性
const uploadAction = computed(() => '/adminapi/upload/image')
const uploadHeaders = computed(() => ({
  'X-Requested-With': 'XMLHttpRequest'
}))
const uploadData = computed(() => ({
  storage_type: uploadForm.storage_type,
  storage_permission: uploadForm.storage_permission,
  category_id: uploadForm.category_id,
  tags: uploadForm.tags.join(',')
}))
const maxFiles = 10

// 方法
const handleClose = () => {
  emit('update:visible', false)
  resetForm()
}

const handleConfirm = async () => {
  if (fileList.value.length === 0) {
    ElMessage.warning('请先选择要上传的图片')
    return
  }
  
  try {
    uploading.value = true
    
    // 开始上传所有文件
    for (let i = 0; i < fileList.value.length; i++) {
      const file = fileList.value[i]
      if (file.status === 'ready') {
        await uploadFile(file, i)
      }
    }
    
    // 检查是否有成功上传的文件
    const successFiles = uploadProgress.value.filter(p => p.status === 'success')
    if (successFiles.length > 0) {
      ElMessage.success(`成功上传 ${successFiles.length} 张图片`)
      emit('success', successFiles[0]) // 返回第一张成功上传的图片
      handleClose()
    }
  } catch (error) {
    ElMessage.error('上传过程中出现错误')
  } finally {
    uploading.value = false
  }
}

const uploadFile = async (file: any, index: number) => {
  const progress = {
    filename: file.name,
    status: 'uploading',
    percentage: 0,
    url: '',
    error: ''
  }
  
  uploadProgress.value[index] = progress
  
  try {
    // 创建 FormData
    const formData = new FormData()
    formData.append('file', file.raw)
    formData.append('storage_type', uploadForm.storage_type)
    formData.append('storage_permission', uploadForm.storage_permission)
    formData.append('category_id', uploadForm.category_id.toString())
    formData.append('tags', uploadForm.tags.join(','))
    
    // 模拟上传进度
    const progressInterval = setInterval(() => {
      if (progress.percentage < 90) {
        progress.percentage += Math.random() * 20
      }
    }, 200)
    
    const response = await uploadImage(formData)
    
    clearInterval(progressInterval)
    progress.percentage = 100
    progress.status = 'success'
    progress.url = response.data.url
    
    // 更新文件状态
    file.status = 'success'
    file.url = response.data.url
    
  } catch (error) {
    progress.status = 'error'
    progress.error = error.message || '上传失败'
    file.status = 'error'
  }
}

const beforeUpload = (file: File) => {
  // 检查文件类型
  const isImage = file.type.startsWith('image/')
  if (!isImage) {
    ElMessage.error('只能上传图片文件')
    return false
  }
  
  // 检查文件大小 (10MB)
  const isLt10M = file.size / 1024 / 1024 < 10
  if (!isLt10M) {
    ElMessage.error('图片大小不能超过 10MB')
    return false
  }
  
  // 检查文件数量
  if (fileList.value.length >= maxFiles) {
    ElMessage.error(`最多只能上传 ${maxFiles} 张图片`)
    return false
  }
  
  return true
}

const onUploadSuccess = (response: any, file: any) => {
  file.status = 'success'
  file.url = response.data.url
  ElMessage.success(`${file.name} 上传成功`)
}

const onUploadError = (error: any, file: any) => {
  file.status = 'error'
  ElMessage.error(`${file.name} 上传失败`)
}

const onFileRemove = (file: any) => {
  const index = fileList.value.indexOf(file)
  if (index > -1) {
    fileList.value.splice(index, 1)
    uploadProgress.value.splice(index, 1)
  }
}

const onExceed = () => {
  ElMessage.warning(`最多只能上传 ${maxFiles} 张图片`)
}

const handleManageCategory = () => {
  categoryVisible.value = true
}

const handleCategorySuccess = () => {
  loadCategories()
}

const loadCategories = async () => {
  try {
    const response = await getImageCategories()
    categoryTree.value = response.data || []
  } catch (error) {
    ElMessage.error('加载分类失败')
  }
}

const resetForm = () => {
  uploadForm.category_id = props.categoryId || 0
  uploadForm.tags = []
  uploadForm.storage_type = 'local'
  uploadForm.storage_permission = 'public'
  fileList.value = []
  uploadProgress.value = []
}

// 监听器
watch(() => props.visible, (newVal) => {
  if (newVal) {
    loadCategories()
    uploadForm.category_id = props.categoryId || 0
  }
})

watch(() => props.categoryId, (newVal) => {
  uploadForm.category_id = newVal || 0
})
</script>

<style lang="scss" scoped>
.form-tip {
  margin-top: 4px;
}

.upload-progress {
  margin-top: 20px;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
  
  h4 {
    margin: 0 0 16px 0;
    font-size: 14px;
    font-weight: 600;
  }
  
  .progress-item {
    margin-bottom: 16px;
    
    &:last-child {
      margin-bottom: 0;
    }
    
    .progress-info {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;
      
      .filename {
        font-size: 14px;
        color: #303133;
      }
      
      .status {
        font-size: 12px;
        color: #909399;
      }
    }
    
    .success-info {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-top: 8px;
      
      .url {
        font-size: 12px;
        color: #67c23a;
        word-break: break-all;
      }
    }
    
    .error-info {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-top: 8px;
      
      .error-message {
        font-size: 12px;
        color: #f56c6c;
      }
    }
  }
}

.dialog-footer {
  text-align: right;
}

:deep(.el-upload--picture-card) {
  width: 120px;
  height: 120px;
  line-height: 120px;
}

:deep(.el-upload__tip) {
  margin-top: 8px;
  font-size: 12px;
  color: #909399;
}
</style>
