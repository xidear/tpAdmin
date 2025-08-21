<template>
  <div class="file-list">
    <!-- 搜索和操作栏 -->
    <div class="toolbar">
      <div class="search-area">
        <el-input
          v-model="searchForm.keyword"
          placeholder="搜索原始文件名"
          style="width: 200px; margin-right: 10px"
          clearable
          @keyup.enter="handleSearch"
        >
          <template #prefix>
            <el-icon><Search /></el-icon>
          </template>
        </el-input>
        
        <el-select
          v-model="searchForm.mime_type"
          placeholder="文件类型"
          style="width: 120px; margin-right: 10px"
          clearable
        >
          <el-option label="图片" value="image" />
          <el-option label="视频" value="video" />
          <el-option label="文档" value="application" />
          <el-option label="其他" value="other" />
        </el-select>
        
        <el-select
          v-model="searchForm.storage_type"
          placeholder="存储类型"
          style="width: 120px; margin-right: 10px"
          clearable
        >
          <el-option label="本地存储" value="local" />
          <el-option label="七牛云" value="qiniu" />
          <el-option label="阿里云OSS" value="aliyun_oss" />
          <el-option label="腾讯云COS" value="qcloud_cos" />
          <el-option label="AWS S3" value="aws_s3" />
        </el-select>
        
        <el-button type="primary" @click="handleSearch" icon="Search">
          搜索
        </el-button>
        <el-button @click="handleReset" icon="Refresh">
          重置
        </el-button>
      </div>
      
      <div class="action-area">
        <!-- 暂时移除上传按钮 -->
        <!-- <el-button 
          v-auth="'create'" 
          type="success" 
          @click="showUploadDialog = true" 
          icon="Upload"
        >
          上传文件
        </el-button> -->
        <el-button 
          v-auth="'batchDelete'" 
          type="warning" 
          @click="handleBatchDelete" 
          icon="Delete" 
          :disabled="!selectedFiles.length"
        >
          批量删除
        </el-button>
      </div>
    </div>

    <!-- 文件列表表格 -->
    <el-table
      v-loading="loading"
      :data="fileList"
      @selection-change="handleSelectionChange"
      style="width: 100%"
      class="file-table"
    >
      <el-table-column type="selection" width="55" />
      
      <el-table-column prop="origin_name" label="文件名" min-width="200">
        <template #default="{ row }">
          <div class="file-name">
            <span class="name-text" :title="row.origin_name">{{ row.origin_name }}</span>
            <el-tag v-if="row.storage_permission === 'private'" size="small" type="warning">
              私有
            </el-tag>
          </div>
        </template>
      </el-table-column>
      
      <el-table-column label="当前URL" min-width="300">
        <template #default="{ row }">
          <div class="url-display">
            <el-text type="info" size="small">{{ row.url }}</el-text>
          </div>
        </template>
      </el-table-column>
      
      <el-table-column prop="file_name" label="存储文件名" min-width="200">
        <template #default="{ row }">
          <div class="storage-name">
            <el-text type="warning" size="small">{{ row.file_name }}</el-text>
          </div>
        </template>
      </el-table-column>
      
      <el-table-column prop="mime_type" label="类型" width="120">
        <template #default="{ row }">
          <el-tag size="small" :type="getMimeTypeTagType(row.mime_type)">
            {{ getMimeTypeLabel(row.mime_type) }}
          </el-tag>
        </template>
      </el-table-column>
      
      <el-table-column prop="size" label="大小" width="100">
        <template #default="{ row }">
          {{ formatFileSize(row.size) }}
        </template>
      </el-table-column>
      
      <el-table-column prop="storage_type" label="存储位置" width="120">
        <template #default="{ row }">
          <el-tag size="small" type="info">
            {{ getStorageTypeLabel(row.storage_type) }}
          </el-tag>
        </template>
      </el-table-column>
      
      <el-table-column prop="created_at" label="上传时间" width="180">
        <template #default="{ row }">
          {{ formatDate(row.created_at) }}
        </template>
      </el-table-column>
      
      <el-table-column label="操作" width="150" fixed="right">
        <template #default="{ row }">
          <el-button size="small" @click="handlePreview(row)" icon="View">
            预览
          </el-button>
          <el-button 
            v-auth="'delete'" 
            size="small" 
            type="danger" 
            @click="handleDelete(row)" 
            icon="Delete"
          >
            删除
          </el-button>
        </template>
      </el-table-column>
    </el-table>

    <!-- 分页 -->
    <div class="pagination-wrapper">
      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.pageSize"
        :page-sizes="[10, 20, 50, 100]"
        :total="pagination.total"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="handleSizeChange"
        @current-change="handleCurrentChange"
      />
    </div>

    <!-- 暂时移除文件上传对话框 -->
    <!-- <el-dialog
      v-model="showUploadDialog"
      title="上传文件"
      width="500px"
      :close-on-click-modal="false"
    >
      <el-form :model="uploadForm" label-width="80px">
        <el-form-item label="选择文件">
          <el-upload
            ref="uploadRef"
            :auto-upload="false"
            :on-change="handleFileChange"
            :file-list="uploadFileList"
            :limit="5"
            multiple
            drag
          >
            <el-icon class="el-icon--upload"><upload-filled /></el-icon>
            <div class="el-upload__text">
              将文件拖到此处，或<em>点击上传</em>
            </div>
            <template #tip>
              <div class="el-upload__tip">
                支持 jpg/png/gif/pdf/doc/docx/xls/xlsx/mp4 等格式，单个文件不超过 10MB
              </div>
            </template>
          </el-upload>
        </el-form-item>
        
        <el-form-item label="上传目录">
          <el-input v-model="uploadForm.upload_dir" placeholder="uploads" />
        </el-form-item>
        
        <el-form-item label="权限设置">
          <el-radio-group v-model="uploadForm.permission">
            <el-radio label="public">公开</el-radio>
            <el-radio label="private">私有</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="showUploadDialog = false">取消</el-button>
          <el-button type="primary" @click="handleUpload" :loading="uploading">
            开始上传
          </el-button>
        </span>
      </template>
    </el-dialog> -->

    <!-- 文件预览对话框 -->
    <el-dialog
      v-model="showPreviewDialog"
      title="文件预览"
      width="800px"
      :close-on-click-modal="false"
    >
      <div class="file-preview-content">
        <div v-if="previewFile" class="preview-info">
          <h3>{{ previewFile.origin_name }}</h3>
          <p><strong>类型：</strong>{{ previewFile.mime_type }}</p>
          <p><strong>大小：</strong>{{ formatFileSize(previewFile.size) }}</p>
          <p><strong>存储：</strong>{{ getStorageTypeLabel(previewFile.storage_type) }}</p>
          <p><strong>上传时间：</strong>{{ formatDate(previewFile.created_at) }}</p>
        </div>
        
        <div class="preview-content">
          <!-- 图片预览 -->
          <el-image
            v-if="isImage(previewFile?.mime_type || '')"
            :src="previewFile?.url || ''"
            fit="contain"
            style="max-width: 100%; max-height: 400px"
          />
          
          <!-- 视频预览 -->
          <video
            v-else-if="isVideo(previewFile?.mime_type || '')"
            :src="previewFile?.url || ''"
            controls
            style="max-width: 100%; max-height: 400px"
          />
          
          <!-- 其他文件 -->
          <div v-else class="other-file-preview">
            <el-icon :size="80" color="#909399">
              <component :is="getFileIcon(previewFile?.mime_type || '')" />
            </el-icon>
            <p>此文件类型不支持预览</p>
            <el-button type="primary" @click="downloadFile(previewFile)">
              下载文件
            </el-button>
          </div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox, ElUpload } from 'element-plus'
