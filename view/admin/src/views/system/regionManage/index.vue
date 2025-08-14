<template>
  <div class="region-manage-container">
    <div class="toolbar">
      <el-button 
        type="primary" 
        size="large" 
        v-auth="'create'"
        @click="handleAdd"
        :loading="addButtonLoading"
      >
        <el-icon><Plus /></el-icon>
        添加地区
      </el-button>
      <el-button 
        type="warning" 
        size="large" 
        @click="handleClearCache"
        v-auth="'refreshCache'"
        :loading="clearCacheLoading"
      >
        <el-icon><Refresh /></el-icon>
        清除地区缓存
      </el-button>
    </div>
    
    <el-tree-v2
      :data="tableData"
      :props="{
        value: 'region_id',
        label: 'name',
        children: 'children'
      }"
      :height="600"
      :item-size="32"
      :default-expanded-keys="expandedKeys"
      highlight-current
      @node-click="handleNodeClick"
    >
      <template #default="{ node, data }">
        <div class="custom-tree-node">
          <div class="node-info">
            <span class="node-name">{{ node.label }}</span>
            <span class="node-type">{{ getTypeLabel(data.type) }}</span>
          </div>
          <div class="node-actions">
            <el-button size="small" type="primary" v-auth="'read'" link @click.stop="handleDetail(data)">详情</el-button>
            <el-button size="small" type="primary" v-auth="'update'" link @click.stop="handleEdit(data)">编辑</el-button>
            <el-button size="small" type="danger" v-auth="'delete'" link @click.stop="handleDelete(data)">删除</el-button>
          </div>
        </div>
      </template>
    </el-tree-v2>

    <!-- 详情弹窗 -->
    <el-dialog
      v-model="detailDialogVisible"
      title="地区详情"
      width="600px"
      :close-on-click-modal="false"
    >
      <el-descriptions :column="2" border>
        <el-descriptions-item label="地区ID">{{ detailData.region_id }}</el-descriptions-item>
        <el-descriptions-item label="地区名称">{{ detailData.name }}</el-descriptions-item>
        <el-descriptions-item label="地区编码">{{ detailData.code }}</el-descriptions-item>
        <el-descriptions-item label="地区类型">{{ getTypeLabel(detailData.type) }}</el-descriptions-item>
        <el-descriptions-item label="父级ID">{{ detailData.parent_id || '无' }}</el-descriptions-item>
        <el-descriptions-item label="创建时间">{{ detailData.created_at }}</el-descriptions-item>
        <el-descriptions-item label="更新时间">{{ detailData.updated_at }}</el-descriptions-item>
     </el-descriptions>
      <template #footer>
        <el-button @click="detailDialogVisible = false">关闭</el-button>
      </template>
    </el-dialog>

    <!-- 编辑弹窗 -->
    <el-dialog
      v-model="editDialogVisible"
      title="编辑地区"
      width="500px"
      :close-on-click-modal="false"
    >
      <el-form
        ref="editFormRef"
        :model="editForm"
        :rules="editRules"
        label-width="100px"
      >
        <el-form-item label="地区名称" prop="name">
          <el-input v-model="editForm.name" placeholder="请输入地区名称" />
        </el-form-item>
        <el-form-item label="地区编码" prop="code">
          <el-input v-model="editForm.code" placeholder="请输入地区编码" />
        </el-form-item>
        <el-form-item label="地区类型" prop="type">
          <el-select v-model="editForm.type" placeholder="请选择地区类型">
            <el-option label="国家" value="country" />
            <el-option label="省份" value="province" />
            <el-option label="城市" value="city" />
            <el-option label="区县" value="area" />
            <el-option label="街道" value="street" />
            <el-option label="社区" value="community" />
          </el-select>
        </el-form-item>
        <el-form-item label="父级地区" prop="parent_id">
          <el-tree-select
            v-model="editForm.parent_id"
            :data="parentOptions"
            :props="{
              value: 'region_id',
              label: 'name',
              children: 'children',
              disabled: 'disabled'
            }"
            :filter-method="filterParentNode"
            :filterable="true"
            :clearable="true"
            placeholder="请选择父级地区"
            :default-expanded-keys="getTreeSelectProps().expandedKeys"
            :node-key="'region_id'"
            :render-after-expand="false"
            :show-checkbox="false"
            :check-strictly="true"
            :disabled="editLoading"
            style="width: 100%"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="editDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleEditSubmit" :loading="editLoading">确定</el-button>
      </template>
    </el-dialog>

    <!-- 新增弹窗 -->
    <el-dialog
      v-model="addDialogVisible"
      title="添加地区"
      width="500px"
      :close-on-click-modal="false"
    >
      <el-form
        ref="addFormRef"
        :model="addForm"
        :rules="addRules"
        label-width="100px"
      >
        <el-form-item label="地区名称" prop="name">
          <el-input v-model="addForm.name" placeholder="请输入地区名称" />
        </el-form-item>
        <el-form-item label="地区编码" prop="code">
          <el-input v-model="addForm.code" placeholder="请输入地区编码" />
        </el-form-item>
        <el-form-item label="地区类型" prop="type">
          <el-select v-model="addForm.type" placeholder="请选择地区类型">
            <el-option label="国家" value="country" />
            <el-option label="省份" value="province" />
            <el-option label="城市" value="city" />
            <el-option label="区县" value="area" />
            <el-option label="街道" value="street" />
            <el-option label="社区" value="community" />
          </el-select>
        </el-form-item>
        <el-form-item label="父级地区" prop="parent_id">
          <el-tree-select
            v-model="addForm.parent_id"
            :data="parentOptions"
            :props="{
              value: 'region_id',
              label: 'name',
              children: 'children'
            }"
            :filter-method="filterParentNode"
            :filterable="true"
            :clearable="true"
            placeholder="请选择父级地区（不选择则为顶级地区）"
            :default-expanded-keys="[]"
            :node-key="'region_id'"
            :render-after-expand="false"
            :show-checkbox="false"
            :check-strictly="true"
            :disabled="addLoading"
            style="width: 100%"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="addDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleAddSubmit" :loading="addLoading">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, nextTick } from 'vue'
