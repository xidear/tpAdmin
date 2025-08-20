<template>
  <div class="file-migration">
    <!-- 迁移配置 -->
    <el-card class="migration-config" shadow="never">
      <template #header>
        <div class="card-header">
          <span>迁移配置</span>
          <el-tag type="info" size="small">用于批量更新文件URL中的域名</el-tag>
        </div>
      </template>
      
      <el-form :model="migrationForm" :rules="migrationRules" label-width="120px" class="migration-form" ref="migrationFormRef">
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="旧域名" prop="old_domain" required>
              <el-input
                v-model="migrationForm.old_domain"
                placeholder="例如: https://old.example.com 或 http://old.example.com"
                clearable
              />
            </el-form-item>
          </el-col>
          
          <el-col :span="12">
            <el-form-item label="新域名" prop="new_domain" required>
              <el-input
                v-model="migrationForm.new_domain"
                placeholder="例如: https://new.example.com 或 http://new.example.com"
                clearable
              />
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="批次大小">
              <el-input-number
                v-model="migrationForm.batch_size"
                :min="0"
                :max="10000"
                :step="100"
                placeholder="每次处理的文件数量"
                @change="handleBatchSizeChange"
              />
              <span class="form-tip">
                {{ migrationForm.batch_size === 0 ? '0表示一次性处理所有文件' : '建议值：1000' }}
              </span>
            </el-form-item>
          </el-col>
          
          <el-col :span="12">
            <el-form-item label="最大批次数">
              <el-input-number
                v-model="migrationForm.max_batches"
                :min="1"
                :max="100"
                :step="1"
                :disabled="migrationForm.batch_size === 0"
                placeholder="最大执行批次数"
              />
              <span class="form-tip">
                {{ migrationForm.batch_size === 0 ? '一次性处理时批次数固定为1' : '建议值：10' }}
              </span>
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-form-item>
          <el-button 
            v-auth="'getMigrationPreview'" 
            type="primary" 
            @click="handlePreview" 
            :loading="previewLoading" 
            icon="View"
          >
            生成预览
          </el-button>
          <el-button @click="handleReset" icon="Refresh">
            重置配置
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 迁移预览 -->
    <el-card v-if="previewData" class="migration-preview" shadow="never">
      <template #header>
        <div class="card-header">
          <span>迁移预览</span>
          <el-tag :type="previewData.error ? 'danger' : 'success'" size="small">
            {{ previewData.error ? '预览失败' : '预览完成' }}
          </el-tag>
        </div>
      </template>
      
      <div v-if="previewData.error" class="preview-error">
        <el-alert
          :title="previewData.error"
          type="error"
          :closable="false"
          show-icon
        />
      </div>
      
      <div v-else class="preview-content">
        <!-- 统计信息 -->
        <el-row :gutter="20" class="preview-stats">
          <el-col :span="6">
            <div class="stat-item">
              <div class="stat-number">{{ previewData.total_count }}</div>
              <div class="stat-label">总文件数</div>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="stat-item">
              <div class="stat-number">{{ previewData.local_count }}</div>
              <div class="stat-label">本地文件</div>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="stat-item">
              <div class="stat-number">{{ previewData.other_count }}</div>
              <div class="stat-label">云存储文件</div>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="stat-item">
              <div class="stat-number">{{ previewData.affected_files.length }}</div>
              <div class="stat-label">
                {{ migrationForm.batch_size === 0 ? '预览样本' : '当前页显示' }}
              </div>
            </div>
          </el-col>
        </el-row>
        
        <!-- 一次性处理提示 -->
        <el-alert
          v-if="migrationForm.batch_size === 0"
          title="一次性处理模式"
          type="info"
          :closable="false"
          show-icon
          class="preview-info"
        >
          <template #default>
            <div>
              <p>当前设置为一次性处理所有文件，预览仅显示前100个文件作为样本。</p>
              <p>执行迁移时将处理全部 {{ previewData.total_count }} 个文件。</p>
            </div>
          </template>
        </el-alert>
        
        <!-- 警告信息 -->
        <el-alert
          v-if="previewData.warning"
          :title="previewData.warning"
          type="warning"
          :closable="false"
          show-icon
          class="preview-warning"
        />
        
        <!-- 文件列表 -->
        <div class="preview-files">
          <div class="preview-files-header">
            <h4>受影响的文件列表</h4>
            <div class="preview-pagination">
              <el-pagination
                v-model:current-page="previewPagination.page"
                :page-size="previewPagination.list_rows"
                :total="previewData.total_count"
                layout="prev, pager, next"
                @current-change="handlePreviewPageChange"
              />
            </div>
          </div>
          
          <el-table :data="previewData.affected_files" style="width: 100%" class="preview-table">
            <el-table-column label="预览" width="80">
              <template #default="{ row }">
                <div class="file-preview">
                  <el-image
                    v-if="isImage(row.mime_type)"
                    :src="row.url"
                    :preview-src-list="[row.url]"
                    fit="cover"
                    style="width: 40px; height: 40px; border-radius: 4px"
                  />
                  <div v-else class="file-icon">
                    <el-icon :size="24" :color="getFileIconColor(row.mime_type)">
                      <component :is="getFileIcon(row.mime_type)" />
                    </el-icon>
                  </div>
                </div>
              </template>
            </el-table-column>
            
            <el-table-column prop="file_name" label="文件名" min-width="200">
              <template #default="{ row }">
                <div class="file-name">
                  <span class="name-text" :title="row.file_name">{{ row.file_name }}</span>
                </div>
              </template>
            </el-table-column>
            
            <el-table-column label="当前URL" min-width="300">
              <template #default="{ row }">
                <div class="url-display">
                  <el-text type="danger">{{ row.url }}</el-text>
                </div>
              </template>
            </el-table-column>
            
            <el-table-column label="迁移后URL" min-width="300">
              <template #default="{ row }">
                <div class="url-display">
                  <el-text type="success">{{ getNewUrl(row.url) }}</el-text>
                </div>
              </template>
            </el-table-column>
            
            <el-table-column prop="storage_type" label="存储类型" width="120">
              <template #default="{ row }">
                <el-tag size="small" type="info">
                  {{ getStorageTypeLabel(row.storage_type) }}
                </el-tag>
              </template>
            </el-table-column>
          </el-table>
        </div>
        
        <!-- 执行迁移按钮 -->
        <div class="migration-actions">
          <el-button
            v-auth="'getMigrationPreview'"
            type="success"
            size="large"
            @click="handleExecuteMigration"
            :loading="migrationLoading"
            icon="Check"
          >
            执行迁移
          </el-button>
          <el-text type="warning" size="small">
            ⚠️ 迁移操作不可逆，请确认预览结果无误后再执行
          </el-text>
        </div>
      </div>
    </el-card>

    <!-- 迁移结果 -->
    <el-card v-if="migrationResult" class="migration-result" shadow="never">
      <template #header>
        <div class="card-header">
          <span>迁移结果</span>
          <el-tag :type="migrationResult.success ? 'success' : 'danger'" size="small">
            {{ migrationResult.success ? '迁移成功' : '迁移失败' }}
          </el-tag>
        </div>
      </template>
      
      <div class="result-content">
        <el-result
          :icon="migrationResult.success ? 'success' : 'error'"
          :title="migrationResult.message"
          :sub-title="getResultSubTitle()"
        >
          <template #extra>
            <div class="result-stats">
              <el-row :gutter="20">
                <el-col :span="8">
                  <div class="result-stat">
                    <div class="stat-number">{{ migrationResult.total_files }}</div>
                    <div class="stat-label">总文件数</div>
                  </div>
                </el-col>
                <el-col :span="8">
                  <div class="result-stat">
                    <div class="stat-number success">{{ migrationResult.migrated_files }}</div>
                    <div class="stat-label">成功迁移</div>
                  </div>
                </el-col>
                <el-col :span="8">
                  <div class="result-stat">
                    <div class="stat-number warning">{{ migrationResult.skipped_files }}</div>
                    <div class="stat-label">跳过文件</div>
                  </div>
                </el-col>
              </el-row>
            </div>
            
            <!-- 错误信息 -->
            <div v-if="migrationResult.errors.length" class="result-errors">
              <h4>错误详情：</h4>
              <el-alert
                v-for="(error, index) in migrationResult.errors"
                :key="index"
                :title="error"
                type="error"
                :closable="false"
                show-icon
                class="error-item"
              />
            </div>
            
            <el-button type="primary" @click="handleReset">
              重新开始
            </el-button>
          </template>
        </el-result>
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { View, Refresh, Check } from '@element-plus/icons-vue'
import { getMigrationPreviewApi, postMigrateUrlsApi } from '@/api/modules/file'
import type { FileManagement } from '@/typings/fileManagement'

