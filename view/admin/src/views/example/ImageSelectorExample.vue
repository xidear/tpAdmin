<template>
  <div class="image-selector-example">
    <div class="example-header">
      <h2>图片选择器组件示例</h2>
      <p class="description">展示图片选择器组件的各种使用方式</p>
    </div>

    <el-row :gutter="20">
      <!-- 单选模式 -->
      <el-col :span="12">
        <el-card class="example-card">
          <template #header>
            <div class="card-header">
              <span>单选模式</span>
              <el-tag type="info">single</el-tag>
            </div>
          </template>
          
          <div class="example-content">
            <p class="example-desc">只能选择一张图片，适合头像、主图等场景</p>
            
            <ImageSelector
              v-model="singleImage"
              :multiple="false"
              @change="handleSingleChange"
            />
            
            <div v-if="singleImage.length > 0" class="selected-preview">
              <h4>已选择的图片：</h4>
              <div class="image-preview">
                <el-image
                  :src="singleImage[0].url"
                  :alt="singleImage[0].origin_name"
                  fit="cover"
                  style="width: 100px; height: 100px; border-radius: 8px;"
                />
                <div class="image-info">
                  <p><strong>文件名：</strong>{{ singleImage[0].origin_name }}</p>
                  <p><strong>大小：</strong>{{ formatFileSize(singleImage[0].size) }}</p>
                  <p><strong>类型：</strong>{{ singleImage[0].mime_type }}</p>
                </div>
              </div>
            </div>
          </div>
        </el-card>
      </el-col>

      <!-- 多选模式 -->
      <el-col :span="12">
        <el-card class="example-card">
          <template #header>
            <div class="card-header">
              <span>多选模式</span>
              <el-tag type="success">multiple</el-tag>
            </div>
          </template>
          
          <div class="example-content">
            <p class="example-desc">可以选择多张图片，适合相册、产品图等场景</p>
            
            <ImageSelector
              v-model="multipleImages"
              :multiple="true"
              :max-count="5"
              @change="handleMultipleChange"
            />
            
            <div v-if="multipleImages.length > 0" class="selected-preview">
              <h4>已选择的图片 ({{ multipleImages.length }}/5)：</h4>
              <div class="images-grid">
                <div
                  v-for="(image, index) in multipleImages"
                  :key="image.file_id"
                  class="image-item"
                >
                  <el-image
                    :src="image.url"
                    :alt="image.origin_name"
                    fit="cover"
                    style="width: 80px; height: 80px; border-radius: 6px;"
                  />
                  <div class="image-info">
                    <p class="filename">{{ image.origin_name }}</p>
                    <p class="size">{{ formatFileSize(image.size) }}</p>
                  </div>
                  <el-button
                    type="danger"
                    size="small"
                    circle
                    @click="removeImage(index)"
                  >
                    <el-icon><Delete /></el-icon>
                  </el-button>
                </div>
              </div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <el-row :gutter="20" style="margin-top: 20px;">
      <!-- 指定分类 -->
      <el-col :span="12">
        <el-card class="example-card">
          <template #header>
            <div class="card-header">
              <span>指定分类</span>
              <el-tag type="warning">category</el-tag>
            </div>
          </template>
          
          <div class="example-content">
            <p class="example-desc">限制只能选择指定分类下的图片</p>
            
            <div class="category-selector">
              <el-select v-model="selectedCategory" placeholder="选择图片分类" @change="handleCategoryChange">
                <el-option label="全部分类" :value="0" />
                <el-option label="产品图片" :value="2" />
                <el-option label="营销图片" :value="3" />
                <el-option label="装饰图片" :value="4" />
              </el-select>
            </div>
            
            <ImageSelector
              v-model="categoryImages"
              :category-id="selectedCategory"
              :multiple="true"
              :max-count="3"
              @change="handleCategoryImagesChange"
            />
            
            <div v-if="categoryImages.length > 0" class="selected-preview">
              <h4>已选择的图片：</h4>
              <div class="images-grid">
                <div
                  v-for="image in categoryImages"
                  :key="image.file_id"
                  class="image-item"
                >
                  <el-image
                    :src="image.url"
                    :alt="image.origin_name"
                    fit="cover"
                    style="width: 80px; height: 80px; border-radius: 6px;"
                  />
                  <div class="image-info">
                    <p class="filename">{{ image.origin_name }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </el-card>
      </el-col>

      <!-- 自定义触发按钮 -->
      <el-col :span="12">
        <el-card class="example-card">
          <template #header>
            <div class="card-header">
              <span>自定义触发按钮</span>
              <el-tag type="primary">custom trigger</el-tag>
            </div>
          </template>
          
          <div class="example-content">
            <p class="example-desc">使用自定义的触发按钮样式</p>
            
            <ImageSelector
              v-model="customImages"
              :multiple="true"
              :max-count="2"
              @change="handleCustomChange"
            >
              <template #trigger>
                <div class="custom-trigger">
                  <el-button type="success" :icon="Picture">
                    {{ customImages.length > 0 ? `已选择 ${customImages.length} 张图片` : '选择图片' }}
                  </el-button>
                  <el-button v-if="customImages.length > 0" type="info" size="small" @click="clearCustomImages">
                    清空
                  </el-button>
                </div>
              </template>
            </ImageSelector>
            
            <div v-if="customImages.length > 0" class="selected-preview">
              <h4>已选择的图片：</h4>
              <div class="images-grid">
                <div
                  v-for="image in customImages"
                  :key="image.file_id"
                  class="image-item"
                >
                  <el-image
                    :src="image.url"
                    :alt="image.origin_name"
                    fit="cover"
                    style="width: 80px; height: 80px; border-radius: 6px;"
                  />
                  <div class="image-info">
                    <p class="filename">{{ image.origin_name }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 使用说明 -->
    <el-card style="margin-top: 20px;">
      <template #header>
        <span>使用说明</span>
      </template>
      
      <div class="usage-guide">
        <h4>组件特性：</h4>
        <ul>
          <li><strong>支持单选/多选：</strong>通过 <code>multiple</code> 属性控制</li>
          <li><strong>限制选择数量：</strong>通过 <code>max-count</strong> 属性设置最大选择数量</li>
          <li><strong>分类筛选：</strong>通过 <code>category-id</code> 属性限制图片分类</li>
          <li><strong>自定义触发按钮：</strong>通过 <code>#trigger</code> 插槽自定义按钮样式</li>
          <li><strong>图片上传：</strong>支持直接上传新图片，自动分类管理</li>
          <li><strong>分类管理：</strong>支持创建、编辑、删除图片分类</li>
        </ul>
        
        <h4>基本用法：</h4>
        <pre><code>&lt;ImageSelector
  v-model="selectedImages"
  :multiple="true"
  :max-count="5"
  @change="handleChange"
/&gt;</code></pre>
        
        <h4>Props 说明：</h4>
        <el-table :data="propsTable" border>
          <el-table-column prop="prop" label="属性名" width="150" />
          <el-table-column prop="type" label="类型" width="100" />
          <el-table-column prop="default" label="默认值" width="100" />
          <el-table-column prop="description" label="说明" />
        </el-table>
        
        <h4>Events 说明：</h4>
        <el-table :data="eventsTable" border>
          <el-table-column prop="event" label="事件名" width="150" />
          <el-table-column prop="params" label="参数" width="200" />
          <el-table-column prop="description" label="说明" />
        </el-table>
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { Picture, Delete } from '@element-plus/icons-vue'
import ImageSelector from '@/components/ImageSelector/index.vue'
import { formatFileSize } from '@/utils/format'

// 响应式数据
const singleImage = ref([])
const multipleImages = ref([])
const categoryImages = ref([])
const customImages = ref([])
const selectedCategory = ref(0)

// 事件处理
const handleSingleChange = (images: any[]) => {
  console.log('单选图片变化:', images)
  ElMessage.success(`已选择图片: ${images[0]?.origin_name || '无'}`)
}

const handleMultipleChange = (images: any[]) => {
  console.log('多选图片变化:', images)
  ElMessage.success(`已选择 ${images.length} 张图片`)
}

const handleCategoryImagesChange = (images: any[]) => {
  console.log('分类图片变化:', images)
  ElMessage.success(`已选择 ${images.length} 张分类图片`)
}

const handleCustomChange = (images: any[]) => {
  console.log('自定义图片变化:', images)
  ElMessage.success(`已选择 ${images.length} 张自定义图片`)
}

const handleCategoryChange = (categoryId: number) => {
  console.log('分类变化:', categoryId)
  // 清空已选择的图片
  categoryImages.value = []
}

const removeImage = (index: number) => {
  multipleImages.value.splice(index, 1)
  ElMessage.success('图片已移除')
}

const clearCustomImages = () => {
  customImages.value = []
  ElMessage.success('已清空选择的图片')
}

// Props 说明表格数据
const propsTable = [
  { prop: 'modelValue', type: 'Array', default: '[]', description: '选中的图片数组' },
  { prop: 'multiple', type: 'Boolean', default: 'true', description: '是否支持多选' },
  { prop: 'maxCount', type: 'Number', default: '10', description: '最大选择数量' },
  { prop: 'categoryId', type: 'Number', default: '0', description: '限制图片分类ID' }
]

// Events 说明表格数据
const eventsTable = [
  { event: 'update:modelValue', params: 'value: Array', description: '选中图片变化时触发' },
  { event: 'change', params: 'value: Array', description: '选中图片变化时触发' }
]
</script>

<style lang="scss" scoped>
.image-selector-example {
  padding: 20px;
}

.example-header {
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

.example-card {
  margin-bottom: 20px;
  
  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  
  .example-content {
    .example-desc {
      margin: 0 0 16px 0;
      color: #666;
      font-size: 14px;
    }
    
    .selected-preview {
      margin-top: 20px;
      padding: 16px;
      background: #f8f9fa;
      border-radius: 8px;
      
      h4 {
        margin: 0 0 12px 0;
        font-size: 14px;
        font-weight: 600;
      }
      
      .image-preview {
        display: flex;
        gap: 16px;
        align-items: flex-start;
        
        .image-info {
          p {
            margin: 4px 0;
            font-size: 12px;
            color: #666;
          }
        }
      }
      
      .images-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 12px;
        
        .image-item {
          position: relative;
          text-align: center;
          
          .image-info {
            margin-top: 8px;
            
            .filename {
              margin: 4px 0;
              font-size: 11px;
              color: #666;
              overflow: hidden;
              text-overflow: ellipsis;
              white-space: nowrap;
            }
            
            .size {
              margin: 0;
              font-size: 10px;
              color: #999;
            }
          }
          
          .el-button {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 20px;
            height: 20px;
            padding: 0;
          }
        }
      }
    }
  }
}

.category-selector {
  margin-bottom: 16px;
}

.custom-trigger {
  display: flex;
  gap: 8px;
  align-items: center;
}

.usage-guide {
  h4 {
    margin: 20px 0 12px 0;
    font-size: 16px;
    font-weight: 600;
    
    &:first-child {
      margin-top: 0;
    }
  }
  
  ul {
    margin: 0 0 20px 0;
    padding-left: 20px;
    
    li {
      margin: 8px 0;
      line-height: 1.6;
    }
  }
  
  pre {
    background: #f8f9fa;
    padding: 16px;
    border-radius: 6px;
    overflow-x: auto;
    margin: 16px 0;
    
    code {
      font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
      font-size: 13px;
    }
  }
  
  .el-table {
    margin: 16px 0;
  }
}
</style>
