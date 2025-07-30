<template>
  <div class="table-box">
    <ProTable
      ref="proTable"
      title="配置分组管理"
      row-key="system_config_group_id"
      :columns="columns"
      :request-api="getConfigGroupList"
      :init-param="initParam"
      :data-callback="dataCallback"
      :pagination="true"
    >
      <!-- 表格 header 按钮 -->
      <template #tableHeader="scope">
        <el-button type="primary" v-auth="'create'" :icon="CirclePlus" @click="addConfigGroup">新增配置分组</el-button>
      </template>

      <!-- 操作列 -->
      <template #operation="scope">
        <el-button type="primary" v-auth="'update'" link :icon="EditPen" @click="editConfigGroup(scope.row)">编辑</el-button>
        <el-button type="primary" v-auth="'delete'" link :icon="Delete" @click="deleteConfigGroup(scope.row)">删除</el-button>
        <el-button type="primary" v-auth="'read'" link :icon="View" @click="openDetailDialog(scope.row.system_config_group_id)">查看详情</el-button>
      </template>
    </ProTable>

    <!-- 配置分组编辑弹窗 -->
    <el-dialog
      v-model="dialogVisible"
      :title="isEdit ? '编辑配置分组' : '新增配置分组'"
      width="500px"
      :close-on-click-modal="false"
    >
      <el-form
        :model="configGroupForm"
        :rules="formRules"
        ref="formRef"
        label-width="120px"
      >
        <el-form-item label="分组名称" prop="group_name">
          <el-input
            v-model="configGroupForm.group_name"
            placeholder="请输入分组名称"
            maxlength="255"
          />
        </el-form-item>

        <el-form-item label="排序" prop="sort">
          <el-input-number
            v-model.number="configGroupForm.sort"
            :min="0"
            placeholder="请输入排序值"
            controls-position="right"
          />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm">确认</el-button>
      </template>
    </el-dialog>

    <!-- 配置分组详情弹窗 -->
    <el-dialog
      v-model="detailDialogVisible"
      title="配置分组详情"
      width="600px"
      :close-on-click-modal="false"
    >
      <el-form
        :model="configGroupDetail"
        label-width="120px"
      >
        <el-form-item label="分组ID">
          <span>{{ configGroupDetail.system_config_group_id }}</span>
        </el-form-item>
        <el-form-item label="分组名称">
          <span>{{ configGroupDetail.group_name || '无' }}</span>
        </el-form-item>
        <el-form-item label="排序值">
          <span>{{ configGroupDetail.sort }}</span>
        </el-form-item>
        <el-form-item label="创建人ID">
          <span>{{ configGroupDetail.created_by || '未知' }}</span>
        </el-form-item>
        <el-form-item label="创建时间">
          <span>{{ formatDateTime(configGroupDetail.created_at) }}</span>
        </el-form-item>
        <el-form-item label="更新人ID">
          <span>{{ configGroupDetail.updated_by || '未知' }}</span>
        </el-form-item>
        <el-form-item label="更新时间">
          <span>{{ formatDateTime(configGroupDetail.updated_at) }}</span>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="detailDialogVisible = false">关闭</el-button>
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
import { CirclePlus, Delete, EditPen, View } from "@element-plus/icons-vue";
import {
  getListApi as getConfigGroupListApi,
  postCreateApi,
  getReadApi,
  putUpdateApi,
  deleteDeleteApi,
} from "@/api/modules/configGroup";

// 状态变量
const proTable = ref<InstanceType<typeof ProTable>>();
const formRef = ref<FormInstance>();
const dialogVisible = ref(false);
const isEdit = ref(false);
const detailDialogVisible = ref(false);

// 详情数据
const configGroupDetail = ref<ConfigGroup.ConfigGroupOptions>({
  system_config_group_id: 0,
  group_name: "",
  created_by: 0,
  created_at: "",
  updated_by: 0,
  updated_at: "",
  sort: 0
});

import type { ConfigGroup } from "@/typings/configGroup";

// 表单数据
const configGroupForm = ref<ConfigGroup.ConfigGroupOptions>({
  system_config_group_id: 0,
  group_name: "",
  sort: 0,
  created_by: 0,
  created_at: "",
  updated_by: 0,
  updated_at: ""
});

