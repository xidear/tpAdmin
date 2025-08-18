<template>
  <div class="image-selector">
    <!-- 触发按钮 -->
    <div class="trigger-area" @click="handleOpen">
      <slot name="trigger">
        <el-button type="primary" :icon="Picture">
          {{ selectedImages.length > 0 ? `已选择 ${selectedImages.length} 张图片` : '选择图片' }}
        </el-button>
      </slot>
    </div>

    <!-- 图片选择对话框 -->
    <el-dialog
      v-model="visible"
      title="选择图片"
      width="1000px"
      :close-on-click-modal="false"
      @close="handleClose"
    >
      <div class="image-selector-content">
        <!-- 左侧分类和搜索 -->
        <div class="left-panel">
          <div class="search-section">
            <el-input
              v-model="searchKeyword"
              placeholder="搜索图片名称"
              clearable
              @input="handleSearch"
            >
              <template #prefix>
                <el-icon><Search /></el-icon>
              </template>
            </el-input>
          </div>

          <div class="category-section">
            <h4>图片分类</h4>
            <el-tree
              :data="categoryTree"
              :props="categoryProps"
              :default-expand-all="true"
              @node-click="handleCategoryClick"
            >
              <template #default="{ node, data }">
                <span class="category-node">
                  <span>{{ node.label }}</span>
                  <span class="category-count">({{ data.count || 0 }})</span>
                </span>
              </template>
            </el-tree>
          </div>
        </div>

        <!-- 右侧图片展示区 -->
        <div class="right-panel">
          <div class="panel-header">
            <div class="header-left">
              <h4>{{ currentCategory?.name || '全部图片' }}</h4>
              <span class="image-count">共 {{ totalCount }} 张图片</span>
            </div>
            <div class="header-right">
              <el-button type="primary" @click="handleUpload">
                <el-icon><Upload /></el-icon>
                上传新图片
              </el-button>
            </div>
          </div>

          <!-- 图片网格 -->
          <div class="image-grid">
            <div
              v-for="image in imageList"
              :key="image.file_id"
              class="image-item"
              :class="{ selected: isSelected(image) }"
              @click="handleImageClick(image)"
            >
              <div class="image-wrapper">
                <el-image
                  :src="image.url"
                  :alt="image.origin_name"
                  fit="cover"
                  loading="lazy"
                  @error="handleImageError"
                />
                <div class="image-overlay">
                  <el-checkbox
                    :model-value="isSelected(image)"
                    @change="(checked) => handleImageSelect(image, checked)"
                    @click.stop
                  />
                </div>
              </div>
              <div class="image-info">
                <div class="image-name" :title="image.origin_name">
                  {{ image.origin_name }}
                </div>
                <div class="image-meta">
                  <span class="file-size">{{ formatFileSize(image.size) }}</span>
                  <span class="storage-type">{{ getStorageTypeName(image.storage_type) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- 分页 -->
          <div v-if="totalCount > pageSize" class="pagination-wrapper">
            <el-pagination
              v-model:current-page="currentPage"
              v-model:page-size="pageSize"
              :total="totalCount"
              :page-sizes="[12, 24, 48, 96]"
              layout="total, sizes, prev, pager, next, jumper"
              @size-change="handleSizeChange"
              @current-change="handlePageChange"
            />
          </div>
        </div>
      </div>

      <!-- 底部操作按钮 -->
      <template #footer>
        <div class="dialog-footer">
          <div class="footer-left">
            <span class="selected-count">已选择 {{ selectedImages.length }} 张图片</span>
            <el-button v-if="selectedImages.length > 0" type="text" @click="clearSelection">
              清空选择
            </el-button>
          </div>
          <div class="footer-right">
            <el-button @click="handleClose">取消</el-button>
            <el-button type="primary" @click="handleConfirm" :disabled="selectedImages.length === 0">
              确定选择
            </el-button>
          </div>
        </div>
      </template>
    </el-dialog>

    <!-- 图片上传对话框 -->
    <ImageUpload
      v-model:visible="uploadVisible"
      :category-id="currentCategory?.category_id"
      @success="handleUploadSuccess"
    />

    <!-- 分类管理对话框 -->
    <CategoryManager
      v-model:visible="categoryVisible"
      @success="handleCategorySuccess"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Picture, Search, Upload } from '@element-plus/icons-vue'
import ImageUpload from './ImageUpload.vue'
import CategoryManager from './CategoryManager.vue'
import { getImageList, getImageCategories } from '@/api/image'
import { formatFileSize } from '@/utils/format'

// Props
interface Props {
  modelValue?: any[]
  multiple?: boolean
  maxCount?: number
  categoryId?: number
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: () => [],
  multiple: true,
  maxCount: 10,
  categoryId: 0
})

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: any[]]
  'change': [value: any[]]
}>()

// 响应式数据
const visible = ref(false)
const uploadVisible = ref(false)
const categoryVisible = ref(false)
const searchKeyword = ref('')
const currentPage = ref(1)
const pageSize = ref(24)
const totalCount = ref(0)
const imageList = ref([])
const categoryTree = ref([])
const currentCategory = ref(null)

// 选中的图片
const selectedImages = ref<any[]>([])

// 分类配置
const categoryProps = {
  children: 'children',
  label: 'name',
  value: 'category_id'
}

// 计算属性
const canSelectMore = computed(() => {
  if (!props.multiple) return selectedImages.value.length === 0
  return selectedImages.value.length < props.maxCount
})

// 方法
const handleOpen = () => {
  visible.value = true
  loadCategories()
  loadImages()
}

