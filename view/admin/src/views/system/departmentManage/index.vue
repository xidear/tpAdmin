<template>
  <div class="department-manage-container">
         <div class="toolbar">
       <el-button type="primary" :icon="CirclePlus" @click="openDrawer('新增')" v-auth="'create'">
         新增部门
       </el-button>
       <el-button
         type="danger"
         :icon="Delete"
         plain
         :disabled="!selectData.length"
         @click="batchDelete"
         v-auth="'batchDelete'"
       >
         批量删除
       </el-button>
       <el-button type="success" :icon="Download" plain @click="downloadFile" v-auth="'export'">
         导出数据
       </el-button>
       <el-button :icon="Refresh" circle @click="getTableList" />
     </div>

    <!-- 搜索表单 -->
    <div class="search-form">
      <el-form :model="searchParam" :inline="true" ref="searchFormRef">
        <el-form-item label="部门名称" prop="keyword">
          <el-input
            v-model="searchParam.keyword"
            placeholder="请输入部门名称或编码"
            clearable
            @keyup.enter="search"
            style="width: 200px"
          />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-select v-model="searchParam.status" placeholder="请选择状态" clearable style="width: 150px">
            <el-option 
              v-for="item in statusOptions" 
              :key="item.value" 
              :label="item.label" 
              :value="item.value" 
            />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" :icon="Search" @click="search">搜索</el-button>
          <el-button :icon="Delete" @click="resetSearch">重置</el-button>
        </el-form-item>
      </el-form>
    </div>
    <el-table
      ref="tableRef"
      v-loading="loading"
      :data="tableData"
      :tree-props="{ children: 'children', hasChildren: 'hasChildren' }"
      row-key="department_id"
      @selection-change="selectionChange"
      :header-cell-style="{ background: '#f5f7fa', color: '#606266' }"
    >
          <el-table-column type="selection" width="55" align="center" />
          <el-table-column prop="name" label="部门名称" min-width="200" align="left" show-overflow-tooltip />
          <el-table-column prop="code" label="部门编码" min-width="120" align="center" show-overflow-tooltip />
          <el-table-column prop="level" label="层级" width="80" align="center" />
          <el-table-column prop="sort" label="排序" width="80" align="center" />
          <el-table-column prop="status" label="状态" width="150" align="center">
            <template #default="{ row }">
              <el-tag :type="row.status === 1 ? 'success' : 'danger'">
                {{ row.status === 1 ? '启用' : '禁用' }}
              </el-tag>
                             <el-switch
                 :model-value="row.status || 1"
                 :active-value="1"
                 :inactive-value="2"
                 @update:model-value="(val) => { row.status = val; changeStatus(row); }"
                 style="margin-left: 8px"
                 v-auth="'updateStatus'"
               />
            </template>
          </el-table-column>
          <el-table-column prop="leader" label="部门主管" width="120" align="center" show-overflow-tooltip>
            <template #default="{ row }">
              {{ row.leader?.username || '无' }}
            </template>
          </el-table-column>
          <el-table-column prop="description" label="描述" min-width="150" show-overflow-tooltip />
          <el-table-column prop="created_at" label="创建时间" width="180" align="center" />
          <el-table-column label="操作" width="350" align="center" fixed="right">
            <template #default="{ row }">
              <div class="operation-buttons">
                <el-button
                  type="primary"
                  link
                  :icon="Plus"
                  @click="openDrawer('新增', row.department_id)"
                  v-auth="'create'"
                >
                  新增下级
                </el-button>

                <el-button
                  type="primary"
                  link
                  :icon="EditPen"
                  @click="openDrawer('编辑', row.department_id, row)"
                  v-auth="'update'"
                >
                  编辑
                </el-button>
                                 <el-button
                   type="danger"
                   link
                   :icon="Delete"
                   @click="deleteAccount(row)"
                   v-auth="'delete'"
                 >
                   删除
                 </el-button>
                 <el-button
                   type="warning"
                   link
                   :icon="View"
                   @click="openPositionDialog(row)"
                   v-auth="'positions'"
                 >
                   职位管理
                 </el-button>
              </div>
            </template>
                     </el-table-column>
         </el-table>

         <!-- 部门新增/编辑抽屉 -->
     <el-drawer 
       v-model="drawerVisible" 
       :destroy-on-close="false" 
       size="450px" 
       :title="`${drawerProps.title}部门`"
       :with-header="true"
       :modal="true"
       :append-to-body="true"
       :lock-scroll="true"
       :close-on-click-modal="true"
       :close-on-press-escape="true"
     >
      <el-form
        ref="ruleFormRef"
        label-width="100px"
        label-suffix=" :"
        :rules="rules"
        :model="drawerProps.row"
        @submit.prevent
      >
                 <el-form-item label="上级部门" prop="parent_id">
           <el-tree-select
             v-model="drawerProps.row!.parent_id"
             :data="parentDeptOptions"
             :props="{ value: 'department_id', label: 'name', children: 'children' }"
             placeholder="请选择上级部门（不选择则为顶级部门）"
             check-strictly
             :render-after-expand="false"
             :lazy="false"
             :default-expand-all="false"
             :expand-on-click-node="false"
             :highlight-current="true"
             clearable
             style="width: 100%"
           />
         </el-form-item>
        <el-form-item label="部门名称" prop="name">
          <el-input v-model="drawerProps.row!.name" placeholder="请输入部门名称" clearable />
        </el-form-item>
                 <el-form-item label="部门编码" prop="code">
           <el-input v-model="drawerProps.row!.code" placeholder="请输入部门编码" clearable />
         </el-form-item>
                   <el-form-item label="部门主管" prop="leader_id">
            <el-select 
              v-model="drawerProps.row!.leader_id" 
              placeholder="请选择部门主管" 
              clearable 
              filterable
              remote
              :remote-method="searchAdmins"
              :loading="adminSearchLoading"
              style="width: 100%"
            >
                             <el-option
                 v-for="admin in adminOptions"
                 :key="admin.admin_id"
                 :label="admin.username"
                 :value="admin.admin_id"
               />
            </el-select>
          </el-form-item>
         <el-form-item label="排序" prop="sort">
           <el-input-number v-model="drawerProps.row!.sort" :min="0" :max="999" style="width: 100%" />
         </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="drawerProps.row!.status">
            <el-radio 
              v-for="item in statusOptions" 
              :key="item.value" 
              :label="item.value"
            >
              {{ item.label }}
            </el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="描述" prop="description">
          <el-input
            v-model="drawerProps.row!.description"
            type="textarea"
            :rows="3"
            placeholder="请输入部门描述"
            maxlength="500"
            show-word-limit
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="drawerVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit">确定</el-button>
      </template>
    </el-drawer>

    <!-- 职位管理对话框 -->
    <el-dialog v-model="positionDialogVisible" title="职位管理" width="800px" :destroy-on-close="true">
             <div class="position-header">
         <el-button type="primary" :icon="Plus" @click="openPositionDrawer('新增')" v-auth="'createPosition'">
           新增职位
         </el-button>
         <span class="department-info">{{ currentDepartment?.name }} 的职位列表</span>
       </div>
      <el-table
        :data="positionList"
        v-loading="positionLoading"
        :header-cell-style="{ background: '#f5f7fa', color: '#606266' }"
      >
        <el-table-column prop="name" label="职位名称" min-width="120" />
        <el-table-column prop="code" label="职位编码" min-width="120" />
        <el-table-column prop="sort" label="排序" width="80" align="center" />
        <el-table-column prop="status" label="状态" width="80" align="center">
          <template #default="{ row }">
            <el-tag :type="row.status ? 'success' : 'danger'">{{ row.status ? '启用' : '禁用' }}</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="description" label="描述" min-width="150" show-overflow-tooltip />
                 <el-table-column label="操作" width="150" align="center">
           <template #default="{ row }">
             <el-button
               type="primary"
               link
               :icon="EditPen"
               @click="openPositionDrawer('编辑', row)"
               v-auth="'updatePosition'"
             >
               编辑
             </el-button>
             <el-button
               type="danger"
               link
               :icon="Delete"
               @click="deletePosition(row)"
               v-auth="'deletePosition'"
             >
               删除
             </el-button>
           </template>
         </el-table-column>
      </el-table>
    </el-dialog>

    <!-- 职位新增/编辑抽屉 -->
    <el-drawer v-model="positionDrawerVisible" :destroy-on-close="true" size="400px" :title="`${positionDrawerProps.title}职位`">
      <el-form
        ref="positionFormRef"
        label-width="80px"
        label-suffix=" :"
        :rules="positionRules"
        :model="positionDrawerProps.row"
        @submit.prevent
      >
        <el-form-item label="职位名称" prop="name">
          <el-input v-model="positionDrawerProps.row!.name" placeholder="请输入职位名称" clearable />
        </el-form-item>
        <el-form-item label="职位编码" prop="code">
          <el-input v-model="positionDrawerProps.row!.code" placeholder="请输入职位编码" clearable />
        </el-form-item>
        <el-form-item label="排序" prop="sort">
          <el-input-number v-model="positionDrawerProps.row!.sort" :min="0" :max="999" style="width: 100%" />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="positionDrawerProps.row!.status">
            <el-radio 
              v-for="item in statusOptions" 
              :key="item.value" 
              :label="item.value"
            >
              {{ item.label }}
            </el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="描述" prop="description">
          <el-input
            v-model="positionDrawerProps.row!.description"
            type="textarea"
            :rows="3"
            placeholder="请输入职位描述"
            maxlength="500"
            show-word-limit
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="positionDrawerVisible = false">取消</el-button>
        <el-button type="primary" @click="handlePositionSubmit">确定</el-button>
      </template>
    </el-drawer>
  </div>