// 响应式数据
const previewLoading = ref(false)
const migrationLoading = ref(false)
const previewData = ref<FileManagement.MigrationPreviewResponse | null>(null)
const migrationResult = ref<FileManagement.MigrationResponse | null>(null)

// 迁移表单
const migrationForm = reactive<FileManagement.MigrationParams>({
  old_domain: '',
  new_domain: '',
  batch_size: 1000,
  max_batches: 10
})

// 监听批次大小变化
const handleBatchSizeChange = (value: number | undefined) => {
  if (value === 0) {
    migrationForm.max_batches = 1
  }
}

// 预览分页
const previewPagination = reactive({
  page: 1,
  list_rows: 15
})

// 表单验证规则
const migrationRules = reactive({
  old_domain: [
    { required: true, message: '请输入旧域名', trigger: 'blur' },
    { pattern: /^https?:\/\/.+/, message: '请输入有效的URL格式，包含协议(http://或https://)', trigger: 'blur' }
  ],
  new_domain: [
    { required: true, message: '请输入新域名', trigger: 'blur' },
    { pattern: /^https?:\/\/.+/, message: '请输入有效的URL格式，包含协议(http://或https://)', trigger: 'blur' }
  ]
})

// 表单实例
const migrationFormRef = ref<any>(null)

