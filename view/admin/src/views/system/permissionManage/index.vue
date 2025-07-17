<template>
  <div class="table-box">
    <ProTable
      ref="proTable"
      title="权限管理"
      row-key="permission_id"
      :indent="20"
      :columns="columns"
      :request-api="getPermissionList"
      :init-param="initParam"
      :data-callback="dataCallback"
      :pagination="true"
    >
      <!-- 表格 header 按钮 -->
      <template #tableHeader="scope">
        <el-button type="primary" :icon="CirclePlus" @click="addPermission">新增权限</el-button>
        <el-button
          type="danger"
          :icon="Delete"
          plain
          :disabled="!scope.isSelected"
          @click="batchDelete(scope.selectedListIds)"
        >
          批量删除
        </el-button>
      </template>

      <!-- 操作列 -->
      <template #operation="scope">
        <el-button type="primary" link :icon="EditPen" @click="editPermission(scope.row)">编辑</el-button>
        <el-button type="primary" link :icon="Delete" @click="deletePermission(scope.row)">删除</el-button>
      </template>

      <!-- 是否公开列 -->
      <template #is_public="scope">
        <el-tag v-if="scope.row.is_public === 1" type="success">公开</el-tag>
        <el-tag v-else type="info">私有</el-tag>
      </template>

      <!-- 请求方法列 - 已完全修复undefined错误 -->
      <template #method="scope">
        <el-tag
          v-if="scope.row.method"
          :type="{
            'get': 'primary',
            'post': 'success',
            'put': 'warning',
            'delete': 'danger',
            'patch': 'info'
          }[scope.row.method.toLowerCase()]"
          effect="light"
          size="small"
        >
          {{ scope.row.method.toUpperCase() }}
        </el-tag>
        <el-tag v-else type="info">无方法</el-tag>
      </template>
    </ProTable>

    <!-- 权限编辑弹窗 -->
    <el-dialog
      v-model="dialogVisible"
      :title="isEdit ? '编辑权限' : '新增权限'"
      width="500px"
      :close-on-click-modal="false"
    >
      <el-form
        :model="permissionForm"
        :rules="formRules"
        ref="formRef"
        label-width="120px"
      >
        <el-form-item label="权限节点" prop="node">
          <el-input
            v-model="permissionForm.node"
            placeholder="例如: permission/create"
            maxlength="50"
          />
          <div class="form-tip">格式: 模块/操作 (如: user/list)</div>
        </el-form-item>

        <el-form-item label="权限名称" prop="name">
          <el-input
            v-model="permissionForm.name"
            placeholder="例如: 创建权限"
            maxlength="20"
          />
        </el-form-item>

        <el-form-item label="权限描述" prop="description">
          <el-input
            v-model="permissionForm.description"
            type="textarea"
            :rows="3"
            maxlength="100"
          />
        </el-form-item>

        <el-form-item label="请求方法" prop="method">
          <el-select v-model="permissionForm.method" placeholder="请选择">
            <el-option label="GET" value="get" />
            <el-option label="POST" value="post" />
            <el-option label="PUT" value="put" />
            <el-option label="DELETE" value="delete" />
            <el-option label="PATCH" value="patch" />
          </el-select>
        </el-form-item>

        <el-form-item label="是否公开" prop="is_public">
          <el-switch
            v-model="permissionForm.is_public"
            :active-value="1"
            :inactive-value="2"
            active-text="公开"
            inactive-text="私有"
          />
          <div class="form-tip">公开权限无需登录即可访问</div>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm">确认</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from "vue";
import { ElMessageBox, ElMessage } from "element-plus";
import type { FormInstance, FormRules } from "element-plus";
import ProTable from "@/components/ProTable/index.vue";
import { ColumnProps } from "@/components/ProTable/interface";
import { CirclePlus, Delete, EditPen } from "@element-plus/icons-vue";
import {
  getListApi as getPermissionListApi,
  postCreateApi,
  getReadApi,
  putUpdateApi,
  deleteDeleteApi
} from "@/api/modules/permission";

const proTable = ref<InstanceType<typeof ProTable>>();
const formRef = ref<FormInstance>();

const dialogVisible = ref(false);
const isEdit = ref(false);

const initParam = reactive({});

// 安全数据处理函数
const dataCallback = (res: any) => {
  const safeData = res || {};
  console.log(res)
  return {
    list: safeData.list || [],
    total: safeData.total || 0
  };
};

const getPermissionList = (params: any) => {
  return getPermissionListApi(params);
};

const permissionForm = ref({
  permission_id: 0,
  node: "",
  name: "",
  description: "",
  method: "get",
  is_public: 2
});

