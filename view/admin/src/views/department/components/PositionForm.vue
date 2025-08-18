<template>
  <el-dialog
    :title="isEdit ? '编辑职位' : '新增职位'"
    :visible="visible"
    width="500px"
    @close="handleClose"
  >
    <el-form
      ref="formRef"
      :model="form"
      :rules="rules"
      label-width="100px"
      @submit.prevent
    >
      <el-form-item label="职位名称" prop="name">
        <el-input
          v-model="form.name"
          placeholder="请输入职位名称"
          maxlength="100"
          show-word-limit
        />
      </el-form-item>
      
      <el-form-item label="职位编码" prop="code">
        <el-input
          v-model="form.code"
          placeholder="请输入职位编码（可选）"
          maxlength="50"
          show-word-limit
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
      
      <el-form-item label="职位描述" prop="description">
        <el-input
          v-model="form.description"
          type="textarea"
          :rows="3"
          placeholder="请输入职位描述（可选）"
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
import { ref, reactive, watch, computed } from 'vue'
import { ElMessage } from 'element-plus'
import { createPosition, updatePosition } from '@/api/department'

// Props
interface Props {
  visible: boolean
  position?: any
  departmentId?: number
}

const props = withDefaults(defineProps<Props>(), {
  visible: false,
  position: null,
  departmentId: 0
})

// Emits
const emit = defineEmits<{
  'update:visible': [value: boolean]
  'success': []
}>()

// 响应式数据
const formRef = ref()
const loading = ref(false)

// 表单数据
const form = reactive({
  department_id: 0,
  name: '',
  code: '',
  sort: 0,
  status: 1,
  description: ''
})

// 表单验证规则
const rules = {
  name: [
    { required: true, message: '请输入职位名称', trigger: 'blur' },
    { max: 100, message: '职位名称不能超过100个字符', trigger: 'blur' }
  ],
  code: [
    { max: 50, message: '职位编码不能超过50个字符', trigger: 'blur' }
  ],
  sort: [
    { type: 'number', min: 0, message: '排序值不能小于0', trigger: 'change' }
  ],
  status: [
    { required: true, message: '请选择职位状态', trigger: 'change' }
  ]
}

// 计算属性
const isEdit = computed(() => !!props.position)

// 方法
const resetForm = () => {
  form.department_id = props.departmentId || 0
  form.name = ''
  form.code = ''
  form.sort = 0
  form.status = 1
  form.description = ''
  
  if (formRef.value) {
    formRef.value.clearValidate()
  }
}

const initForm = () => {
  if (props.position) {
    // 编辑模式
    Object.assign(form, {
      department_id: props.position.department_id || props.departmentId || 0,
      name: props.position.name || '',
      code: props.position.code || '',
      sort: props.position.sort || 0,
      status: props.position.status !== undefined ? props.position.status : 1,
      description: props.position.description || ''
    })
  } else {
    // 新增模式
    resetForm()
  }
}

const handleSubmit = async () => {
  try {
    await formRef.value.validate()
    
    loading.value = true
    
    if (isEdit.value) {
      await updatePosition(props.position.position_id, form)
      ElMessage.success('更新成功')
    } else {
      await createPosition(form)
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

watch(() => props.departmentId, (newVal) => {
  if (newVal && !isEdit.value) {
    form.department_id = newVal
  }
})
</script>

<style lang="scss" scoped>
.dialog-footer {
  text-align: right;
}
</style>
