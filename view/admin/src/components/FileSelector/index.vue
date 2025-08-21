<template>
  <div class="file-selector">
    <!-- 默认状态：显示加号选择框 -->
    <div v-if="selectedFiles.length === 0" class="selector-empty" @click="showSelectorDialog = true">
      <div class="empty-box">
        <i class="el-icon-plus"></i>
      </div>
    </div>

    <!-- 已选择状态：显示单个文件预览 -->
    <div v-else class="selector-preview">
      <div 
        v-for="fileUrl in selectedFiles.slice(0, 1)" 
        :key="fileUrl" 
        class="preview-item"
      >
        <!-- 图片预览 -->
        <div v-if="isImageUrl(fileUrl)" class="image-preview" @click="showSelectorDialog = true">
          <img 
            :src="fileUrl" 
            :alt="getFileNameFromUrl(fileUrl)"
            class="preview-image"
            @error="handleImageError"
          />
          <!-- hover时显示的操作层 -->
          <div class="image-overlay">
            <div class="overlay-actions">
              <el-button type="primary" size="small" @click.stop="showSelectorDialog = true">
                更换
              </el-button>
              <el-button type="danger" size="small" @click.stop="clearSelection">
                移除
              </el-button>
            </div>
            <!-- 半透明文件信息 -->
            <div class="overlay-info">
              <div class="file-name">{{ getFileNameFromUrl(fileUrl) }}</div>
              <div class="file-meta">
                图片文件
              </div>
            </div>
          </div>
        </div>

        <!-- 其他文件类型预览 -->
        <div v-else class="file-preview" @click="showSelectorDialog = true">
          <div class="file-icon">
            <i class="el-icon-document" style="font-size: 48px; color: #666;"></i>
          </div>
          <!-- hover时显示的操作层 -->
          <div class="file-overlay">
            <div class="overlay-actions">
              <el-button type="primary" size="small" @click.stop="showSelectorDialog = true">
                更换
              </el-button>
              <el-button type="danger" size="small" @click.stop="clearSelection">
                移除
              </el-button>
            </div>
            <!-- 半透明文件信息 -->
            <div class="overlay-info">
              <div class="file-name">{{ getFileNameFromUrl(fileUrl) }}</div>
              <div class="file-meta">
                文件
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 文件选择弹窗 -->
    <el-dialog
      v-model="showSelectorDialog"
      title="选择文件"
      width="1000px"
      :close-on-click-modal="false"
      @open="handleDialogOpen"
      top="5vh"
    >
      <div class="selector-content">
        <!-- 筛选控件 -->
        <div class="selector-header">
          <div class="header-left">
            <el-select
              v-model="selectedCategoryId"
              placeholder="选择分类"
              clearable
              @change="handleCategoryChange"
              style="width: 150px; margin-right: 10px"
            >
              <el-option label="全部分类" :value="0" />
              <el-option
                v-for="category in categories"
                :key="category.category_id"
                :label="category.name"
                :value="category.category_id"
              />
            </el-select>

            <el-select
              v-model="selectedFileType"
              placeholder="文件类型"
              @change="handleFileTypeChange"
              style="width: 120px; margin-right: 10px"
            >
              <el-option 
                v-for="option in availableFileTypes"
                :key="option.value"
                :label="option.label" 
                :value="option.value" 
              />
            </el-select>

            <el-select
              v-model="selectedScope"
              placeholder="文件范围"
              @change="handleScopeChange"
              style="width: 120px; margin-right: 10px"
            >
              <el-option 
                v-for="option in availableScopes"
                :key="option.value"
                :label="option.label" 
                :value="option.value" 
              />
            </el-select>

            <el-input
              v-model="keyword"
              placeholder="搜索文件名"
              @change="handleKeywordChange"
              style="width: 200px; margin-right: 10px"
              clearable
            />

            <el-button @click="loadFiles" type="primary">刷新</el-button>
          </div>

          <div class="header-right">
            <FileUpload 
              :file-type="fileType"
              :categories="categories"
              @upload-success="handleUploadSuccess"
              style="margin-right: 10px"
            />
            <el-button 
              v-auth="'file.manage'"
              @click="showCategoryManager = true"
              type="warning"
              style="margin-right: 10px"
            >
              分类管理
            </el-button>
            <el-button @click="toggleSelection" type="info">
              {{ multiple ? '取消多选' : '开启多选' }}
            </el-button>
          </div>
        </div>

        <!-- 文件网格 -->
        <div class="file-grid" v-loading="loading">
          <div
            v-for="file in files"
            :key="file.file_id"
            class="file-item"
            :class="{
              selected: isSelected(file),
              'is-image': isImageFile(file)
            }"
            @click="selectFile(file)"
          >
            <div class="file-preview">
              <img
                v-if="isImageFile(file)"
                :src="file.url"
                :alt="file.origin_name"
                @error="handleImageError"
              />
              <div v-else class="file-icon">
                <i :class="getFileIcon(file)" style="font-size: 48px; color: #666;"></i>
              </div>
            </div>

            <div class="file-info">
              <div class="file-name" :title="file.origin_name">
                {{ file.origin_name }}
              </div>
              <div class="file-meta">
                <span class="file-size">{{ formatFileSize(file.size) }}</span>
                <span class="file-date">{{ formatDate(file.created_at) }}</span>
              </div>
            </div>

            <!-- 选择状态指示器 -->
            <div v-if="multiple" class="selection-indicator">
              <el-checkbox 
                :model-value="isSelected(file)"
                @change="(checked) => handleCheckboxChange(file, checked)"
              />
            </div>
          </div>
        </div>

        <!-- 分页 -->
        <div class="pagination-container">
          <el-pagination
            v-model:current-page="currentPage"
            v-model:page-size="pageSize"
            :page-sizes="[15, 30, 50, 100]"
            :total="total"
            layout="total, sizes, prev, pager, next, jumper"
            @size-change="handleSizeChange"
            @current-change="handleCurrentChange"
          />
        </div>
      </div>

      <template #footer>
        <span class="dialog-footer">
          <el-button @click="showSelectorDialog = false">取消</el-button>
          <el-button 
            type="primary" 
            @click="confirmSelection"
            :disabled="selectedFiles.length === 0"
          >
            确定选择({{ selectedFiles.length }})
          </el-button>
        </span>
      </template>
    </el-dialog>

    <!-- 分类管理弹窗 -->
    <el-dialog
      v-model="showCategoryManager"
      title="分类管理"
      width="600px"
      :close-on-click-modal="false"
    >
      <div class="category-manager">
        <div class="category-list">
          <div class="category-header">
            <el-button 
              v-auth="'file.manage'"
              type="primary" 
              @click="showAddCategory = true"
            >
              添加分类
            </el-button>
          </div>
          
          <el-tree
            :data="categoryTree"
            :props="{ children: 'children', label: 'name' }"
            node-key="category_id"
            show-checkbox
          >
            <template #default="{ node, data }">
              <span class="category-node">
                <span class="category-label">{{ data.name }}</span>
                <span v-auth="'file.manage'" class="category-actions">
                  <el-button 
                    type="text" 
                    size="small" 
                    @click="editCategory(data)"
                  >
                    编辑
                  </el-button>
                  <el-button 
                    type="text" 
                    size="small" 
                    @click="deleteCategory(data.category_id)"
                    style="color: #f56c6c"
                  >
                    删除
                  </el-button>
                </span>
              </span>
            </template>
          </el-tree>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { ElMessage } from 'element-plus';