</template>

<script setup lang="ts" name="departmentManage">
import { ref, reactive, onMounted } from "vue"
import { ElMessage, ElMessageBox } from "element-plus"
import {
  CirclePlus,
  Delete,
  EditPen,
  Download,
  Search,
  Refresh,
  Plus,
  View
} from "@element-plus/icons-vue"
import { useHandleData } from "@/hooks/useHandleData"
import { useDownload } from "@/hooks/useDownload"
import { 
  getDepartmentTreeApi, 
  getDepartmentListApi, 
  postDepartmentCreateApi, 
  putDepartmentUpdateApi, 
  deleteDepartmentApi, 
  postDepartmentBatchDeleteApi, 
  putDepartmentUpdateStatusApi, 
  getDepartmentExportApi, 
  getDepartmentPositionsApi, 
  postPositionCreateApi, 
  putPositionUpdateApi, 
  deletePositionApi,
  type Department, 
  type DepartmentPosition 
} from "@/api/modules/department"
import { getListApi } from "@/api/modules/account"
import { getEnumDataApi } from "@/api/modules/enum"

// 表格相关
const tableRef = ref()
const loading = ref(false)
const tableData = ref<Department[]>([])
const selectData = ref<Department[]>([])

// 搜索相关
const searchFormRef = ref()
const searchParam = reactive({
  keyword: "",
  status: null
})