const handleClose = () => {
  visible.value = false
  // 恢复原始选择状态
  selectedImages.value = [...props.modelValue]
}

const handleConfirm = () => {
  emit('update:modelValue', selectedImages.value)
  emit('change', selectedImages.value)
  visible.value = false
}

const handleUpload = () => {
  uploadVisible.value = true
}

const handleUploadSuccess = (image: any) => {
  // 上传成功后自动选中新图片
  if (canSelectMore.value) {
    selectedImages.value.push(image)
  }
  // 重新加载图片列表
  loadImages()
}

const handleCategoryClick = (data: any) => {
  currentCategory.value = data
  currentPage.value = 1
  loadImages()
}

const handleSearch = () => {
  currentPage.value = 1
  loadImages()
}

const handleImageClick = (image: any) => {
  if (props.multiple) {
    if (isSelected(image)) {
      removeImage(image)
    } else if (canSelectMore.value) {
      addImage(image)
    } else {
      ElMessage.warning(`最多只能选择 ${props.maxCount} 张图片`)
    }
  } else {
    // 单选模式
    selectedImages.value = [image]
  }
}

const handleImageSelect = (image: any, checked: boolean) => {
  if (checked && canSelectMore.value) {
    addImage(image)
  } else if (!checked) {
    removeImage(image)
  }
}

const addImage = (image: any) => {
  if (!isSelected(image)) {
    selectedImages.value.push(image)
  }
}

const removeImage = (image: any) => {
  const index = selectedImages.value.findIndex(item => item.file_id === image.file_id)
  if (index > -1) {
    selectedImages.value.splice(index, 1)
  }
}

const isSelected = (image: any) => {
  return selectedImages.value.some(item => item.file_id === image.file_id)
}

const clearSelection = () => {
  selectedImages.value = []
}

const handleSizeChange = (size: number) => {
  pageSize.value = size
  currentPage.value = 1
  loadImages()
}

const handlePageChange = (page: number) => {
  currentPage.value = page
  loadImages()
}

const handleImageError = () => {
  // 图片加载失败处理
}

const handleCategorySuccess = () => {
  loadCategories()
}

const loadImages = async () => {
  try {
    const params = {
      page: currentPage.value,
      list_rows: pageSize.value,
      keyword: searchKeyword.value,
      category_id: currentCategory.value?.category_id || props.categoryId
    }
    
    const response = await getImageList(params)
    imageList.value = response.data.list || []
    totalCount.value = response.data.total || 0
  } catch (error) {
    ElMessage.error('加载图片列表失败')
  }
}

const loadCategories = async () => {
  try {
    const response = await getImageCategories()
    categoryTree.value = response.data || []
  } catch (error) {
    ElMessage.error('加载分类失败')
  }
}

const formatFileSize = (size: number) => {
  return formatFileSize(size)
}

const getStorageTypeName = (type: string) => {
  const typeMap = {
    local: '本地',
    aliyun_oss: '阿里云OSS',
    qcloud_cos: '腾讯云COS',
    aws_s3: 'AWS S3'
  }
  return typeMap[type] || type
}

// 监听器
watch(() => props.modelValue, (newVal) => {
  selectedImages.value = [...(newVal || [])]
}, { immediate: true })

// 生命周期
onMounted(() => {
  // 初始化选中状态
  selectedImages.value = [...(props.modelValue || [])]
})
</script>

<style lang="scss" scoped>
.image-selector {
  display: inline-block;
}

.trigger-area {
  cursor: pointer;
}

.image-selector-content {
  display: flex;
  gap: 20px;
  height: 600px;
}

.left-panel {
  width: 250px;
  border-right: 1px solid #e4e7ed;
  
  .search-section {
    margin-bottom: 20px;
  }
  
  .category-section {
    h4 {
      margin: 0 0 12px 0;
      font-size: 14px;
      font-weight: 600;
    }
    
    .category-node {
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 100%;
      
      .category-count {
        color: #909399;
        font-size: 12px;
      }
    }
  }
}

.right-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  
  .panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    
    .header-left {
      h4 {
        margin: 0 0 4px 0;
        font-size: 16px;
        font-weight: 600;
      }
      
      .image-count {
        color: #909399;
        font-size: 12px;
      }
    }
  }
  
  .image-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 12px;
    flex: 1;
    overflow-y: auto;
    
    .image-item {
      border: 2px solid transparent;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s;
      
      &:hover {
        border-color: #409eff;
        
        .image-overlay {
          opacity: 1;
        }
      }
      
      &.selected {
        border-color: #409eff;
        background-color: #f0f9ff;
      }
      
      .image-wrapper {
        position: relative;
        aspect-ratio: 1;
        border-radius: 6px;
        overflow: hidden;
        
        .el-image {
          width: 100%;
          height: 100%;
        }
        
        .image-overlay {
          position: absolute;
          top: 8px;
          right: 8px;
          opacity: 0;
          transition: opacity 0.3s;
          background: rgba(255, 255, 255, 0.9);
          border-radius: 4px;
          padding: 2px;
        }
      }
      
      .image-info {
        padding: 8px 4px;
        
        .image-name {
          font-size: 12px;
          color: #303133;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
          margin-bottom: 4px;
        }
        
        .image-meta {
          display: flex;
          justify-content: space-between;
          font-size: 11px;
          color: #909399;
        }
      }
    }
  }
  
  .pagination-wrapper {
    margin-top: 20px;
    text-align: center;
  }
}

.dialog-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  
  .footer-left {
    .selected-count {
      margin-right: 12px;
      color: #409eff;
      font-weight: 500;
    }
  }
}
</style>