// 表单验证规则
const formRules = reactive<FormRules>({
  node: [
    { required: true, message: "权限节点不能为空", trigger: "blur" },
    {
      pattern: /^[a-z0-9_-]+\/[a-z0-9_-]+$/i,
      message: "格式应为: 控制器/方法 (允许字母、数字、下划线和连字符)",
      trigger: "blur"
    },
    { min: 3, max: 50, message: "长度需在3-50个字符", trigger: "blur" }
  ],
  name: [
    { required: true, message: "权限名称不能为空", trigger: "blur" },
    { min: 2, max: 20, message: "长度需在2-20个字符", trigger: "blur" }
  ],
  description: [
    { max: 100, message: "描述不超过100字符", trigger: "blur" }
  ],
  method: [
    { required: true, message: "请选择请求方法", trigger: "change" }
  ]
});

// 初始化表单
const initForm = () => {
  permissionForm.value = {
    permission_id: 0,
    node: "",
    name: "",
    description: "",
    method: "get",
    is_public: 2
  };
};

const addPermission = () => {
  isEdit.value = false;
  initForm();
  dialogVisible.value = true;
};

const editPermission = async (row: any) => {
  isEdit.value = true;
  try {
    const res = await getReadApi(row.permission_id);

    // 安全赋值，确保所有字段存在
    const formData = res.data || {};
    permissionForm.value = {
      permission_id: formData.permission_id || 0,
      node: formData.node || "",
      name: formData.name || "",
      description: formData.description || "",
      method: formData.method ? formData.method.toLowerCase() : "get",
      is_public: formData.is_public || 2
    };

    dialogVisible.value = true;
  } catch (error) {
  }
};

const deletePermission = async (row: any) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除权限 "${row.name}" (${row.node}) 吗?`,
      "提示",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }
    );

    await deleteDeleteApi({ ids: [row.permission_id] });
    ElMessage.success("删除成功");
    proTable.value?.getTableList();
  } catch (error) {
    // 用户取消删除，无需处理
  }
};

const batchDelete = async (ids: number[]) => {
  if (ids.length === 0) {
    ElMessage.warning("请选择需要删除的权限");
    return;
  }

  try {
    await ElMessageBox.confirm(
      `确定要删除选中的 ${ids.length} 个权限吗?`,
      "提示",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }
    );

    await deleteDeleteApi({ ids });
    ElMessage.success(`成功删除 ${ids.length} 个权限`);

    proTable.value?.clearSelection();
    proTable.value?.getTableList();
  } catch (error) {
    // 用户取消删除，无需处理
  }
};

const submitForm = async () => {
  if (!formRef.value) return;

  try {
    // 验证表单
    const valid = await formRef.value.validate();
    if (!valid) return;

    // 确保方法值为小写
    const payload = {
      ...permissionForm.value,
      method: permissionForm.value.method.toLowerCase()
    };

    if (isEdit.value) {
      await putUpdateApi(payload.permission_id, payload);
      ElMessage.success("更新成功");
    } else {
      await postCreateApi(payload);
      ElMessage.success("创建成功");
    }

    // 关闭弹窗并刷新表格
    dialogVisible.value = false;
    proTable.value?.getTableList();
  } catch (error: any) {
  }
};

// 安全日期格式化
const formatDate = (dateString: string | undefined) => {
  if (!dateString) return "-";
  try {
    return new Date(dateString).toLocaleDateString();
  } catch (e) {
    return dateString.split(" ")[0] || dateString;
  }
};

const columns = reactive<ColumnProps[]>([
  { type: "selection", fixed: "left", width: 70 },
  { type: "sort", label: "排序", width: 80 },
  { prop: "node", label: "权限节点", search: { el: "input" }, width: 200 },
  { prop: "name", label: "权限名称", search: { el: "input" }, width: 150 },
  { prop: "description", label: "权限描述", width: 250 },
  {
    prop: "method",
    label: "请求方法",
    width: 100,
    custom: true,
    formatter: (row) => row.method?.toUpperCase() || "N/A"  // 安全格式化
  },
  { prop: "is_public", label: "是否公开", width: 100, custom: true },
  {
    prop: "created_at",
    label: "创建时间",
    width: 130,
    formatter: (row) => formatDate(row.created_at)
  },
  {
    prop: "updated_at",
    label: "更新时间",
    width: 130,
    formatter: (row) => formatDate(row.updated_at)
  },
  { prop: "operation", label: "操作", fixed: "right", width: 180 }
]);
</script>

<style scoped>
.form-tip {
  font-size: 12px;
  color: #999;
  margin-top: 4px;
  line-height: 1.4;
}
</style>