import type { CheckboxValueType } from 'element-plus';
import { getFileListApi, getFileCategoriesApi } from '@/api/modules/file';

import FileUpload from './FileUpload.vue';

interface FileItem {
  file_id: number;
  origin_name: string;
  url: string;
  size: number;
  mime_type: string;
  storage_permission: string;
  uploader_type: string;
  uploader_id: number;
  created_at: string;
}

interface CategoryItem {
  category_id: number;
  name: string;
  parent_id: number | null;
}

const props = defineProps<{
  fileType?: string; // 限制文件类型: image, video, document, pdf, all
  configType?: number; // 配置类型枚举值，用于自动确定文件类型限制
  multiple?: boolean; // 是否支持多选
  maxSelect?: number; // 最大选择数量
  modelValue?: string[]; // v-model 支持，直接使用URL字符串数组
}>();

const emit = defineEmits<{
  confirm: [files: string[]];
  cancel: [];
  'update:modelValue': [files: string[]];
}>();

const files = ref<FileItem[]>([]);
const categories = ref<CategoryItem[]>([]);
const selectedFiles = ref<string[]>([]);
const loading = ref(false);
const showCategoryManager = ref(false);
const showAddCategory = ref(false);
const categoryTree = ref<CategoryItem[]>([]);
const showSelectorDialog = ref(false);