// 抽屉相关
const drawerVisible = ref(false)
const ruleFormRef = ref()
const drawerProps = ref<{
  title: string
  row: Partial<Department>
}>({
  title: "",
  row: {}
})

// 职位管理相关
const positionDialogVisible = ref(false)
const positionDrawerVisible = ref(false)
const positionFormRef = ref()
const positionLoading = ref(false)
const positionList = ref<DepartmentPosition[]>([])
const currentDepartment = ref<Department>()

const positionDrawerProps = ref<{
  title: string
  row: Partial<DepartmentPosition>
}>({
  title: "",
  row: {}
})

// 父部门选项
const parentDeptOptions = ref<Department[]>([])

// 管理员选项
const adminOptions = ref<{ admin_id: number; username: string }[]>([])
const adminSearchLoading = ref(false)

// 状态枚举选项
const statusOptions = ref<{ label: string; value: number | string }[]>([])

// 表单验证规则
const rules = reactive({
  name: [{ required: true, message: "请输入部门名称", trigger: "blur" }],
  parent_id: [{ required: false, message: "请选择上级部门", trigger: "change" }],
  sort: [{ required: true, message: "请输入排序", trigger: "blur" }],
  status: [{ required: true, message: "请选择状态", trigger: "change" }]
})

const positionRules = reactive({
  name: [{ required: true, message: "请输入职位名称", trigger: "blur" }],
  sort: [{ required: true, message: "请输入排序", trigger: "blur" }],
  status: [{ required: true, message: "请选择状态", trigger: "change" }]
})

