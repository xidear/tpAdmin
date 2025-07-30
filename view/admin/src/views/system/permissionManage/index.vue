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
      <!-- 表格头部按钮 - 新增同步按钮 -->
      <template #tableHeader>
        <el-button type="primary" :icon="Refresh" @click="syncPermission">同步数据</el-button>
      </template>

      <!-- 操作列 - 详情按钮 -->
      <template #operation="scope">
        <el-button type="primary" link :icon="View" @click="viewDetail(scope.row.permission_id)">详情</el-button>
      </template>

      <!-- 是否需要登录列 -->
      <template #need_login="scope">
        <el-tag v-if="scope.row.need_login === 1" type="info">需登录</el-tag>
        <el-tag v-else type="danger">无需登录</el-tag>
      </template>

      <!-- 是否需要权限验证列 -->
      <template #need_permission="scope">
        <el-tag v-if="scope.row.need_permission === 1" type="info">需验证</el-tag>
        <el-tag v-else type="danger">无需验证</el-tag>
      </template>

      <!-- 请求方法列 -->
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

    <!-- 权限详情弹窗 -->
    <el-dialog
      v-model="detailVisible"
      title="权限详情"
      width="500px"
      :close-on-click-modal="false"
    >
      <el-descriptions column="1" border v-if="Object.keys(detailData).length">
        <el-descriptions-item label="权限ID">{{ detailData.permission_id }}</el-descriptions-item>
        <el-descriptions-item label="权限节点">{{ detailData.node }}</el-descriptions-item>
        <el-descriptions-item label="权限名称">{{ detailData.name }}</el-descriptions-item>
        <el-descriptions-item label="路由规则">{{ detailData.rule }}</el-descriptions-item>
        <el-descriptions-item label="请求方法">
          <el-tag
            :type="{
              'get': 'primary',
              'post': 'success',
              'put': 'warning',
              'delete': 'danger',
              'patch': 'info'
            }[detailData.method?.toLowerCase()]"
            effect="light"
            size="small"
          >
            {{ detailData.method?.toUpperCase() || '-' }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="是否需要登录">
          <el-tag v-if="detailData.need_login === 1" type="info">需登录</el-tag>
          <el-tag v-else type="success">无需登录</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="是否需要权限验证">
          <el-tag v-if="detailData.need_permission === 1" type="warning">需验证</el-tag>
          <el-tag v-else type="success">无需验证</el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="权限描述">{{ detailData.description || '-' }}</el-descriptions-item>
        <el-descriptions-item label="创建时间">{{ formatDate(detailData.created_at) }}</el-descriptions-item>
        <el-descriptions-item label="更新时间">{{ formatDate(detailData.updated_at) }}</el-descriptions-item>
      </el-descriptions>

      <template #footer>
        <el-button @click="detailVisible = false">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { useAuthButtons } from "@/hooks/useAuthButtons";
const { BUTTONS } = useAuthButtons();

import { ref, reactive } from "vue";
import { ElMessage, ElMessageBox } from "element-plus";
import ProTable from "@/components/ProTable/index.vue";
import { ColumnProps } from "@/components/ProTable/interface";
import { View, Refresh } from "@element-plus/icons-vue";
import {
  getListApi as getPermissionListApi,
  getReadApi,
  postSyncApi  // 导入同步接口
} from "@/api/modules/permission";

// 表格实例引用
const proTable = ref<InstanceType<typeof ProTable>>();

// 初始请求参数
const initParam = reactive({});

// 详情相关状态
const detailVisible = ref(false);
const detailData = ref({});

// 数据处理回调函数
const dataCallback = (res: any) => {
  const safeData = res || {};
  return {
    list: safeData.list || [],
    total: safeData.total || 0
  };
};

// 获取权限列表的请求方法
const getPermissionList = (params: any) => {
  return getPermissionListApi(params);
};

// 同步数据方法
const syncPermission = async () => {
  try {
    // 显示确认弹窗
    await ElMessageBox.confirm(
      "确定要同步权限数据吗？这将更新系统中的权限节点信息。",
      "同步确认",
      {
        confirmButtonText: "确认同步",
        cancelButtonText: "取消",
        type: "info"
      }
    );

    // 调用同步接口
    await postSyncApi();
    
    // 同步成功后显示提示并刷新表格数据
    ElMessage.success("数据同步成功");
    proTable.value?.getTableList();  // 重新获取数据
  } catch (error) {
    // 捕获取消操作或错误
  }
};

// 查看详情
const viewDetail = async (permissionId: number) => {
  try {
    const res = await getReadApi(permissionId);
    detailData.value = res.data || {};
    detailVisible.value = true;
  } catch (error) {
    ElMessage.error("获取权限详情失败，请重试");
    console.error("详情获取失败:", error);
  }
};

// 安全的日期格式化函数
const formatDate = (dateString: string | undefined) => {
  if (!dateString) return "-";
  try {
    return new Date(dateString).toLocaleString();
  } catch (e) {
    return dateString || "-";
  }
};

// 表格列配置
const columns = reactive<ColumnProps[]>([
  { prop: "node", label: "权限节点", search: { el: "input" }},
  { prop: "name", label: "权限名称", search: { el: "input" }  },
  { prop: "rule", label: "路由规则", search: { el: "input" }  },
  {
    prop: "method",
    label: "请求方法",
    width: 100,
    formatter: (row) => row.method?.toUpperCase() || "N/A"
  },
  { prop: "need_login", label: "是否需要登录", width: 120},
  { prop: "need_permission", label: "是否需要权限验证", width: 150 },

  { prop: "operation", label: "操作", fixed: "right", width: 100 }
]);
</script>

<style scoped>
:deep(.el-descriptions-item__label) {
  font-weight: 500;
  background-color: #f5f7fa;
}

:deep(.el-descriptions) {
  margin-top: 10px;
}

/* 同步按钮样式 */
:deep(.el-table__header-wrapper .el-button) {
  margin-bottom: 10px;
}
</style>