// 筛选条件
const selectedCategoryId = ref(0);
const selectedFileType = ref('all');
const selectedScope = ref('all');
const keyword = ref('');

// 分页
const currentPage = ref(1);
const pageSize = ref(15); // 统一使用15作为默认值
const total = ref(0);

// 计算属性
const fileType = computed(() => {
  // 如果指定了 configType，根据配置类型自动确定文件类型
  if (props.configType) {
    switch (props.configType) {
      case 20: // IMAGE - 单图上传
        return 'image';
      case 21: // IMAGES - 多图上传
        return 'image';
      case 22: // VIDEO - 视频上传
        return 'video';
      case 23: // FILE - 单文件上传
        return 'all';
      case 24: // FILES - 多文件上传
        return 'all';
      default:
        return props.fileType || 'all';
    }
  }
  return props.fileType || 'all';
});

const multiple = computed(() => {
  // 如果指定了 configType，根据配置类型自动确定是否多选
  if (props.configType) {
    switch (props.configType) {
      case 21: // IMAGES - 多图上传
      case 24: // FILES - 多文件上传
        return true;
      case 20: // IMAGE - 单图上传
      case 22: // VIDEO - 视频上传
      case 23: // FILE - 单文件上传
        return false;
      default:
        return props.multiple || false;
    }
  }
  return props.multiple || false;
});

const buttonText = computed(() => {
  const type = fileType.value;
  if (type === 'image') return '选择图片';
  if (type === 'video') return '选择视频';
  if (type === 'document') return '选择文档';
  if (type === 'pdf') return '选择PDF';
  return '选择文件';
});

// 根据文件类型限制筛选选项
const availableFileTypes = computed(() => {
  const type = fileType.value;
  if (type === 'image') {
    return [
      { label: '图片', value: 'image' }
    ];
  } else if (type === 'video') {
    return [
      { label: '视频', value: 'video' }
    ];
  } else if (type === 'document') {
    return [
      { label: '文档', value: 'document' }
    ];
  } else if (type === 'pdf') {
    return [
      { label: 'PDF', value: 'pdf' }
    ];
  }
  // 如果是 'all' 类型，显示所有选项
  return [
    { label: '全部类型', value: 'all' },
    { label: '图片', value: 'image' },
    { label: '视频', value: 'video' },
    { label: '文档', value: 'document' },
    { label: 'PDF', value: 'pdf' }
  ];
});

// 根据文件类型限制范围选项
const availableScopes = computed(() => {
  const type = fileType.value;
  if (type === 'image' || type === 'video' || type === 'document' || type === 'pdf') {
    // 对于特定类型，只显示相关范围
    return [
      { label: '全部文件', value: 'all' },
      { label: '我的文件', value: 'own' },
      { label: '公共文件', value: 'public' }
    ];
  }
  return [
    { label: '全部文件', value: 'all' },
    { label: '我的文件', value: 'own' },
    { label: '公共文件', value: 'public' }
  ];
});

// 监听 modelValue 变化
watch(() => props.modelValue, (newValue) => {
  if (newValue && Array.isArray(newValue)) {
    selectedFiles.value = [...newValue];
  }
}, { immediate: true });