// 获取部门列表
const getTableList = async () => {
  loading.value = true
  try {
    // 构建查询参数，过滤掉空值
    const params: Record<string, any> = {}
    if (searchParam.keyword) {
      params.keyword = searchParam.keyword
    }
    if (searchParam.status !== null && searchParam.status !== '') {
      params.status = searchParam.status
    }
    
    console.log('查询参数:', params)
    const { data } = await getDepartmentTreeApi(params)
    tableData.value = data as Department[]
  } catch (error) {
    console.error("获取部门列表失败:", error)
  } finally {
    loading.value = false
  }
}

// 获取父部门选项
const getParentDeptOptions = async () => {
  try {
    const { data } = await getDepartmentListApi()
    // 不添加顶级部门选项，留空表示顶级部门
    parentDeptOptions.value = data as Department[]
  } catch (error) {
    console.error("获取父部门选项失败:", error)
  }
}

// 获取管理员选项（远程搜索）
const searchAdmins = async (query: string) => {
  // 清空查询时不显示任何结果
  if (query === '') {
    adminOptions.value = []
    return
  }
  
  // 搜索关键词长度限制，避免无效搜索
  if (query.length < 2) {
    adminOptions.value = []
    return
  }
  
  adminSearchLoading.value = true
  try {
    // 调用搜索管理员的API，传递搜索关键词
    const { data } = await getListApi({ keyword: query })
    // 确保返回的是数组格式
    if (data && data.list) {
      adminOptions.value = data.list
    } else if (Array.isArray(data)) {
      adminOptions.value = data
    } else {
      adminOptions.value = []
    }
  } catch (error) {
    console.error("搜索管理员失败:", error)
    adminOptions.value = []
  } finally {
    adminSearchLoading.value = false
  }
}

// 获取状态枚举选项
const getStatusOptions = async () => {
  try {
    const statusData = await getEnumDataApi('Status')
    statusOptions.value = statusData
  } catch (error) {
    console.error("获取状态枚举失败:", error)
    // 如果获取失败，使用默认值
    statusOptions.value = [
      { label: '启用', value: 1 },
      { label: '禁用', value: 2 }
    ]
  }
}

// 搜索
const search = () => {
  getTableList()
}

// 重置搜索
const resetSearch = () => {
  searchFormRef.value?.resetFields()
  // 手动重置状态值
  searchParam.keyword = ""
  searchParam.status = null
  getTableList()
}

// 表格选择
const selectionChange = (selection: Department[]) => {
  selectData.value = selection
}

// 打开抽屉
const openDrawer = async (title: string, parentId?: number, row?: Department) => {
  // 只在第一次打开时获取父部门选项，避免重复请求
  if (parentDeptOptions.value.length === 0) {
    await getParentDeptOptions()
  }
  
  const defaultRow: Partial<Department> = {
    name: "",
    code: "",
    parent_id: parentId || null,
    leader_id: undefined,
    sort: 0,
    status: 1, // 使用枚举值：1=启用
    description: ""
  }

  // 处理编辑时的数据，确保顶级部门的 parent_id 为 null
  let editRow = row
  if (row && (row.parent_id === 0 || row.parent_id === null)) {
    editRow = { ...row, parent_id: null }
  }

  drawerProps.value = {
    title,
    row: editRow ? { ...editRow } : defaultRow
  }
  drawerVisible.value = true
}

// 提交表单
const handleSubmit = () => {
  ruleFormRef.value!.validate(async (valid: boolean) => {
    if (!valid) return
    try {
      const params = { ...drawerProps.value.row }
      if (drawerProps.value.title === "新增") {
        await postDepartmentCreateApi(params)
        ElMessage.success("新增成功！")
      } else {
        await putDepartmentUpdateApi(params.department_id!, params)
        ElMessage.success("编辑成功！")
      }
      drawerVisible.value = false
      getTableList()
    } catch (error) {
      console.error("提交失败:", error)
    }
  })
}

