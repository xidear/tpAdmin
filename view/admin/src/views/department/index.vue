<template>
  <div class="department-container">
    <div class="department-header">
      <div class="header-left">
        <h2>部门管理</h2>
        <p class="description">管理系统组织架构，支持树状结构展示</p>
      </div>
      <div class="header-right">
        <el-button type="primary" @click="handleCreate">
          <el-icon><Plus /></el-icon>
          新增部门
        </el-button>
        <el-button @click="handleExport">
          <el-icon><Download /></el-icon>
          导出数据
        </el-button>
      </div>
    </div>

    <div class="department-content">
      <div class="content-left">
        <div class="tree-container">
          <div class="tree-header">
            <h3>部门结构</h3>
            <el-input
              v-model="searchKeyword"
              placeholder="搜索部门"
              clearable
              @input="handleSearch"
            >
              <template #prefix>
                <el-icon><Search /></el-icon>
              </template>
            </el-input>
          </div>
          <el-tree
            ref="treeRef"
            :data="departmentTree"
            :props="treeProps"
            :expand-on-click-node="false"
            :highlight-current="true"
            @node-click="handleNodeClick"
          >
            <template #default="{ node, data }">
              <div class="tree-node">
                <span class="node-label">{{ node.label }}</span>
                <div class="node-actions">
                  <el-button
                    type="text"
                    size="small"
                    @click.stop="handleEdit(data)"
                  >
                    编辑
                  </el-button>
                  <el-button
                    type="text"
                    size="small"
                    @click.stop="handleAddChild(data)"
                  >
                    添加子部门
                  </el-button>
                  <el-button
                    type="text"
                    size="small"
                    @click.stop="handleDelete(data)"
                  >
                    删除
                  </el-button>
                </div>
              </div>
            </template>
          </el-tree>
        </div>
      </div>

      <div class="content-right">
        <div class="detail-container" v-if="selectedDepartment">
          <div class="detail-header">
            <h3>部门详情</h3>
            <el-button type="text" @click="handleEdit(selectedDepartment)">
              编辑
            </el-button>
          </div>
          <div class="detail-content">
            <el-descriptions :column="2" border>
              <el-descriptions-item label="部门名称">
                {{ selectedDepartment.name }}
              </el-descriptions-item>
              <el-descriptions-item label="部门编码">
                {{ selectedDepartment.code || '-' }}
              </el-descriptions-item>
              <el-descriptions-item label="部门层级">
                {{ selectedDepartment.level }}
              </el-descriptions-item>
              <el-descriptions-item label="排序">
                {{ selectedDepartment.sort }}
              </el-descriptions-item>
              <el-descriptions-item label="状态">
                <el-tag :type="selectedDepartment.status ? 'success' : 'danger'">
                  {{ selectedDepartment.status ? '启用' : '禁用' }}
                </el-tag>
              </el-descriptions-item>
              <el-descriptions-item label="创建时间">
                {{ formatDateTime(selectedDepartment.created_at) }}
              </el-descriptions-item>
              <el-descriptions-item label="描述" :span="2">
                {{ selectedDepartment.description || '-' }}
              </el-descriptions-item>
            </el-descriptions>
          </div>

          <div class="positions-section">
            <div class="section-header">
              <h4>部门职位</h4>
              <el-button type="primary" size="small" @click="handleCreatePosition">
                新增职位
              </el-button>
            </div>
            <el-table :data="positions" border>
              <el-table-column prop="name" label="职位名称" />
              <el-table-column prop="code" label="职位编码" />
              <el-table-column prop="sort" label="排序" width="80" />
              <el-table-column prop="status" label="状态" width="80">
                <template #default="{ row }">
                  <el-tag :type="row.status ? 'success' : 'danger'">
                    {{ row.status ? '启用' : '禁用' }}
                  </el-tag>
                </template>
              </el-table-column>
              <el-table-column label="操作" width="150">
                <template #default="{ row }">
                  <el-button type="text" size="small" @click="handleEditPosition(row)">
                    编辑
                  </el-button>
                  <el-button type="text" size="small" @click="handleDeletePosition(row)">
                    删除
                  </el-button>
                </template>
              </el-table-column>
            </el-table>
          </div>
        </div>
        <div class="empty-state" v-else>
          <el-empty description="请选择左侧部门查看详情" />
        </div>
      </div>
    </div>

    <!-- 部门表单对话框 -->
    <DepartmentForm
      v-model:visible="formVisible"
      :department="currentDepartment"
      :parent-department="parentDepartment"
      @success="handleFormSuccess"
    />

    <!-- 职位表单对话框 -->
    <PositionForm
      v-model:visible="positionFormVisible"
      :position="currentPosition"
      :department-id="selectedDepartment?.department_id"
      @success="handlePositionFormSuccess"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { Plus, Download, Search } from '@element-plus/icons-vue'
