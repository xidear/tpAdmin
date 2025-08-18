<template>
  <el-dialog
    :title="isEdit ? '编辑部门' : '新增部门'"
    :visible="visible"
    width="600px"
    @close="handleClose"
  >
    <el-form
      ref="formRef"
      :model="form"
      :rules="rules"
      label-width="100px"
      @submit.prevent
    >
      <el-form-item label="部门名称" prop="name">
        <el-input
          v-model="form.name"
          placeholder="请输入部门名称"
          maxlength="100"
          show-word-limit
        />
      </el-form-item>
      
      <el-form-item label="部门编码" prop="code">
        <el-input
          v-model="form.code"
          placeholder="请输入部门编码（可选）"
          maxlength="50"
          show-word-limit
        />
      </el-form-item>
      
      <el-form-item label="父部门" prop="parent_id">
        <el-tree-select
          v-model="form.parent_id"
          :data="departmentOptions"
          :props="treeProps"
          placeholder="请选择父部门"
          clearable
          check-strictly
          :render-after-expand="false"
        />
      </el-form-item>
      
      <el-form-item label="排序" prop="sort">
        <el-input-number
          v-model="form.sort"
          :min="0"
          :max="9999"
          placeholder="数字越小排序越靠前"
        />
      </el-form-item>
      
      <el-form-item label="状态" prop="status">
        <el-radio-group v-model="form.status">
          <el-radio :label="1">启用</el-radio>
          <el-radio :label="0">禁用</el-radio>
        </el-radio-group>
      </el-form-item>
      
      <el-form-item label="部门描述" prop="description">
        <el-input
          v-model="form.description"
          type="textarea"
          :rows="3"
          placeholder="请输入部门描述（可选）"
          maxlength="500"
          show-word-limit
        />
      </el-form-item>
    </el-form>
    
    <template #footer>
      <div class="dialog-footer">
        <el-button @click="handleClose">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="loading">
          {{ isEdit ? '更新' : '创建' }}
        </el-button>
      </div>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, reactive, watch, computed, onMounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { createDepartment, updateDepartment, getDepartmentList } from '@/api/department'

// Props
interface Props {
  visible: boolean
  department?: any
  parentDepartment?: any
}

const props = withDefaults(defineProps<Props>(), {
  visible: false,
  department: null,
  parentDepartment: null
})

// Emits
const emit = defineEmits<{
  'update:visible': [value: boolean]
  'success': []
}>()

// 响应式数据
const formRef = ref()
const loading = ref(false)
const departmentOptions = ref([])

// 表单数据
const form = reactive({
  name: '',
  code: '',
  parent_id: 0,
  sort: 0,
  status: 1,
  description: ''
})

// 表单验证规则
const rules = {
  name: [
    { required: true, message: '请输入部门名称', trigger: 'blur' },
    { max: 100, message: '部门名称不能超过100个字符', trigger: 'blur' }
  ],
  code: [
    { max: 50, message: '部门编码不能超过50个字符', trigger: 'blur' }
  ],
  parent_id: [
    { type: 'number', min: 0, message: '父部门ID无效', trigger: 'change' }
  ],
  sort: [
    { type: 'number', min: 0, message: '排序值不能小于0', trigger: 'change' }
  ],
  status: [
    { required: true, message: '请选择部门状态', trigger: 'change' }
  ]
}

// 树形配置
const treeProps = {
  children: 'children',
  label: 'name',
  value: 'department_id'
}

// 计算属性
const isEdit = computed(() => !!props.department)

// 方法
const loadDepartmentOptions = async () => {
  try {
    const response = await getDepartmentList()
    // 添加顶级部门选项
    departmentOptions.value = [
      { department_id: 0, name: '顶级部门', children: response.data }
    ]
  } catch (error) {
    ElMessage.error('加载部门选项失败')
  }
}

const resetForm = () => {
  form.name = ''
  form.code = ''
  form.parent_id = 0
  form.sort = 0
  form.status = 1
  form.description = ''
  
  if (formRef.value) {
    formRef.value.clearValidate()
  }
}

const initForm = () => {
  if (props.department) {
    // 编辑模式
    Object.assign(form, {
      name: props.department.name || '',
      code: props.department.code || '',
      parent_id: props.department.parent_id || 0,
      sort: props.department.sort || 0,
      status: props.department.status !== undefined ? props.department.status : 1,
      description: props.department.description || ''
    })
  } else if (props.parentDepartment) {
    // 添加子部门模式
    form.parent_id = props.parentDepartment.department_id
  } else {
    // 新增顶级部门模式
    resetForm()
  }
}

const handleSubmit = async () => {
  try {
    await formRef.value.validate()
    
    loading.value = true
    
    if (isEdit.value) {
      await updateDepartment(props.department.department_id, form)
      ElMessage.success('更新成功')
    } else {
      await createDepartment(form)
      ElMessage.success('创建成功')
    }
    
    emit('success')
  } catch (error) {
    if (error !== false) {
      ElMessage.error(isEdit.value ? '更新失败' : '创建失败')
    }
  } finally {
    loading.value = false
  }
}

const handleClose = () => {
  emit('update:visible', false)
  resetForm()
}

// 监听器
watch(() => props.visible, (newVal) => {
  if (newVal) {
    initForm()
  }
})

// 生命周期
onMounted(() => {
  loadDepartmentOptions()
})
</script>

<style lang="scss" scoped>
.dialog-footer {
  text-align: right;
}

:deep(.el-tree-select) {
  width: 100%;
}
</style>