// 监听 selectedFiles 变化，同步到 modelValue
watch(selectedFiles, (newValue) => {
  emit('update:modelValue', [...newValue]);
}, { deep: true });

// 监听 fileType 变化，自动更新 selectedFileType
watch(fileType, (newType) => {
  // 如果当前选择的类型不在可用类型中，自动切换到第一个可用类型
  const availableTypes = availableFileTypes.value;
  if (availableTypes.length > 0) {
    const firstType = availableTypes[0].value;
    if (newType !== 'all' && availableTypes.length === 1) {
      // 如果限制为特定类型，强制设置为该类型
      selectedFileType.value = newType;
    } else if (availableTypes.some(opt => opt.value === selectedFileType.value)) {
      // 如果当前选择仍然有效，保持不变
    } else {
      // 否则设置为第一个可用类型
      selectedFileType.value = firstType;
    }
  }
}, { immediate: true });

// 判断是否为图片URL
const isImageUrl = (url: string): boolean => {
  if (!url) return false;
  const ext = url.split('.').pop()?.toLowerCase();
  return ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico'].includes(ext || '');
};

// 从URL中提取文件名
const getFileNameFromUrl = (url: string): string => {
  if (!url) return '';
  return url.split('/').pop()?.split('?')[0] || 'file';
};

// 弹窗打开时加载数据
const handleDialogOpen = () => {
  loadCategories();
  loadFiles();
};

// 加载文件列表
const loadFiles = async () => {
  loading.value = true;
  try {
    const params = {
      page: currentPage.value,
      list_rows: pageSize.value,
      keyword: keyword.value || undefined,
      category_id: selectedCategoryId.value || undefined,
      file_type: fileType.value === 'all' ? undefined : fileType.value,
      scope: selectedScope.value === 'all' ? undefined : selectedScope.value,
    };

    // 强制应用文件类型限制
    if (fileType.value !== 'all') {
      params.file_type = fileType.value;
    }

    const response = await getFileListApi(params);
    console.log('API Response:', response);
    console.log('Response data:', response.data);
    
    // 正确的数据结构：response.data.list
    const data = response.data as any;
    files.value = data?.list || [];
    total.value = data?.total || 0;
    console.log('Files loaded:', files.value);
    console.log('Total:', total.value);
  } catch (error) {
    console.error('Load files error:', error);
  } finally {
    loading.value = false;
  }
};

// 加载分类列表
const loadCategories = async () => {
  try {
    const response = await getFileCategoriesApi();
    // 正确的数据结构：response.data
    const data = response.data as any;
    categories.value = data || [];
    categoryTree.value = buildCategoryTree(categories.value);
  } catch (error) {
    console.error('Load categories error:', error);
  }
};

// 选择文件
const selectFile = (file: FileItem) => {
  const fileUrl = file.url;
  if (multiple.value) {
    const index = selectedFiles.value.findIndex(f => f === fileUrl);
    if (index >= 0) {
      selectedFiles.value.splice(index, 1);
    } else {
      if (props.maxSelect && selectedFiles.value.length >= props.maxSelect) {
        ElMessage.warning(`最多只能选择 ${props.maxSelect} 个文件`);
        return;
      }
      selectedFiles.value.push(fileUrl);
    }
  } else {
    selectedFiles.value = [fileUrl];
  }
};

// 检查文件是否被选中
const isSelected = (file: FileItem) => {
  return selectedFiles.value.some(f => f === file.url);
};

// 判断是否为图片文件
const isImageFile = (file: FileItem) => {
  return file.mime_type && file.mime_type.startsWith('image/');
};

// 获取文件图标
const getFileIcon = (file: FileItem) => {
  if (file.mime_type) {
    if (file.mime_type.startsWith('video/')) return 'el-icon-video-camera';
    if (file.mime_type === 'application/pdf') return 'el-icon-document';
    if (file.mime_type.includes('word')) return 'el-icon-document';
    if (file.mime_type.includes('excel')) return 'el-icon-s-grid';
    if (file.mime_type.includes('powerpoint')) return 'el-icon-s-marketing';
  }
  return 'el-icon-document';
};