import DepartmentForm from './components/DepartmentForm.vue'
import PositionForm from './components/PositionForm.vue'
import { getDepartmentTree, getDepartmentPositions, deleteDepartment, deletePosition } from '@/api/department'
import { formatDateTime } from '@/utils/format'

// 响应式数据
const treeRef = ref()
const searchKeyword = ref('')
const departmentTree = ref([])
const selectedDepartment = ref(null)
const positions = ref([])
const formVisible = ref(false)
const positionFormVisible = ref(false)
const currentDepartment = ref(null)
const currentPosition = ref(null)
const parentDepartment = ref(null)

// 树形配置
const treeProps = {
  children: 'children',
  label: 'name'
}

// 计算属性
const filteredTree = computed(() => {
  if (!searchKeyword.value) return departmentTree.value
  // 这里可以实现搜索过滤逻辑
  return departmentTree.value
})

// 方法
const loadDepartmentTree = async () => {
  try {
    const response = await getDepartmentTree()
    departmentTree.value = response.data
  } catch (error) {
    ElMessage.error('加载部门数据失败')
  }
}

const loadPositions = async (departmentId: number) => {
  try {
    const response = await getDepartmentPositions(departmentId)
    positions.value = response.data
  } catch (error) {
    ElMessage.error('加载职位数据失败')
  }
}

const handleNodeClick = (data: any) => {
  selectedDepartment.value = data
  if (data.department_id) {
    loadPositions(data.department_id)
  }
}

const handleSearch = () => {
  // 实现搜索逻辑
}

const handleCreate = () => {
  currentDepartment.value = null
  parentDepartment.value = null
  formVisible.value = true
}

const handleAddChild = (data: any) => {
  currentDepartment.value = null
  parentDepartment.value = data
  formVisible.value = true
}

const handleEdit = (data: any) => {
  currentDepartment.value = { ...data }
  parentDepartment.value = null
  formVisible.value = true
}

const handleDelete = async (data: any) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除部门"${data.name}"吗？删除后不可恢复。`,
      '确认删除',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }
    )
    
    await deleteDepartment(data.department_id)
    ElMessage.success('删除成功')
    loadDepartmentTree()
    
    if (selectedDepartment.value?.department_id === data.department_id) {
      selectedDepartment.value = null
      positions.value = []
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

const handleExport = () => {
  // 实现导出逻辑
  ElMessage.info('导出功能开发中')
}

const handleFormSuccess = () => {
  formVisible.value = false
  loadDepartmentTree()
  ElMessage.success('操作成功')
}

const handleCreatePosition = () => {
  currentPosition.value = null
  positionFormVisible.value = true
}

const handleEditPosition = (data: any) => {
  currentPosition.value = { ...data }
  positionFormVisible.value = true
}

const handleDeletePosition = async (data: any) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除职位"${data.name}"吗？`,
      '确认删除',
      {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      }
    )
    
    await deletePosition(data.position_id)
    ElMessage.success('删除成功')
    if (selectedDepartment.value) {
      loadPositions(selectedDepartment.value.department_id)
    }
  } catch (error) {
    if (error !== 'cancel') {
      ElMessage.error('删除失败')
    }
  }
}

const handlePositionFormSuccess = () => {
  positionFormVisible.value = false
  if (selectedDepartment.value) {
    loadPositions(selectedDepartment.value.department_id)
  }
  ElMessage.success('操作成功')
}

// 生命周期
onMounted(() => {
  loadDepartmentTree()
})
</script>

<style lang="scss" scoped>
.department-container {
  padding: 20px;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.department-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  
  .header-left {
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
  
  .header-right {
    display: flex;
    gap: 12px;
  }
}

.department-content {
  flex: 1;
  display: flex;
  gap: 20px;
  min-height: 0;
}

.content-left {
  width: 400px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  
  .tree-container {
    height: 100%;
    display: flex;
    flex-direction: column;
    
    .tree-header {
      padding: 16px;
      border-bottom: 1px solid #f0f0f0;
      
      h3 {
        margin: 0 0 12px 0;
        font-size: 16px;
        font-weight: 600;
      }
    }
    
    .el-tree {
      flex: 1;
      padding: 16px;
      overflow-y: auto;
    }
  }
}

.content-right {
  flex: 1;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  overflow-y: auto;
}

.tree-node {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
  
  .node-label {
    flex: 1;
  }
  
  .node-actions {
    display: none;
    gap: 4px;
  }
}

.el-tree-node:hover .node-actions {
  display: flex;
}

.detail-container {
  padding: 20px;
  
  .detail-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    
    h3 {
      margin: 0;
      font-size: 18px;
      font-weight: 600;
    }
  }
  
  .detail-content {
    margin-bottom: 30px;
  }
  
  .positions-section {
    .section-header {
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

.empty-state {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  min-height: 400px;
}
</style>