// 初始化参数
const initParam = reactive({});

// 处理接口返回数据
const dataCallback = (res: any) => {
  const safeData = res || {};
  return {
    list: safeData.list || [],
    total: safeData.total || 0
  };
};

// 获取列表数据
const getConfigGroupList = (params: any) => {
  return getConfigGroupListApi(params);
};

// 表单验证规则
const formRules = reactive<FormRules>({
  group_name: [
    { required: true, message: "分组名称不能为空", trigger: "blur" },
    { max: 255, message: "分组名称长度不能超过255个字符", trigger: "blur" }
  ],
  sort: [
    { required: true, message: "排序值不能为空", trigger: "blur" },
    // 直接使用 min 规则，async-validator 在处理 min 时，如果值是数字字符串，通常会正确转换比较
    { type: 'number', min: 0, message: "排序值不能小于0", trigger: "blur", transform: (value) => Number(value) } // 更稳妥的方式
  ]
});

// 初始化表单
const initForm = () => {
  configGroupForm.value = {
    system_config_group_id: 0,
    group_name: "",
    sort: 0
  };
};

// 新增配置分组
const addConfigGroup = () => {
  isEdit.value = false;
  initForm();
  dialogVisible.value = true;
};

// 编辑配置分组
const editConfigGroup = async (row: ConfigGroup.ConfigGroupOptions) => {
  isEdit.value = true;
  try {
    const res = await getReadApi(row.system_config_group_id);
    const formData = res.data || {};
    configGroupForm.value = {
      system_config_group_id: formData.system_config_group_id || 0,
      group_name: formData.group_name || "",
      sort: formData.sort || 0
    };
    dialogVisible.value = true;
  } catch (error) {
  }
};

// 删除单个配置分组
const deleteConfigGroup = async (row: ConfigGroup.ConfigGroupOptions) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除配置分组 "${row.group_name}" 吗?`,
      "提示",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }
    );

    await deleteDeleteApi(row.system_config_group_id);
    ElMessage.success("删除成功");
    proTable.value?.getTableList();
  } catch (error) {
    // 用户取消删除，不做处理
  }
};

// 提交表单
const submitForm = async () => {
  if (!formRef.value) return;


  try {
    await formRef.value.validate();
    const payload = { ...configGroupForm.value };

    if (isEdit.value && payload.system_config_group_id) {
      await putUpdateApi(payload.system_config_group_id, payload);
      ElMessage.success("更新成功");
    } else {
      await postCreateApi(payload);
      ElMessage.success("创建成功");
    }

    dialogVisible.value = false;
    proTable.value?.getTableList();
  } catch (error: any) {
    ElMessage.error(error.message || "提交失败");
  }
};

// 格式化日期时间
const formatDateTime = (dateString: string | undefined) => {
  // 函数内部的 !dateString 判断逻辑已经可以完美处理 undefined 或空字符串的情况
  if (!dateString) return "未知";
  return new Date(dateString).toLocaleString();
};

// 表格列定义
const columns = reactive<ColumnProps[]>([
  { prop: "system_config_group_id", label: "分组ID", width: 100 },
  { prop: "group_name", label: "分组名称", search: { el: "input" } },
  { prop: "sort", label: "排序", sortable: true, width: 80 },
  { prop: "operation", label: "操作", fixed: "right", width: 300 }
]);

// 打开详情弹窗
const openDetailDialog = async (groupId: number) => {
  try {
    const res = await getReadApi(groupId);
    const data = res.data || {};
    configGroupDetail.value = {
      system_config_group_id: data.system_config_group_id || 0,
      group_name: data.group_name || "",
      created_by: data.created_by || 0,
      created_at: data.created_at || "",
      updated_by: data.updated_by || 0,
      updated_at: data.updated_at || "",
      sort: data.sort || 0
    };
    detailDialogVisible.value = true;
  } catch (error) {
    ElMessage.error("获取配置分组详情失败");
  }
};
</script>

<style scoped>
/* 表格容器样式 */
.table-box {
  padding: 20px;
  background-color: #fff;
  border-radius: 4px;
  box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.05);
}

/* 表单提示文字 */
.form-tip {
  font-size: 12px;
  color: #999;
  margin-top: 4px;
  line-height: 1.4;
}
</style>
