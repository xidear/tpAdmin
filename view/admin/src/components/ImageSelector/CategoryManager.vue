<template>
  <el-dialog
    v-model="visible"
    title="管理图片分类"
    width="600px"
    :close-on-click-modal="false"
    @close="handleClose"
  >
    <div class="category-manager">
      <!-- 分类列表 -->
      <div class="category-list">
        <div class="list-header">
          <h4>分类列表</h4>
          <el-button type="primary" size="small" @click="handleAddCategory">
            <el-icon><Plus /></el-icon>
            新增分类
          </el-button>
        </div>
        
        <el-table :data="categoryList" border>
          <el-table-column prop="name" label="分类名称" />
          <el-table-column prop="code" label="分类编码" width="120" />
          <el-table-column prop="sort" label="排序" width="80" />
          <el-table-column prop="status" label="状态" width="80">
            <template #default="{ row }">
              <el-tag :type="row.status ? 'success' : 'danger'">
                {{ row.status ? '启用' : '禁用' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="count" label="图片数量" width="100" />
          <el-table-column label="操作" width="150">
            <template #default="{ row }">
              <el-button type="text" size="small" @click="handleEditCategory(row)">
                编辑
              </el-button>
              <el-button type="text" size="small" @click="handleDeleteCategory(row)">
                删除
              </el-button>
            </template>
          </el-table-column>
        </el-table>
      </div>
    </div>

    <!-- 分类表单对话框 -->
    <el-dialog
      v-model="formVisible"
      :title="isEdit ? '编辑分类' : '新增分类'"
      width="500px"
      append-to-body
      :close-on-click-modal="false"
    >
      <el-form
        ref="formRef"
        :model="categoryForm"
        :rules="formRules"
        label-width="100px"
        @submit.prevent
      >
        <el-form-item label="分类名称" prop="name">
          <el-input
            v-model="categoryForm.name"
            placeholder="请输入分类名称"
            maxlength="50"
            show-word-limit
          />
        </el-form-item>
        
        <el-form-item label="分类编码" prop="code">
          <el-input
            v-model="categoryForm.code"
            placeholder="请输入分类编码（可选）"
            maxlength="30"
            show-word-limit
          />
        </el-form-item>
        
        <el-form-item label="父分类" prop="parent_id">
          <el-tree-select
            v-model="categoryForm.parent_id"
            :data="categoryTree"
            :props="treeProps"
            placeholder="请选择父分类"
            clearable
            check-strictly
            :render-after-expand="false"
          />
        </el-form-item>
        
        <el-form-item label="排序" prop="sort">
          <el-input-number
            v-model="categoryForm.sort"
            :min="0"
            :max="9999"
            placeholder="数字越小排序越靠前"
          />
        </el-form-item>
        
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="categoryForm.status">
            <el-radio :label="1">启用</el-radio>
            <el-radio :label="0">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
        
        <el-form-item label="分类描述" prop="description">
          <el-input
            v-model="categoryForm.description"
            type="textarea"
            :rows="3"
            placeholder="请输入分类描述（可选）"
            maxlength="200"
            show-word-limit
          />
        </el-form-item>
      </el-form>
      
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="formVisible = false">取消</el-button>
          <el-button type="primary" @click="handleSubmit" :loading="submitting">
            {{ isEdit ? '更新' : '创建' }}
          </el-button>
        </div>
      </template>
    </el-dialog>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import { 
  getImageCategories, 
  createImageCategory, 
  updateImageCategory, 
 
deleteImageCategory 
} from '@/api/image'

// Props
interface Props {
  visible: boolean
}

const props = withDefaults(defineProps<Props>(), {
  visible: false
})

// Emits
const emit = defineEmits<{
  'update:visible': [value: boolean]
  'success': []
}>()

// 响应式数据
const formVisible = ref(false)
const submitting = ref(false)
const categoryList = ref([])
const categoryTree = ref([])
const currentCategory = ref(null)

// 分类表单
const categoryForm = reactive({
  name: '',
  code: '',
  parent_id: 0,
  sort: 0,
  status: 1,
  description: ''
})

// 表单验证规则
const formRules = {
  name: [
    { required: true, message: '请输入分类名称', trigger: 'blur' },
    { max: 50, message: '分类名称不能超过50个字符', trigger: 'blur' }
  ],
  code: [
    { max: 30, message: '分类编码不能超过30个字符', trigger: 'blur' }
  ],
  sort: [
    { type: 'number', min: 0, message: '排序值不能小于0', trigger: 'change' }
  ],
  status: [
    { required: true, message: '请选择分类状态', trigger: 'change' }
  ]
}

// 分类配置
const treeProps = {
  children: 'children',
  label: 'name',
  value: 'category_id'
}

// 计算属性
const isEdit = computed(() => !!currentCategory.value)

// 方法
const handleClose = () => {
  emit('update:visible', false)
}

const handleAddCategory = () => {
  currentCategory.value = null
  resetForm()
  formVisible.value = true
}

const handleEditCategory = (category: any) => {
  currentCategory.value = category
  initForm(category)
  formVisible.value = true
}

const handleDeleteCategory = async (category: any) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除分类"${category.name}"吗？删除后该分类下的图片将变为未分类状态。`,
      '确认删除',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }
    )
    
    await deleteImageCategory(category.category_id)
    ElMessage.success('删除成功')
    loadCategories()
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

const handleSubmit = async () => {
  try {
    await formRef.value.validate()
    
    submitting.value = true
    
    if (isEdit.value) {
      await updateImageCategory(currentCategory.value.category_id, categoryForm)
      ElMessage.success('更新成功')
    } else {
      await createImageCategory(categoryForm)
      ElMessage.success('创建成功')
    }
    
    formVisible.value = false
    loadCategories()
    emit('success')
  } catch (error) {
    if (error !== false) {
      ElMessage.error(isEdit.value ? '更新失败' : '创建失败')
    }
  } finally {
    submitting.value = false
  }
}

const resetForm = () => {
  categoryForm.name = ''
  categoryForm.code = ''
  categoryForm.parent_id = 0
  categoryForm.sort = 0
  categoryForm.status = 1
  categoryForm.description = ''
  
  if (formRef.value) {
    formRef.value.clearValidate()
  }
}

const initForm = (category: any) => {
  Object.assign(categoryForm, {
    name: category.name || '',
    code: category.code || '',
    parent_id: category.parent_id || 0,
    sort: category.sort || 0,
    status: category.status !== undefined ? category.status : 1,
    description: category.description || ''
  })
}

const loadCategories = async () => {
  try {
    const response = await getImageCategories()
    categoryList.value = response.data || []
    categoryTree.value = response.data || []
  } catch (error) {
    ElMessage.error('加载分类失败')
  }
}

// 监听器
watch(() => props.visible, (newVal) => {
  if (newVal) {
    loadCategories()
  }
})
</script>

<style lang="scss" scoped>
.category-manager {
  .category-list {
    .list-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
      
      h4 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
      }
    }
  }
}

.dialog-footer {
  text-align: right;
}

:deep(.el-tree-select) {
  width: 100%;
}
</style>