import { Search, Upload, Delete, View, Refresh, UploadFilled } from '@element-plus/icons-vue'
import { getFileListApi } from '@/api/modules/file'
import type { FileManagement } from '@/typings/fileManagement'

// 响应式数据
const loading = ref(false)
const fileList = ref<FileManagement.FileInfo[]>([])
const selectedFiles = ref<FileManagement.FileInfo[]>([])
// const showUploadDialog = ref(false) // 暂时移除
const showPreviewDialog = ref(false)
const previewFile = ref<FileManagement.FileInfo | null>(null)
// const uploading = ref(false) // 暂时移除
// const uploadRef = ref<InstanceType<typeof ElUpload>>() // 暂时移除
// const uploadFileList = ref<any[]>([]) // 暂时移除

// 搜索表单
const searchForm = reactive<FileManagement.FileListParams>({
  keyword: '',
  mime_type: '',
  storage_type: '',
  page: 1,
  pageSize: 20
})

// 分页信息
const pagination = reactive({
  page: 1,
  pageSize: 20,
  total: 0
})

// 上传表单 - 暂时移除
// const uploadForm = reactive({
//   upload_dir: 'uploads',
//   permission: 'public'
// })

// 计算属性
const hasSelectedFiles = computed(() => selectedFiles.value.length > 0)

