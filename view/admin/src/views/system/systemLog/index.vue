<template>
  <div class="table-box">
    <ProTable
      ref="proTable"
      title="系统日志"
      row-key="id"
      :columns="columns"
      :request-api="getSystemLogList"
      :init-param="initParam"
      :data-callback="dataCallback"
      :pagination="true"
    >
      <!-- 表格 header 按钮 -->
      <template #tableHeader="scope">
        <el-button
          v-auth="'batchDelete'"
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
        <el-button type="primary" v-auth="'read'" link :icon="View" @click="openDetailDialog(scope.row.id)">详情</el-button>
        <el-button type="primary" v-auth="'delete'" link :icon="Delete" @click="deleteLog(scope.row)">删除</el-button>
      </template>

      <!-- 状态列 -->
      <template #status="scope">
        <el-tag v-if="scope.row.status === 1" type="success">成功</el-tag>
        <el-tag v-else type="danger">失败</el-tag>
      </template>

      <!-- 请求方法列 -->
      <template #request_method="scope">
        <el-tag :type="scope.row.request_method === 'GET' ? 'info' : 'warning'">
          {{ scope.row.request_method }}
        </el-tag>
      </template>
    </ProTable>

    <!-- 日志详情弹窗 -->
    <el-dialog
      v-model="detailDialogVisible"
      title="日志详情"
      width="800px"
      :close-on-click-modal="false"
    >
      <el-form
        :model="logDetail"
        label-width="140px"
        size="small"
      >
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="管理员信息">
              <span>
                <!-- 优先显示真实姓名，不存在则显示用户名 -->
                {{ logDetail.admin?.real_name ? `${logDetail.admin.real_name}(${logDetail.username})` : logDetail.username }}
              </span>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="日志ID">
              <span>{{ logDetail.id }}</span>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="管理员ID">
              <span>{{ logDetail.admin_id }}</span>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="操作状态">
              <el-tag :type="logDetail.status === 1 ? 'success' : 'danger'">
                {{ logDetail.status === 1 ? '成功' : '失败' }}
              </el-tag>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="模块">
              <span>{{ logDetail.module }}</span>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="控制器">
              <span>{{ logDetail.controller }}</span>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="操作方法">
              <span>{{ logDetail.action }}</span>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="路由名称">
              <span>{{ logDetail.route_name }}</span>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="请求方法">
              <el-tag :type="logDetail.request_method === 'GET' ? 'info' : 'warning'">
                {{ logDetail.request_method }}
              </el-tag>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="IP地址">
              <span>
                <a :href="`https://www.ip138.com/iplookup.php?ip=${logDetail.ip}`" target="_blank" rel="noopener noreferrer" class="ip-link">
                  {{ logDetail.ip }}
                </a>
              </span>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="执行时间(秒)">
              <span>{{ logDetail.execution_time }}</span>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="操作时间">
              <span>{{ formatDateTime(logDetail.created_at) }}</span>
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="操作描述">
              <span>{{ logDetail.description }}</span>
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="请求URL">
              <div class="url-content">{{ logDetail.request_url }}</div>
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="请求参数">
              <pre class="param-pre">{{ formatParams(logDetail.request_param) }}</pre>
            </el-form-item>
          </el-col>
          <el-col :span="24">
            <el-form-item label="用户代理">
              <div class="ua-content">
                <!-- 原始user_agent信息 -->
                <div class="ua-original">{{ logDetail.user_agent }}</div>

                <!-- 解析后的ua信息 -->
                <div class="ua-parsed" v-if="logDetail.ua">
                  <div class="ua-item">浏览器：{{ logDetail.ua.browser }} {{ logDetail.ua.browser_version }}</div>
                  <div class="ua-item">操作系统：{{ logDetail.ua.os }} {{ logDetail.ua.os_version }}</div>
                  <div class="ua-item">设备类型：{{ logDetail.ua.device }}</div>
                </div>
              </div>
            </el-form-item>
          </el-col>
          <el-col :span="24" v-if="logDetail.status !== 1">
            <el-form-item label="错误信息">
              <div class="error-content">{{ logDetail.error_msg }}</div>
            </el-form-item>
          </el-col>
        </el-row>
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
import type { FormInstance } from "element-plus";
import ProTable from "@/components/ProTable/index.vue";
import { ColumnProps } from "@/components/ProTable/interface";
import { Delete, View } from "@element-plus/icons-vue";
import {
  getListApi as getSystemLogListApi,
  getReadApi,
  batchDeleteDeleteApi,
  deleteApi
} from "@/api/modules/systemLog";
import { SystemLogItem } from "@/api/modules/systemLog"; // 引入类型定义