// 计算属性
const canPreview = computed(() => {
  return migrationForm.old_domain.trim() && migrationForm.new_domain.trim()
})

const canExecute = computed(() => {
  return previewData.value && !previewData.value.error && previewData.value.total_count > 0
})

// 方法
const handlePreview = async () => {
  // 先验证表单
  if (!migrationFormRef.value) return
  
  try {
    await migrationFormRef.value.validate()
  } catch (error) {
    ElMessage.warning('请检查表单输入')
    return
  }
  
  if (!canPreview.value) {
    ElMessage.warning('请填写旧域名和新域名')
    return
  }
  
  // 如果批次大小为0，预览时使用合理的分页大小
  const previewListRows = (migrationForm.batch_size || 0) === 0 ? 100 : (migrationForm.batch_size || 1000)
  
  previewLoading.value = true
  try {
    const params = {
      ...migrationForm,
      page: previewPagination.page,
      list_rows: Math.min(previewListRows, 1000) // 限制最大预览数量为1000
    }
    
    const res = await getMigrationPreviewApi(params)
    previewData.value = res.data as any
    migrationResult.value = null // 清除之前的结果
    
    if (res.data && (res.data as any).error) {
      ElMessage.error(`预览失败: ${(res.data as any).error}`)
    } else {
      ElMessage.success('预览生成成功')
    }
  } catch (error: any) {
    ElMessage.error(`预览失败: ${error.message}`)
  } finally {
    previewLoading.value = false
  }
}

const handlePreviewPageChange = (page: number) => {
  previewPagination.page = page
  handlePreview()
}

const handleExecuteMigration = async () => {
  if (!canExecute.value) {
    ElMessage.warning('请先生成预览')
    return
  }
  
  const batchInfo = migrationForm.batch_size === 0 
    ? `一次性处理所有 ${previewData.value?.total_count} 个文件`
    : `分批处理，每批 ${migrationForm.batch_size} 个文件，最多 ${migrationForm.max_batches} 批`
  
  try {
    await ElMessageBox.confirm(
      `确定要执行迁移吗？\n\n${batchInfo}\n\n这将更新文件URL中的域名地址。`,
      '确认执行迁移',
      {
        confirmButtonText: '确定执行',
        cancelButtonText: '取消',
        type: 'warning'
      }
    )
    
    migrationLoading.value = true
    const res = await postMigrateUrlsApi(migrationForm)
    migrationResult.value = res.data as any
    
    if (res.data && (res.data as any).success) {
      ElMessage.success('迁移执行成功')
      // 重新生成预览
      await handlePreview()
    } else {
      ElMessage.error(`迁移失败: ${(res.data as any).message || '未知错误'}`)
    }
  } catch (error: any) {
    if (error !== 'cancel') {
      ElMessage.error(`迁移失败: ${error.message}`)
    }
  } finally {
    migrationLoading.value = false
  }
}