// 方法
const loadFileList = async () => {
  loading.value = true
  try {
    // 构建查询参数，过滤掉空值
    const queryParams: Record<string, any> = {
      page: pagination.page,
      list_rows: pagination.pageSize
    }
    
    // 只添加非空的筛选条件
    if (searchForm.keyword && searchForm.keyword.trim()) {
      queryParams.keyword = searchForm.keyword.trim()
    }
    if (searchForm.mime_type && searchForm.mime_type !== '') {
      queryParams.mime_type = searchForm.mime_type
    }
    if (searchForm.storage_type && searchForm.storage_type !== '') {
      queryParams.storage_type = searchForm.storage_type
    }
    
    const res = await getFileListApi(queryParams)
    
    const data = res.data as any
    fileList.value = data?.list || []
    pagination.total = data?.total || 0
  } catch (error: any) {
    ElMessage.error(`加载文件列表失败: ${error.message}`)
  } finally {
    loading.value = false
  }
}

const handleSearch = () => {
  pagination.page = 1
  loadFileList()
}

const handleReset = () => {
  // 重置搜索表单
  searchForm.keyword = ''
  searchForm.mime_type = ''
  searchForm.storage_type = ''
  
  // 重置分页
  pagination.page = 1
  
  // 重新加载列表
  loadFileList()
}

const handleSelectionChange = (selection: FileManagement.FileInfo[]) => {
  selectedFiles.value = selection
}

const handleSizeChange = (size: number) => {
  pagination.pageSize = size
  pagination.page = 1
  loadFileList()
}

const handleCurrentChange = (page: number) => {
  pagination.page = page
  loadFileList()
}

// 暂时移除上传相关方法
// const handleFileChange = (file: any) => {
//   // 文件选择处理
//   console.log('File selected:', file)
// }

// const handleUpload = async () => {
//   if (!uploadFileList.value.length) {
//     ElMessage.warning('请选择要上传的文件')
//     return
//   }

//   uploading.value = true
//   try {
//     for (const file of uploadFileList.value) {
//       const formData = new FormData()
//       formData.append('file', file.raw)
      
//       const params = {
//         file_type: 'all',
//         upload_dir: uploadForm.upload_dir,
//         permission: uploadForm.permission
//       }
      
//       const res = await fileManagementApi.uploadFile(formData, params)
//       console.log('Upload response:', res)
//     }
    
//     ElMessage.success('文件上传成功')
//     showUploadDialog.value = false
//     uploadFileList.value = []
//     loadFileList()
//   } catch (error: any) {
//     ElMessage.error(`文件上传失败: ${error.message}`)
//   } finally {
//     uploadingLoading.value = false
//   }
// }

const handlePreview = (file: FileManagement.FileInfo) => {
  previewFile.value = file
  showPreviewDialog.value = true
}