// 状态变量
const proTable = ref<InstanceType<typeof ProTable>>();
const detailDialogVisible = ref(false);

// 详情数据（完善数据结构定义）
const logDetail = ref<SystemLogItem>({
  id: 0,
  admin_id: 0,
  username: "",
  admin: null, // 管理员关联信息
  module: "",
  controller: "",
  action: "",
  route_path: "",
  route_name: "",
  description: "",
  request_method: "",
  request_url: "",
  request_param: "",
  ip: "",
  user_agent: "",
  ua: null, // 用户代理解析信息
  status: 1,
  error_msg: "",
  execution_time: 0,
  created_at: ""
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
const getSystemLogList = (params: any) => {
  return getSystemLogListApi(params);
};

// 格式化日期时间
const formatDateTime = (dateString: string) => {
  if (!dateString) return "";
  return new Date(dateString).toLocaleString();
};

// 格式化请求参数
const formatParams = (params: string) => {
  try {
    // 尝试解析JSON字符串
    const parsed = JSON.parse(params);
    return JSON.stringify(parsed, null, 2);
  } catch (e) {
    return params;
  }
};

// 删除单个日志
const deleteLog = async (row: any) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除ID为 "${row.id}" 的日志吗?`,
      "提示",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }
    );
    await deleteApi(row.id);
    ElMessage.success("删除成功");
    proTable.value?.getTableList();
  } catch (error) {
    // 忽略取消操作的错误
    if (error instanceof Error && !error.message.includes('取消')) {
      ElMessage.error("删除失败：" + error.message);
    }
  }
};

// 批量删除
const batchDelete = async (ids: number[]) => {
  if (ids.length === 0) {
    ElMessage.warning("请选择需要删除的日志");
    return;
  }

  try {
    await ElMessageBox.confirm(
      `确定要删除选中的 ${ids.length} 条日志吗?`,
      "提示",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }
    );

    await batchDeleteDeleteApi({ ids });
    ElMessage.success(`成功删除 ${ids.length} 条日志`);
    proTable.value?.clearSelection();
    proTable.value?.getTableList();
  } catch (error) {
    // 忽略取消操作的错误
    if (error instanceof Error && !error.message.includes('取消')) {
      ElMessage.error("批量删除失败：" + error.message);
    }
  }
};

// 打开详情弹窗
const openDetailDialog = async (id: number) => {
  try {
    const res = await getReadApi(id);
    const data = res.data || {};
    logDetail.value = {
      ...data,
      // 确保日期字段存在
      created_at: data.created_at || ""
    };
    detailDialogVisible.value = true;
  } catch (error) {
    ElMessage.error("获取日志详情失败");
  }
};

// 表格列定义
const columns = reactive<ColumnProps[]>([
  { type: "selection", fixed: "left", width: 70 },
  { prop: "id", label: "ID", width: 80, sortable: true },
  {
    prop: "username",
    label: "操作用户",
    search: { el: "input" },
    width: 120
  },
  {
    prop: "module",
    label: "模块",
    search: { el: "input" },
    width: 100
  },
  {
    prop: "controller",
    label: "控制器",
    search: { el: "input" },
    width: 120
  },
  {
    prop: "action",
    label: "操作方法",
    search: { el: "input" },
    width: 100
  },
  {
    prop: "description",
    label: "描述",
    search: { el: "input" },
    width: 150
  },
  {

    enum: [
      { label: "GET", value: "GET" },
      { label: "POST", value: "POST" },
      { label: "PUT", value: "PUT" },
      { label: "DELETE", value: "DELETE" }
    ],
    // 2. 搜索框配置：el指定为select，无需重复写options
    search: {
      el: "select", // 必须用el，而非type
      // 可选：添加筛选功能
      props: { filterable: true } // 透传给el-select的属性
    },
    prop: "request_method",
    label: "请求方法",
    width: 110,
    custom: true
  },
  {
    prop: "ip",
    label: "IP地址",
    search: { el: "input" },
    width: 130
  },
  {
    prop: "status",
    label: "状态",

    enum: [
      { label: "成功", value: 1 },
      { label: "失败", value: 2 }
    ],
    // 2. 搜索框配置：el指定为select，无需重复写options
    search: {
      el: "select", // 必须用el，而非type
      // 可选：添加筛选功能
      props: { filterable: true } // 透传给el-select的属性
    },
    width: 100,
    custom: true
  },
  {
    prop: "execution_time",
    label: "执行时间(秒)",
    width: 130,
    sortable: true
  },
  {
    prop: "created_at",
    label: "操作时间",
    search: {
      el: "date-picker",
      props: {
        type: "datetimerange",
        valueFormat: "YYYY-MM-DD HH:mm:ss",
        max: new Date(), // 限制最大时间为当前时间
        // 设置UI默认显示的范围（仅显示，不自动作为请求参数）
        'default-value': [ // 注意：element-plus中是default-value（短横线命名）
          new Date(Date.now() - 7 * 24 * 60 * 60 * 1000), // 一周前（仅UI显示）
          new Date() // 当前时间（仅UI显示）
        ]
      }
      // 移除 defaultValue，避免初始请求携带参数
    },
    width: 170,
    sortable: true,
    formatter: (row) => {
      const date = new Date(row.created_at);
      return (
        date.getFullYear() + "-" +
        String(date.getMonth() + 1).padStart(2, '0') + "-" +
        String(date.getDate()).padStart(2, '0') + " " +
        String(date.getHours()).padStart(2, '0') + ":" +
        String(date.getMinutes()).padStart(2, '0') + ":" +
        String(date.getSeconds()).padStart(2, '0')
      );
    }
  },
  // {
  //   prop: "created_at",
  //   label: "操作时间",
  //   search: {
  //     el: "date-picker",
  //     span: 2,
  //     props: { type: "datetimerange", valueFormat: "YYYY-MM-DD HH:mm:ss" },
  //     defaultValue: ["2022-11-12 11:35:00", "2022-12-12 11:35:00"]
  //   },
  //   width: 170,
  //   sortable: true,
  //   formatter: (row) => formatDateTime(row.created_at)
  // },
  {
    prop: "operation",
    label: "操作",
    fixed: "right",
    width: 160
  }
]);

</script>

<style scoped>
/* 表格容器样式 */
.table-box {
  padding: 20px;
  background-color: #fff;
  border-radius: 4px;
  box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.05);
}

/* 详情弹窗中的内容样式 */
.url-content {
  word-break: break-all;
  padding: 8px;
  background-color: #f5f7fa;
  border-radius: 4px;
  font-family: monospace;
  font-size: 13px;
}

.param-pre {
  white-space: pre-wrap;
  word-wrap: break-word;
  padding: 10px;
  background-color: #f5f7fa;
  border-radius: 4px;
  font-family: monospace;
  font-size: 13px;
  margin: 0;
  max-height: 200px;
  overflow-y: auto;
}

/* 用户代理样式优化 */
.ua-content {
  padding: 8px;
  background-color: #f5f7fa;
  border-radius: 4px;
  font-size: 13px;
}

.ua-original {
  margin-bottom: 8px;
  padding-bottom: 8px;
  border-bottom: 1px dashed #e5e7eb;
  word-break: break-all;
}

.ua-parsed {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
}

.ua-item {
  color: #666;
}

.error-content {
  color: #f56c6c;
  word-break: break-all;
  padding: 8px;
  background-color: #fef0f0;
  border-radius: 4px;
  font-size: 13px;
}
</style>