const handleReset = () => {
  Object.assign(migrationForm, {
    old_domain: '',
    new_domain: '',
    batch_size: 1000,
    max_batches: 10
  })
  previewPagination.page = 1
  previewData.value = null
  migrationResult.value = null
}

// 工具方法
const getNewUrl = (oldUrl: string) => {
  if (!migrationForm.old_domain || !migrationForm.new_domain) return oldUrl
  return oldUrl.replace(
    new RegExp(migrationForm.old_domain, 'g'),
    migrationForm.new_domain
  )
}

const getResultSubTitle = () => {
  if (!migrationResult.value) return ''
  
  const { total_files, migrated_files, skipped_files } = migrationResult.value
  return `共处理 ${total_files} 个文件，成功迁移 ${migrated_files} 个，跳过 ${skipped_files} 个`
}

const isImage = (mimeType: string) => mimeType?.startsWith('image/')

const getFileIcon = (mimeType: string) => {
  if (isImage(mimeType)) return 'Picture'
  if (mimeType?.includes('pdf')) return 'Document'
  if (mimeType?.includes('word') || mimeType?.includes('document')) return 'Document'
  if (mimeType?.includes('excel') || mimeType?.includes('spreadsheet')) return 'Grid'
  return 'Document'
}

const getFileIconColor = (mimeType: string) => {
  if (isImage(mimeType)) return '#67C23A'
  if (mimeType?.includes('pdf')) return '#F56C6C'
  return '#909399'
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
</script>

<style scoped>
.file-migration {
  padding: 20px;
}

.migration-config,
.migration-preview,
.migration-result {
  margin-bottom: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.migration-form {
  max-width: 800px;
}

.form-tip {
  margin-left: 10px;
  color: #909399;
  font-size: 12px;
}

.preview-stats {
  margin-bottom: 20px;
}

.stat-item {
  text-align: center;
  padding: 20px;
  background: #f5f7fa;
  border-radius: 6px;
}

.stat-number {
  font-size: 24px;
  font-weight: bold;
  color: #409eff;
  margin-bottom: 8px;
}

.stat-label {
  color: #606266;
  font-size: 14px;
}

.preview-warning {
  margin-bottom: 20px;
}

.preview-info {
  margin-bottom: 20px;
}

.preview-files {
  margin-bottom: 20px;
}

.preview-files-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.preview-files-header h4 {
  margin: 0;
  color: #303133;
}

.preview-table {
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
  width: 40px;
  height: 40px;
  background: #f5f7fa;
  border-radius: 4px;
}

.file-name {
  display: flex;
  align-items: center;
}

.name-text {
  max-width: 180px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.url-display {
  word-break: break-all;
  line-height: 1.4;
}

.migration-actions {
  text-align: center;
  padding: 20px;
  background: #f5f7fa;
  border-radius: 6px;
}

.migration-actions .el-text {
  margin-left: 16px;
}

.result-content {
  text-align: center;
}

.result-stats {
  margin: 20px 0;
}

.result-stat {
  text-align: center;
  padding: 16px;
  background: #f5f7fa;
  border-radius: 6px;
}

.result-stat .stat-number {
  font-size: 20px;
  font-weight: bold;
  margin-bottom: 8px;
}

.result-stat .stat-number.success {
  color: #67c23a;
}

.result-stat .stat-number.warning {
  color: #e6a23c;
}

.result-stat .stat-label {
  color: #606266;
  font-size: 12px;
}

.result-errors {
  margin: 20px 0;
  text-align: left;
}

.result-errors h4 {
  margin: 0 0 12px 0;
  color: #f56c6c;
}

.error-item {
  margin-bottom: 8px;
}
</style>
