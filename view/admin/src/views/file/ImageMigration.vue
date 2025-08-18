<template>
  <div class="image-migration-container">
    <div class="migration-header">
      <h2>图片地址迁移</h2>
      <p class="description">批量迁移图片URL地址，支持域名变更、存储迁移等场景</p>
    </div>

    <el-card class="migration-card">
      <template #header>
        <div class="card-header">
          <span>迁移配置</span>
        </div>
      </template>
      
      <el-form :model="migrationForm" label-width="120px" @submit.prevent>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="旧域名" required>
              <el-input
                v-model="migrationForm.oldDomain"
                placeholder="请输入旧域名，如：https://old.example.com"
                clearable
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="新域名" required>
              <el-input
                v-model="migrationForm.newDomain"
                placeholder="请输入新域名，如：https://new.example.com"
                clearable
              />
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="每批处理数量">
              <el-input-number
                v-model="migrationForm.batchSize"
                :min="100"
                :max="5000"
                :step="100"
                placeholder="建议1000-2000"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="最大批次数">
              <el-input-number
                v-model="migrationForm.maxBatches"
                :min="1"
                :max="50"
                :step="1"
                placeholder="建议10-20"
              />
            </el-form-item>
          </el-col>
        </el-row>
        
        <el-form-item>
          <el-button type="primary" @click="handlePreview" :loading="previewLoading">
            <el-icon><View /></el-icon>
            生成预览
          </el-button>
          <el-button type="success" @click="handleMigrate" :loading="migrateLoading" :disabled="!previewData.total_count">
            <el-icon><Upload /></el-icon>
            执行迁移
          </el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 预览结果 -->
    <el-card v-if="previewData.total_count > 0" class="preview-card">
      <template #header>
        <div class="card-header">
          <span>迁移预览</span>
          <el-tag type="info">共 {{ previewData.total_count }} 个文件需要迁移</el-tag>
        </div>
      </template>
      
      <div class="preview-stats">
        <el-row :gutter="20">
          <el-col :span="6">
            <div class="stat-item">
              <div class="stat-number">{{ previewData.total_count }}</div>
              <div class="stat-label">总文件数</div>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="stat-item">
              <div class="stat-number">{{ previewData.local_count }}</div>
              <div class="stat-label">本地存储</div>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="stat-item">
              <div class="stat-number">{{ previewData.other_count }}</div>
              <div class="stat-label">其他存储</div>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="stat-item">
              <div class="stat-number">{{ previewData.current_page_count || 0 }}</div>
              <div class="stat-label">当前页显示</div>
            </div>
          </el-col>
        </el-row>
      </div>
      
      <div v-if="previewData.warning" class="preview-warning">
        <el-alert :title="previewData.warning" type="warning" show-icon />
      </div>
      
      <el-table :data="previewData.affected_files" border>
        <el-table-column prop="table" label="数据表" width="100" />
        <el-table-column prop="id" label="记录ID" width="100" />
        <el-table-column prop="file_name" label="文件名" />
        <el-table-column prop="storage_type" label="存储类型" width="100" />
        <el-table-column prop="is_local" label="本地存储" width="100">
          <template #default="{ row }">
            <el-tag :type="row.is_local ? 'success' : 'info'">
              {{ row.is_local ? '是' : '否' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="旧URL" min-width="200">
          <template #default="{ row }">
            <el-tooltip :content="row.old_url" placement="top">
              <span class="url-text">{{ row.old_url }}</span>
            </el-tooltip>
          </template>
        </el-table-column>
        <el-table-column label="新URL" min-width="200">
          <template #default="{ row }">
            <el-tooltip :content="row.new_url" placement="top">
              <span class="url-text">{{ row.new_url }}</span>
            </el-tooltip>
          </template>
        </el-table-column>
      </el-table>
      
      <div v-if="previewData.has_more" class="preview-pagination">
        <el-pagination
          v-model:current-page="currentPage"
          :page-size="pageSize"
          :total="previewData.total_count"
          layout="total, prev, pager, next, jumper"
          @current-change="handlePageChange"
        />
      </div>
    </el-card>

    <!-- 迁移结果 -->
    <el-card v-if="migrationResult.total_files > 0" class="result-card">
      <template #header>
        <div class="card-header">
          <span>迁移结果</span>
          <el-tag :type="migrationResult.errors.length > 0 ? 'warning' : 'success'">
            {{ migrationResult.errors.length > 0 ? '部分成功' : '迁移完成' }}
          </el-tag>
        </div>
      </template>
      
      <div class="result-stats">
        <el-row :gutter="20">
          <el-col :span="6">
            <div class="stat-item">
              <div class="stat-number">{{ migrationResult.total_files }}</div>
              <div class="stat-label">总文件数</div>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="stat-item">
              <div class="stat-number">{{ migrationResult.migrated_files }}</div>
              <div class="stat-label">成功迁移</div>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="stat-item">
              <div class="stat-number">{{ migrationResult.skipped_files }}</div>
              <div class="stat-label">跳过文件</div>
            </div>
          </el-col>
          <el-col :span="6">
            <div class="stat-item">
              <div class="stat-number">{{ migrationResult.batches_processed }}</div>
              <div class="stat-label">处理批次数</div>
            </div>
          </el-col>
        </el-row>
      </div>
      
      <div v-if="migrationResult.warning" class="result-warning">
        <el-alert :title="migrationResult.warning" type="warning" show-icon />
      </div>
      
      <div v-if="migrationResult.errors.length > 0" class="result-errors">
        <h4>错误信息：</h4>
        <el-alert
          v-for="(error, index) in migrationResult.errors"
          :key="index"
          :title="error"
          type="error"
          show-icon
          :closable="false"
        />
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { View, Upload } from '@element-plus/icons-vue'
import { getMigrationPreview, migrateUrls } from '@/api/file'

// 响应式数据
const previewLoading = ref(false)
const migrateLoading = ref(false)
const currentPage = ref(1)
const pageSize = ref(15)

// 迁移表单
const migrationForm = reactive({
  oldDomain: '',
  newDomain: '',
  batchSize: 1000,
  maxBatches: 10
})

// 预览数据
const previewData = reactive({
  old_domain: '',
  new_domain: '',
  affected_files: [],
  total_count: 0,
  local_count: 0,
  other_count: 0,
  page: 1,
  list_rows: 15,
  has_more: false,
  warning: '',
  error: ''
})

// 迁移结果
const migrationResult = reactive({
  total_files: 0,
  migrated_files: 0,
  skipped_files: 0,
  errors: [],
  batches_processed: 0,
  current_batch: 0,
  warning: ''
})

// 方法
const handlePreview = async () => {
  if (!migrationForm.oldDomain || !migrationForm.newDomain) {
    ElMessage.warning('请填写旧域名和新域名')
    return
  }
  
  try {
    previewLoading.value = true
    const response = await getMigrationPreview({
      old_domain: migrationForm.oldDomain,
      new_domain: migrationForm.newDomain,
      page: currentPage.value,
      list_rows: pageSize.value
    })
    
    Object.assign(previewData, response.data)
    ElMessage.success('预览生成成功')
  } catch (error) {
    ElMessage.error('预览生成失败')
  } finally {
    previewLoading.value = false
  }
}

const handleMigrate = async () => {
  if (!migrationForm.oldDomain || !migrationForm.newDomain) {
    ElMessage.warning('请填写旧域名和新域名')
    return
  }
  
  try {
    await ElMessageBox.confirm(
      `确定要执行迁移吗？\n旧域名：${migrationForm.oldDomain}\n新域名：${migrationForm.newDomain}\n\n此操作不可逆，请确认无误后继续。`,
      '确认迁移',
      {
        confirmButtonText: '确定迁移',
        cancelButtonText: '取消',
        type: 'warning'
      }
    )
    
    migrateLoading.value = true
    const response = await migrateUrls({
      old_domain: migrationForm.oldDomain,
      new_domain: migrationForm.newDomain,
      batch_size: migrationForm.batchSize,
      max_batches: migrationForm.maxBatches
    })
    
    Object.assign(migrationResult, response.data)
    ElMessage.success('迁移执行完成')
    
    // 重新生成预览
    await handlePreview()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('迁移执行失败')
    }
  } finally {
    migrateLoading.value = false
  }
}

const handlePageChange = (page: number) => {
  currentPage.value = page
  handlePreview()
}

// 生命周期
onMounted(() => {
  // 可以从配置或其他地方获取默认域名
})
</script>

<style lang="scss" scoped>
.image-migration-container {
  padding: 20px;
}

.migration-header {
  margin-bottom: 20px;
  
  h2 {
    margin: 0 0 8px 0;
    font-size: 24px;
    font-weight: 600;
  }
  
  .description {
    margin: 0;
    color: #666;
    font-size: 14px;
  }
}

.migration-card,
.preview-card,
.result-card {
  margin-bottom: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.preview-stats,
.result-stats {
  margin-bottom: 20px;
  
  .stat-item {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    
    .stat-number {
      font-size: 24px;
      font-weight: 600;
      color: #409eff;
      margin-bottom: 8px;
    }
    
    .stat-label {
      color: #666;
      font-size: 14px;
    }
  }
}

.preview-warning,
.result-warning {
  margin-bottom: 20px;
}

.result-errors {
  margin-top: 20px;
  
  h4 {
    margin: 0 0 12px 0;
    color: #f56c6c;
  }
  
  .el-alert {
    margin-bottom: 8px;
  }
}

.preview-pagination {
  margin-top: 20px;
  text-align: center;
}

.url-text {
  display: inline-block;
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