const handleDelete = async (file: FileManagement.FileInfo) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除文件 "${file.origin_name}" 吗？`,
      '确认删除',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }
    )
    
    // TODO: 调用删除接口
    ElMessage.success('文件删除成功')
    loadFileList()
  } catch {
    // 用户取消
  }
}

const handleBatchDelete = async () => {
  if (!selectedFiles.value.length) {
    ElMessage.warning('请选择要删除的文件')
    return
  }

  try {
    await ElMessageBox.confirm(
      `确定要删除选中的 ${selectedFiles.value.length} 个文件吗？`,
      '确认批量删除',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }
    )
    
    // TODO: 调用批量删除接口
    ElMessage.success('批量删除成功')
    selectedFiles.value = []
    loadFileList()
  } catch {
    // 用户取消
  }
}

const downloadFile = (file: FileManagement.FileInfo | null) => {
  if (!file) return
  
  const link = document.createElement('a')
  link.href = file.url
  link.download = file.origin_name
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

// 工具方法
const isImage = (mimeType: string) => mimeType?.startsWith('image/')
const isVideo = (mimeType: string) => mimeType?.startsWith('video/')

const getFileIcon = (mimeType: string) => {
  if (isImage(mimeType)) return 'Picture'
  if (isVideo(mimeType)) return 'VideoPlay'
  if (mimeType?.includes('pdf')) return 'Document'
  if (mimeType?.includes('word') || mimeType?.includes('document')) return 'Document'
  if (mimeType?.includes('excel') || mimeType?.includes('spreadsheet')) return 'Grid'
  return 'Document'
}

const getFileIconColor = (mimeType: string) => {
  if (isImage(mimeType)) return '#67C23A'
  if (isVideo(mimeType)) return '#E6A23C'
  if (mimeType?.includes('pdf')) return '#F56C6C'
  return '#909399'
}

const getMimeTypeLabel = (mimeType: string) => {
  if (isImage(mimeType)) return '图片'
  if (isVideo(mimeType)) return '视频'
  if (mimeType?.includes('pdf')) return 'PDF'
  if (mimeType?.includes('word') || mimeType?.includes('document')) return '文档'
  if (mimeType?.includes('excel') || mimeType?.includes('spreadsheet')) return '表格'
  return '其他'
}

const getMimeTypeTagType = (mimeType: string) => {
  if (isImage(mimeType)) return 'success'
  if (isVideo(mimeType)) return 'warning'
  if (mimeType?.includes('pdf')) return 'danger'
  return 'info'
}

const getStorageTypeLabel = (storageType: string) => {
  const typeMap: Record<string, string> = {
    local: '本地存储',
    qiniu: '七牛云',
    aliyun_oss: '阿里云OSS',
    qcloud_cos: '腾讯云COS',
    aws_s3: 'AWS S3'
  }
  return typeMap[storageType] || storageType
}

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatDate = (dateStr: string) => {
  return new Date(dateStr).toLocaleString('zh-CN')
}

// 生命周期
onMounted(() => {
  loadFileList()
})
</script>

<style scoped>
.file-list {
  padding: 20px;
}

.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding: 16px;
  background: #f5f7fa;
  border-radius: 6px;
}

.search-area {
  display: flex;
  align-items: center;
}

.action-area {
  display: flex;
  gap: 10px;
}

.file-table {
  margin-bottom: 20px;
}

.file-preview {
  display: flex;
  justify-content: center;
  align-items: center;
}

.file-icon {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 50px;
  height: 50px;
  background: #f5f7fa;
  border-radius: 4px;
}

.file-name {
  display: flex;
  align-items: center;
  gap: 8px;
}

.name-text {
  max-width: 150px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.url-display {
  max-width: 300px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.storage-name {
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.pagination-wrapper {
  display: flex;
  justify-content: center;
  margin-top: 20px;
}

.file-preview-content {
  text-align: center;
}

.preview-info {
  text-align: left;
  margin-bottom: 20px;
  padding: 16px;
  background: #f5f7fa;
  border-radius: 6px;
}

.preview-info h3 {
  margin: 0 0 12px 0;
  color: #303133;
}

.preview-info p {
  margin: 8px 0;
  color: #606266;
}

.preview-content {
  min-height: 200px;
  display: flex;
  justify-content: center;
  align-items: center;
}

.other-file-preview {
  text-align: center;
}

.other-file-preview p {
  margin: 16px 0;
  color: #909399;
}
</style>