import { ElTreeV2, ElButton, ElMessageBox, ElMessage, ElDialog, ElDescriptions, ElDescriptionsItem, ElTag, ElForm, ElFormItem, ElInput, ElSelect, ElOption, ElTreeSelect, ElInputNumber, ElRadioGroup, ElRadio } from 'element-plus'
import { Refresh, Plus } from '@element-plus/icons-vue'
import { getTreeApi, getRegionDetailApi, addRegionApi, editRegionApi, deleteRegionApi, refreshCacheApi } from '@/api/modules/region'

const tableData = ref([])
const expandedKeys = ref([])
const clearCacheLoading = ref(false)
const detailDialogVisible = ref(false)
const editDialogVisible = ref(false)
const addDialogVisible = ref(false)
const editLoading = ref(false)
const addLoading = ref(false)
const addButtonLoading = ref(false) // 添加添加按钮loading状态
const editFormRef = ref(null)
const addFormRef = ref(null)
const loading = ref(false)

// 详情数据
const detailData = reactive({
  region_id: '',
  name: '',
  code: '',
  type: '',
  parent_id: '',
  created_at: '',
  updated_at: '',
})

// 编辑表单
const editForm = reactive({
  region_id: '',
  name: '',
  code: '',
  type: '',
  parent_id: null,
})

// 新增表单
const addForm = reactive({
  name: '',
  code: '',
  type: '',
  parent_id: null,
})

// 父级选项数据
const parentOptions = ref([])

// 编辑表单验证规则
const editRules = {
  name: [{ required: true, message: '请输入地区名称', trigger: 'blur' }],
  code: [{ required: true, message: '请输入地区编码', trigger: 'blur' }],
  type: [{ required: true, message: '请选择地区类型', trigger: 'change' }],
}

// 新增表单验证规则
const addRules = {
  name: [{ required: true, message: '请输入地区名称', trigger: 'blur' }],
  code: [{ required: true, message: '请输入地区编码', trigger: 'blur' }],
  type: [{ required: true, message: '请选择地区类型', trigger: 'change' }],
}

// 添加一个变量来存储干净的原始数据
const rawParentOptions = ref([])

const getRegionData = async () => {
  try {
    loading.value = true
    
    // 获取完整层级数据
    const treeResponse = await getTreeApi({ level: 0 })
    if (treeResponse.code === 200) {
      tableData.value = treeResponse.data
    }
    
    // 获取三级父级选项数据，存储为原始数据
    const parentResponse = await getTreeApi({ level: 3 })
    if (parentResponse.code === 200) {
      // 保存干净的原始数据
      rawParentOptions.value = JSON.parse(JSON.stringify(parentResponse.data))
      // 应用过滤
      parentOptions.value = filterParentOptions(rawParentOptions.value)
    }
  } catch (error) {
    console.error('获取地区数据失败:', error)
    ElMessage.error('获取地区数据失败')
  } finally {
    loading.value = false
  }
}