// 格式化文件大小
const formatFileSize = (size: number | undefined) => {
  if (!size || size <= 0) return '0 B';
  if (size < 1024) return size + ' B';
  if (size < 1024 * 1024) return (size / 1024).toFixed(1) + ' KB';
  return (size / 1024 / 1024).toFixed(1) + ' MB';
};

// 图片加载错误处理
const handleImageError = (event: Event) => {
  const img = event.target as HTMLImageElement;
  img.style.display = 'none';
};

// 格式化日期
const formatDate = (timestamp: string | undefined) => {
  if (!timestamp) return '未知时间';
  try {
    const date = new Date(timestamp);
    if (isNaN(date.getTime())) return '无效时间';
    
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day} ${hours}:${minutes}`;
  } catch (error) {
    return '时间格式错误';
  }
};

// 切换多选模式
const toggleSelection = () => {
  if (!multiple.value && selectedFiles.value.length > 1) {
    selectedFiles.value = selectedFiles.value.slice(0, 1);
  }
};

// 处理复选框变化
const handleCheckboxChange = (file: FileItem, checked: CheckboxValueType) => {
  if (checked === true || checked === 'true') {
    selectFile(file);
  } else {
    removeSelectedFile(file);
  }
};

// 移除已选择的文件
const removeSelectedFile = (file: FileItem) => {
  const index = selectedFiles.value.findIndex(f => f === file.url);
  if (index !== -1) {
    selectedFiles.value.splice(index, 1);
  }
};

// 清空选择
const clearSelection = () => {
  selectedFiles.value = [];
};

// 筛选条件变化处理
const handleCategoryChange = () => {
  currentPage.value = 1;
  loadFiles();
};

const handleFileTypeChange = () => {
  currentPage.value = 1;
  loadFiles();
};

const handleScopeChange = () => {
  currentPage.value = 1;
  loadFiles();
};

const handleKeywordChange = () => {
  currentPage.value = 1;
  loadFiles();
};

// 分页变化处理
const handleCurrentChange = (page: number) => {
  currentPage.value = page;
  loadFiles();
};

const handleSizeChange = (size: number) => {
  pageSize.value = size;
  currentPage.value = 1;
  loadFiles();
};

// 上传成功处理
const handleUploadSuccess = () => {
  loadFiles();
};

// 确认选择
const confirmSelection = () => {
  emit('confirm', selectedFiles.value);
  showSelectorDialog.value = false;
};

// 分类管理相关方法
const editCategory = (category: CategoryItem) => {
  ElMessage.info(`编辑分类功能开发中: ${category.name}`);
};

const deleteCategory = (categoryId: number) => {
  ElMessage.info(`删除分类功能开发中: ${categoryId}`);
};

// 构建分类树结构
const buildCategoryTree = (categories: CategoryItem[]) => {
  const map: Record<number, CategoryItem & { children?: CategoryItem[] }> = {};
  const tree: CategoryItem[] = [];
  
  // 创建映射
  categories.forEach(category => {
    map[category.category_id] = { ...category, children: [] };
  });
  
  // 构建树结构
  categories.forEach(category => {
    if (category.parent_id === null) {
      tree.push(map[category.category_id]);
    } else if (map[category.parent_id]) {
      map[category.parent_id].children!.push(map[category.category_id]);
    }
  });
  
  return tree;
};

// 初始化
onMounted(() => {
  // 不在这里加载数据，等弹窗打开时再加载
});
</script>

<style lang="scss" scoped>
.file-selector {
  .selector-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 120px;
    height: 120px;
    cursor: pointer;

    .empty-box {
      width: 100%;
      height: 100%;
      border: 2px dashed #d9d9d9;
      border-radius: 8px;
      background: #fafafa;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.3s;

      i {
        font-size: 32px;
        color: #999;
      }

      &:hover {
        border-color: #409eff;
        background: #f0f8ff;

        i {
          color: #409eff;
        }
      }
    }
  }

  .selector-preview {
    .preview-item {
      position: relative;
      width: 120px;
      height: 120px;
      border-radius: 8px;
      overflow: hidden;
      cursor: pointer;

      .image-preview {
        position: relative;
        width: 100%;
        height: 100%;

        .preview-image {
          width: 100%;
          height: 100%;
          object-fit: cover;
          border-radius: 8px;
        }

        .image-overlay {
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: rgba(0, 0, 0, 0.7);
          display: flex;
          flex-direction: column;
          justify-content: space-between;
          padding: 12px;
          opacity: 0;
          transition: opacity 0.3s;

          .overlay-actions {
            display: flex;
            gap: 8px;
            justify-content: center;
          }

          .overlay-info {
            text-align: center;
            color: white;

            .file-name {
              font-size: 12px;
              margin-bottom: 4px;
              overflow: hidden;
              text-overflow: ellipsis;
              white-space: nowrap;
            }

            .file-meta {
              font-size: 11px;
              opacity: 0.8;
            }
          }
        }

        &:hover .image-overlay {
          opacity: 1;
        }
      }

      .file-preview {
        position: relative;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        border: 1px solid #eee;
        border-radius: 8px;

        .file-overlay {
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: rgba(0, 0, 0, 0.7);
          display: flex;
          flex-direction: column;
          justify-content: space-between;
          padding: 12px;
          opacity: 0;
          transition: opacity 0.3s;

          .overlay-actions {
            display: flex;
            gap: 8px;
            justify-content: center;
          }

          .overlay-info {
            text-align: center;
            color: white;

            .file-name {
              font-size: 12px;
              margin-bottom: 4px;
              overflow: hidden;
              text-overflow: ellipsis;
              white-space: nowrap;
            }

            .file-meta {
              font-size: 11px;
              opacity: 0.8;
            }
          }
        }

        &:hover .file-overlay {
          opacity: 1;
        }
      }
    }
  }

  .selector-content {
    .selector-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 1px solid #eee;

      .header-left {
        display: flex;
        align-items: center;
      }

      .header-right {
        display: flex;
        align-items: center;
      }
    }

    .file-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
      gap: 10px;
      margin-bottom: 15px;

      .file-item {
        position: relative;
        border: 2px solid #eee;
        border-radius: 6px;
        padding: 8px;
        cursor: pointer;
        transition: all 0.3s;
        background: #fff;

        &:hover {
          border-color: #409eff;
          box-shadow: 0 2px 8px rgba(64, 158, 255, 0.2);
        }

        &.selected {
          border-color: #409eff;
          background: #f0f8ff;
        }

        .file-preview {
          width: 100%;
          height: 100px;
          display: flex;
          align-items: center;
          justify-content: center;
          background: #f5f5f5;
          border-radius: 4px;
          margin-bottom: 8px;
          overflow: hidden;

          img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
          }

          .file-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
          }
        }

        .file-info {
          .file-name {
            font-size: 13px;
            font-weight: 500;
            color: #333;
            margin-bottom: 4px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            line-height: 1.2;
          }

          .file-meta {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #666;
            line-height: 1.2;

            .file-size {
              color: #999;
            }

            .file-date {
              color: #999;
            }
          }
        }

        .selection-indicator {
          position: absolute;
          top: 8px;
          right: 8px;
          background: rgba(255, 255, 255, 0.9);
          border-radius: 3px;
          padding: 2px;
        }
      }
    }

    .pagination-container {
      display: flex;
      justify-content: center;
      margin-top: 20px;
    }
  }

  // 分类管理样式
  .category-manager {
    .category-header {
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
    }

    .category-node {
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 100%;
      padding-right: 10px;

      .category-label {
        flex: 1;
      }

      .category-actions {
        display: flex;
        gap: 5px;
      }
    }
  }
}
</style>