// 删除部门
const deleteAccount = async (row: Department) => {
  await useHandleData(
    () => deleteDepartmentApi(row.department_id),
    {},
    `删除【${row.name}】部门`
  )
  getTableList()
}

// 批量删除
const batchDelete = async () => {
  const ids = selectData.value.map(item => item.department_id)
  await useHandleData(
    () => postDepartmentBatchDeleteApi(ids),
    {},
    "批量删除部门"
  )
  getTableList()
}

// 切换状态
const changeStatus = async (row: Department) => {
  try {
    await putDepartmentUpdateStatusApi(row.department_id, row.status)
    const statusText = row.status === 1 ? '启用' : '禁用'
    ElMessage.success(`${statusText}成功！`)
  } catch (error) {
    // 恢复原状态：1=启用，2=禁用
    row.status = row.status === 1 ? 2 : 1
    console.error("状态切换失败:", error)
  }
}

// 导出数据
const downloadFile = async () => {
  ElMessageBox.confirm("确认导出部门数据?", "温馨提示", { type: "warning" })
    .then(() => useDownload(() => getDepartmentExportApi(searchParam), "部门数据", searchParam))
    .catch(() => {})
}

// 职位管理相关方法
const openPositionDialog = async (department: Department) => {
  currentDepartment.value = department
  positionDialogVisible.value = true
  await getPositionList()
}

const getPositionList = async () => {
  if (!currentDepartment.value) return
  positionLoading.value = true
  try {
    const { data } = await getDepartmentPositionsApi(currentDepartment.value.department_id)
    positionList.value = data as DepartmentPosition[]
  } catch (error) {
    console.error("获取职位列表失败:", error)
  } finally {
    positionLoading.value = false
  }
}

const openPositionDrawer = (title: string, row?: DepartmentPosition) => {
  const defaultRow: Partial<DepartmentPosition> = {
    department_id: currentDepartment.value?.department_id || 0,
    name: "",
    code: "",
    sort: 0,
    status: 1, // 使用枚举值：1=启用
    description: ""
  }

  positionDrawerProps.value = {
    title,
    row: row ? { ...row } : defaultRow
  }
  positionDrawerVisible.value = true
}

const handlePositionSubmit = () => {
  positionFormRef.value!.validate(async (valid: boolean) => {
    if (!valid) return
    try {
      const params = { ...positionDrawerProps.value.row }
      if (positionDrawerProps.value.title === "新增") {
        await postPositionCreateApi(params)
        ElMessage.success("新增成功！")
      } else {
        await putPositionUpdateApi(params.position_id!, params)
        ElMessage.success("编辑成功！")
      }
      positionDrawerVisible.value = false
      getPositionList()
    } catch (error) {
      console.error("提交失败:", error)
    }
  })
}

const deletePosition = async (row: DepartmentPosition) => {
  await useHandleData(
    () => deletePositionApi(row.position_id),
    {},
    `删除【${row.name}】职位`
  )
  getPositionList()
}

// 页面挂载
onMounted(async () => {
  await getStatusOptions()
  getTableList()
})
</script>

<style scoped>
.department-manage-container {
  padding: 16px;
}

.toolbar {
  margin-bottom: 16px;
  display: flex;
  gap: 10px;
}

.search-form {
  margin-bottom: 16px;
  padding: 16px;
  background: #f5f7fa;
  border-radius: 4px;
}

.position-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.department-info {
  font-size: 14px;
  color: #606266;
  font-weight: 500;
}

.operation-buttons {
  display: flex;
  gap: 8px;
}

/* 优化抽屉动画性能 */
:deep(.el-drawer) {
  transition: transform 0.3s cubic-bezier(0.23, 1, 0.32, 1);
  will-change: transform;
}

:deep(.el-drawer__wrapper) {
  transition: opacity 0.3s cubic-bezier(0.23, 1, 0.32, 1);
  will-change: opacity;
}

/* 优化树形选择器性能 */
:deep(.el-tree-select) {
  .el-tree {
    max-height: 300px;
    overflow-y: auto;
  }
  
  .el-tree-node__content {
    transition: background-color 0.2s ease;
  }
}
</style>