// 添加操作
const handleAdd = async () => {
  try {
    console.log("准备开启loading")
    addButtonLoading.value = true // 开始loading
    
    // 等待UI更新，确保loading状态显示
    await nextTick()
    console.log("已经开启loading")
    
    // 重置表单
    Object.assign(addForm, {
      name: '',
      code: '',
      type: '',
      parent_id: null,
    })
    
    // 确保parentOptions数据已加载
    if (rawParentOptions.value.length === 0) {
      const parentResponse = await getTreeApi({ level: 3 })
      rawParentOptions.value = parentResponse.data // 直接使用，避免深拷贝
    }
    
    // 新增操作不需要过滤数据，直接使用原始数据
    parentOptions.value = rawParentOptions.value
    
    // 让主线程有机会更新UI状态，确保loading显示
    await new Promise(resolve => setTimeout(resolve, 0))
    
    addDialogVisible.value = true
    
    // 等待DOM更新
    await nextTick()
    addFormRef.value?.clearValidate()
    
    // 延迟关闭loading，确保用户能看到loading状态
    setTimeout(() => {
      addButtonLoading.value = false
      console.log("关闭loading")
    }, 500) // 延迟500ms关闭loading，确保用户能看到
  } catch (error) {
    console.error('准备添加地区失败:', error)
    ElMessage.error('准备添加地区失败')
    addButtonLoading.value = false // 确保异常时也关闭loading
  }
}

// 新增提交
const handleAddSubmit = async () => {
  if (!addFormRef.value) return
  
  try {
    await addFormRef.value.validate()
    addLoading.value = true
    
    // 处理parent_id：将null转换回0，符合后端API要求
    const submitData = {
      ...addForm,
      parent_id: addForm.parent_id === null ? 0 : addForm.parent_id
    }
    
    await addRegionApi(submitData)
    ElMessage.success('添加地区成功')
    addDialogVisible.value = false
    getRegionData() // 重新加载数据
  } catch (error) {
    if (error.message) {
      ElMessage.error(error.message)
    } else {
      ElMessage.error('添加地区失败')
    }
  } finally {
    addLoading.value = false
  }
}

// 编辑操作
const handleEdit = async (data) => {
  try {
    const response = await getRegionDetailApi(data.region_id)
    const detailData = response.data
    
    // 处理parent_id：将0转换为null，使el-tree-select能正确显示
    const formData = {
      ...detailData,
      parent_id: detailData.parent_id === 0 ? null : detailData.parent_id
    }
    
    Object.assign(editForm, formData)
    editDialogVisible.value = true
    
    // 等待DOM更新和数据加载完成
    await nextTick()
    
    // 确保parentOptions数据已加载
    if (rawParentOptions.value.length === 0) {
      const parentResponse = await getTreeApi({ level: 3 })
      // 保存干净的原始数据
      rawParentOptions.value = JSON.parse(JSON.stringify(parentResponse.data))
    }
    
    // 每次都从干净的原始数据重新应用过滤
    parentOptions.value = filterParentOptions(rawParentOptions.value)
    
    editFormRef.value?.clearValidate()
  } catch (error) {
    ElMessage.error('获取地区信息失败')
  }
}

const handleNodeClick = (data) => {
  console.log('节点点击:', data)
}

// 将英文type转换为中文标签
const getTypeLabel = (type) => {
  const typeMap = {
    'country': '国家',
    'province': '省份', 
    'city': '城市',
    'area': '区县',
    'street': '街道',
    'community': '社区'
  }
  return typeMap[type] || type
}

// 详情操作
const handleDetail = async (data) => {
  try {
    const response = await getRegionDetailApi(data.region_id)
    Object.assign(detailData, response.data)
    detailDialogVisible.value = true
  } catch (error) {
    ElMessage.error('获取地区详情失败')
  }
}

// 编辑提交
const handleEditSubmit = async () => {
  if (!editFormRef.value) return
  
  try {
    await editFormRef.value.validate()
    editLoading.value = true
    
    // 处理parent_id：将null转换回0，符合后端API要求
    const submitData = {
      ...editForm,
      parent_id: editForm.parent_id === null ? 0 : editForm.parent_id
    }
    
    await editRegionApi(submitData)
    ElMessage.success('编辑地区成功')
    editDialogVisible.value = false
    getRegionData() // 重新加载数据
  } catch (error) {
    if (error.message) {
      ElMessage.error(error.message)
    } else {
      ElMessage.error('编辑地区失败')
    }
  } finally {
    editLoading.value = false
  }
}

// 删除操作
const handleDelete = (data) => {
  ElMessageBox.confirm(
    `确定要删除 ${data.name} 吗？此操作不可恢复。`,
    '删除确认',
    {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    }
  )
    .then(async () => {
      try {
        await deleteRegionApi(data.region_id)
        ElMessage.success(`删除 ${data.name} 成功`)
        getRegionData() // 重新加载数据
      } catch (error) {
        ElMessage.error('删除地区失败')
      }
    })
    .catch(() => {
    })
}

// 清除缓存操作
const handleClearCache = () => {
  ElMessageBox.confirm(
    '确定要清除所有地区缓存吗？此操作可能会影响系统性能。',
    '清除缓存确认',
    {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    }
  )
    .then(async () => {
      try {
        clearCacheLoading.value = true
        await refreshCacheApi()
        ElMessage.success('清除地区缓存成功')
      } catch (error) {
        ElMessage.error('清除缓存失败')
      } finally {
        clearCacheLoading.value = false
      }
    })
    .catch(() => {
      ElMessage.info('已取消清除缓存')
    })
}

// 获取默认展开的父级节点
const getDefaultExpandedKeys = () => {
  if (!editForm.parent_id) return []
  
  // 查找父级节点的所有父节点路径，用于自动展开
  const findParentPath = (data, targetId, path = []) => {
    for (const item of data) {
      // 跳过当前编辑的节点
      if (item.region_id === editForm.region_id) continue
      
      const currentPath = [...path, item.region_id]
      if (item.region_id === targetId) {
        return currentPath
      }
      if (item.children && item.children.length > 0) {
        const found = findParentPath(item.children, targetId, currentPath)
        if (found) return found
      }
    }
    return null
  }
  
  const path = findParentPath(parentOptions.value, editForm.parent_id)
  return path || [] // 返回完整的父节点路径
}

// 过滤父级节点（只禁用自身及其子节点，不过滤）
const filterParentNode = (value, data) => {
  if (!value) return true
  
  // 不再根据disabled属性过滤，允许所有节点显示
  // 只是在filterParentOptions中设置disabled属性来置灰
  
  const nameMatch = data.name.toLowerCase().includes(value.toLowerCase())
  return nameMatch
}

// 获取当前选中的节点值
const getCurrentValue = () => {
  return editForm.parent_id
}

// 获取默认展开和选中的节点
const getTreeSelectProps = () => {
  const expandedKeys = getDefaultExpandedKeys()
  const currentValue = getCurrentValue()
  
  return {
    expandedKeys,
    currentValue
  }
}

// 过滤掉自身及其子节点的选项，并添加disabled属性
const filterParentOptions = (options) => {
  if (!editForm.region_id) return options
  
  // 使用更高效的方式标记节点，避免创建大量新对象
  const markDisabledNodes = (nodes, targetId) => {
    const targetNode = findNodeById(nodes, targetId)
    if (!targetNode) return nodes
    
    // 收集需要禁用的所有节点ID
    const disabledIds = new Set()
    collectNodeIds(targetNode, disabledIds)
    
    // 只遍历一次，设置disabled属性
    return nodes.map(node => {
      if (disabledIds.has(node.region_id)) {
        return { ...node, disabled: true }
      }
      return node
    })
  }
  
  // 查找指定ID的节点
  const findNodeById = (nodes, id) => {
    for (const node of nodes) {
      if (node.region_id === id) {
        return node
      }
      if (node.children) {
        const found = findNodeById(node.children, id)
        if (found) return found
      }
    }
    return null
  }
  
  // 收集节点及其所有子节点的ID
  const collectNodeIds = (node, idSet) => {
    idSet.add(node.region_id)
    if (node.children) {
      node.children.forEach(child => collectNodeIds(child, idSet))
    }
  }
  
  // 使用优化后的函数
  return markDisabledNodes(options, editForm.region_id)
}

getRegionData()
</script>

<style scoped>
.region-manage-container {
  padding: 16px;
}

.toolbar {
  margin-bottom: 16px;
  display: flex;
  justify-content: flex-end;
}

.custom-tree-node {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 32px;
  padding-right: 8px;
}

.node-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.node-name {
  font-size: 14px;
  font-weight: 500;
  color: #333;
  min-width: 80px;
}

.node-type {
  font-size: 12px;
  color: #409EFF;
  background: #ecf5ff;
  padding: 2px 6px;
  border-radius: 3px;
  font-weight: 500;
}

.node-actions {
  display: flex;
  gap: 4px;
  opacity: 0.7;
  transition: opacity 0.2s;
}

.node-actions:hover {
  opacity: 1;
}

/* 优化树节点内容样式 */
:deep(.el-tree-node__content) {
  height: 32px;
  align-items: center;
}
</style>