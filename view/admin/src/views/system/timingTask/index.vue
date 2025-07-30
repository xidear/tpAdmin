<template>
  <div class="table-box">
    <ProTable
      ref="proTable"
      title="定时任务管理"
      row-key="id"
      :columns="columns"
      :request-api="getTaskList"
      :init-param="initParam"
      :data-callback="dataCallback"
      :pagination="true"
    >
      <!-- 表格 header 按钮 -->
      <template #tableHeader>
        <el-button
          v-auth="'create'"
          type="primary"
          :icon="Plus"
          @click="openEditDialog()"
        >
          新增任务
        </el-button>
        <el-button
          v-auth="'batchDelete'"
          type="danger"
          :icon="Delete"
          plain
          :disabled="!selectedList.length"
          @click="batchDelete"
        >
          批量删除
        </el-button>
      </template>

      <!-- 操作列 -->
      <template #operation="scope">
        <el-button type="primary" v-auth="'read'" link :icon="View" @click="openDetailDialog(scope.row.id)">详情</el-button>
        <el-button type="primary" v-auth="'update'" link :icon="Edit" @click="openEditDialog(scope.row.id)">编辑</el-button>
        <el-button
          type="primary"
          v-auth="'toggleStatus'"
          link
          :icon="scope.row.status === TaskStatus.ENABLED ? CircleClose : CircleCheck"
          @click="toggleTaskStatus(scope.row)"
        >
          {{ scope.row.status === TaskStatus.ENABLED ? '停止' : '开启' }}
        </el-button>
        <el-button type="primary" v-auth="'executeNow'" link :icon="RefreshRight" @click="executeTaskNow(scope.row.id)">立即执行</el-button>
        <el-button type="primary" v-auth="'delete'" link :icon="Delete" @click="deleteTask(scope.row)">删除</el-button>
      </template>

      <!-- 状态列 -->
      <template #status="scope">
        <el-tag v-if="scope.row.status === TaskStatus.ENABLED" type="success">启用</el-tag>
        <el-tag v-else type="info">禁用</el-tag>
      </template>

      <!-- 任务类型列 -->
      <template #type="scope">
        <el-tag :type="getTypeTagType(scope.row.type)">
          {{ getTypeName(scope.row.type) }}
        </el-tag>
      </template>

      <!-- 平台列 -->
      <template #platform="scope">
        <el-tag type="info">
          {{ getPlatformName(scope.row.platform) }}
        </el-tag>
      </template>
    </ProTable>

    <!-- 任务详情弹窗 -->
    <el-dialog
      v-model="detailDialogVisible"
      title="任务详情"
      width="900px"
      :close-on-click-modal="false"
    >
      <el-row :gutter="20">
        <el-col :span="12">
          <el-descriptions column="1" border>
            <el-descriptions-item label="任务ID">{{ taskDetail.id }}</el-descriptions-item>
            <el-descriptions-item label="任务名称">{{ taskDetail.name }}</el-descriptions-item>
            <el-descriptions-item label="任务类型">{{ getTypeName(taskDetail.type) }}</el-descriptions-item>
            <el-descriptions-item label="运行平台">{{ getPlatformName(taskDetail.platform) }}</el-descriptions-item>
            <el-descriptions-item label="状态">{{ taskDetail.status === TaskStatus.ENABLED ? '启用' : '禁用' }}</el-descriptions-item>
            <el-descriptions-item label="执行用户">{{ taskDetail.exec_user || '-' }}</el-descriptions-item>
          </el-descriptions>
        </el-col>
        <el-col :span="12">
          <el-descriptions column="1" border>
            <el-descriptions-item label="超时时间">{{ taskDetail.timeout }}秒</el-descriptions-item>
            <el-descriptions-item label="重试次数">{{ taskDetail.retry }}</el-descriptions-item>
            <el-descriptions-item label="重试间隔">{{ taskDetail.interval }}秒</el-descriptions-item>
            <el-descriptions-item label="最后执行时间">{{ formatDateTime(taskDetail.last_exec_time) }}</el-descriptions-item>
            <el-descriptions-item label="下次执行时间">{{ formatDateTime(taskDetail.next_exec_time) }}</el-descriptions-item>
            <el-descriptions-item label="创建时间">{{ formatDateTime(taskDetail.created_at) }}</el-descriptions-item>
          </el-descriptions>
        </el-col>
        <el-col :span="24">
          <el-descriptions column="1" border>
            <el-descriptions-item label="任务描述">{{ taskDetail.description || '-' }}</el-descriptions-item>
            <el-descriptions-item label="调度规则(crontab)">{{ taskDetail.schedule }}</el-descriptions-item>
            <el-descriptions-item label="任务内容">
              <pre class="task-content">{{ taskDetail.content }}</pre>
            </el-descriptions-item>
          </el-descriptions>
        </el-col>
      </el-row>

      <div class="logs-title">执行日志</div>
      <el-table
        :data="taskLogs.list"
        border
        style="width: 100%; margin-top: 10px"
        max-height="300"
      >
        <el-table-column prop="id" label="ID" width="80"></el-table-column>
        <el-table-column prop="start_time" label="开始时间" width="180" :formatter="formatDateTime"></el-table-column>
        <el-table-column prop="end_time" label="结束时间" width="180" :formatter="formatDateTime"></el-table-column>
        <el-table-column prop="duration" label="执行时长(ms)" width="120"></el-table-column>
        <el-table-column prop="status" label="状态" width="100">
          <template #default="scope">
            <el-tag
              :type="scope.row.status === LogStatus.SUCCESS ? 'success' :
                     scope.row.status === LogStatus.FAILED ? 'danger' :
                     scope.row.status === LogStatus.TIMEOUT ? 'warning' : 'info'"
            >
              {{ getLogStatusName(scope.row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="server_ip" label="执行IP" width="120"></el-table-column>
        <el-table-column prop="operation" label="操作" width="120">
          <template #default="scope">
            <el-button
              type="text"
              size="small"
              @click="showLogDetail(scope.row)"
            >
              查看详情
            </el-button>
          </template>
        </el-table-column>
      </el-table>
      <el-pagination
        v-if="taskLogs.total > 0"
        @size-change="handleLogSizeChange"
        @current-change="handleLogCurrentChange"
        :current-page="logPage"
        :page-sizes="[5, 10, 20]"
        :page-size="logLimit"
        layout="total, sizes, prev, pager, next, jumper"
        :total="taskLogs.total"
        style="margin-top: 10px; text-align: right"
      ></el-pagination>

      <template #footer>
        <el-button @click="detailDialogVisible = false">关闭</el-button>
      </template>
    </el-dialog>

    <!-- 日志详情弹窗 -->
    <el-dialog
      v-model="logDetailDialogVisible"
      title="执行日志详情"
      width="800px"
      :close-on-click-modal="false"
    >
      <el-descriptions column="1" border>
        <el-descriptions-item label="日志ID">{{ currentLog.id }}</el-descriptions-item>
        <el-descriptions-item label="任务ID">{{ currentLog.task_id }}</el-descriptions-item>
        <el-descriptions-item label="任务名称">{{ currentLog.task_name }}</el-descriptions-item>
        <el-descriptions-item label="开始时间">{{ formatDateTime(currentLog.start_time) }}</el-descriptions-item>
        <el-descriptions-item label="结束时间">{{ formatDateTime(currentLog.end_time) }}</el-descriptions-item>
        <el-descriptions-item label="执行时长">{{ currentLog.duration }}毫秒</el-descriptions-item>
        <el-descriptions-item label="状态">
          <el-tag
            :type="currentLog.status === LogStatus.SUCCESS ? 'success' :
                   currentLog.status === LogStatus.FAILED ? 'danger' :
                   currentLog.status === LogStatus.TIMEOUT ? 'warning' : 'info'"
          >
            {{ getLogStatusName(currentLog.status) }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="输出信息" v-if="currentLog.output">
          <pre class="log-content">{{ currentLog.output }}</pre>
        </el-descriptions-item>
        <el-descriptions-item label="错误信息" v-if="currentLog.error" type="error">
          <pre class="log-error">{{ currentLog.error }}</pre>
        </el-descriptions-item>
      </el-descriptions>
      <template #footer>
        <el-button @click="logDetailDialogVisible = false">关闭</el-button>
      </template>
    </el-dialog>

    <!-- 新增/编辑任务弹窗 -->
    <el-dialog
      v-model="editDialogVisible"
      :title="editDialogTitle"
      width="700px"
      :close-on-click-modal="false"
    >
      <el-form
        ref="taskFormRef"
        :model="taskForm"
        :rules="taskRules"
        label-width="120px"
        size="default"
      >
        <el-form-item label="任务名称" prop="name">
          <el-input v-model="taskForm.name" placeholder="请输入任务名称" max-length="100"></el-input>
        </el-form-item>
        <el-form-item label="任务描述" prop="description">
          <el-input type="textarea" v-model="taskForm.description" placeholder="请输入任务描述" rows="3"></el-input>
        </el-form-item>
        <el-form-item label="任务类型" prop="type">
          <el-select v-model="taskForm.type" placeholder="请选择任务类型">
            <el-option
              v-for="item in taskTypeOptions"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            ></el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="运行平台" prop="platform">
          <el-select v-model="taskForm.platform" placeholder="请选择运行平台">
            <el-option
              v-for="item in platformOptions"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            ></el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="执行用户" prop="exec_user" v-if="taskForm.platform === TaskPlatform.LINUX">
          <el-input v-model="taskForm.exec_user" placeholder="请输入Linux执行用户"></el-input>
        </el-form-item>
        <el-form-item label="调度规则" prop="schedule">
          <el-input v-model="taskForm.schedule" placeholder="请输入crontab表达式，如：* * * * *"></el-input>
          <el-tooltip content="格式：分 时 日 月 周，支持* / , -等符号" placement="top">
            <el-icon class="info-icon"><InfoFilled /></el-icon>
          </el-tooltip>
        </el-form-item>
        <el-form-item label="任务内容" prop="content">
          <el-input
            type="textarea"
            v-model="taskForm.content"
            placeholder="请输入任务内容"
            rows="4"
            :placeholder="getTaskContentPlaceholder()"
          ></el-input>
        </el-form-item>
        <el-form-item label="超时时间(秒)" prop="timeout">
          <el-input-number
            v-model="taskForm.timeout"
            :min="1"
            :step="1"
            placeholder="请输入超时时间"
          ></el-input-number>
        </el-form-item>
        <el-form-item label="失败重试次数" prop="retry">
          <el-input-number
            v-model="taskForm.retry"
            :min="0"
            :max="10"
            :step="1"
            placeholder="请输入重试次数"
          ></el-input-number>
        </el-form-item>
        <el-form-item label="重试间隔(秒)" prop="interval" v-if="taskForm.retry > 0">
          <el-input-number
            v-model="taskForm.interval"
            :min="1"
            :step="1"
            placeholder="请输入重试间隔"
          ></el-input-number>
        </el-form-item>
        <el-form-item label="排序" prop="sort">
          <el-input-number
            v-model="taskForm.sort"
            :min="0"
            :step="1"
            placeholder="请输入排序号"
          ></el-input-number>
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="taskForm.status">
            <el-radio :label="TaskStatus.ENABLED">启用</el-radio>
            <el-radio :label="TaskStatus.DISABLED">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="editDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitTaskForm">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="timingTask">
import { ref, reactive, onMounted } from "vue";
import { ElMessageBox, ElMessage, ElForm } from "element-plus";
import ProTable from "@/components/ProTable/index.vue";
import { ColumnProps } from "@/components/ProTable/interface";
import {
  Plus, Delete, Edit, View, Open,TurnOff, RefreshRight, InfoFilled ,CircleClose,CircleCheck
} from "@element-plus/icons-vue";
import {
  getTaskListApi,
  getTaskReadApi,
  postTaskCreateApi,
  putTaskUpdateApi,
  deleteTaskApi,
  batchDeleteTaskApi,
  toggleTaskStatusApi,
  executeTaskNowApi,
  getTaskTypeOptionsApi,
  getPlatformOptionsApi,
  TaskType,
  TaskPlatform,
  TaskStatus,
  LogStatus,
  type TaskItem,
  type TaskOptions,
  type TaskLogItem,
  type TaskLogListResponse,
  type OptionItem
} from "@/api/modules/task";

// 状态变量
const proTable = ref<InstanceType<typeof ProTable>>();
const taskFormRef = ref<InstanceType<typeof ElForm>>();
const detailDialogVisible = ref(false);
const logDetailDialogVisible = ref(false);
const editDialogVisible = ref(false);
const editDialogTitle = ref("新增任务");
const selectedList = ref<number[]>([]);
const currentTaskId = ref<number | null>(null);
const logPage = ref(1);
const logLimit = ref(10);

// 任务详情数据
const taskDetail = ref<TaskItem>({
  id: 0,
  name: "",
  description: "",
  type: TaskType.COMMAND,
  content: "",
  schedule: "",
  status: TaskStatus.DISABLED,
  platform: TaskPlatform.ALL,
  exec_user: null,
  timeout: 60,
  retry: 0,
  interval: 0,
  last_exec_time: null,
  next_exec_time: null,
  sort: 0,
  created_at: "",
  updated_at: ""
});

// 任务日志数据
const taskLogs = ref<TaskLogListResponse>({
  list: [],
  total: 0,
  page: 1,
  limit: 10,
  pages: 0
});

// 当前查看的日志详情
const currentLog = ref<TaskLogItem>({
  id: 0,
  task_id: 0,
  task_name: "",
  start_time: "",
  end_time: null,
  duration: 0,
  status: LogStatus.SUCCESS,
  output: null,
  error: null,
  pid: null,
  server_ip: null,
  created_at: ""
});

// 表单数据
const taskForm = ref<TaskOptions>({
  name: "",
  description: "",
  type: TaskType.COMMAND,
  content: "",
  schedule: "",
  status: TaskStatus.ENABLED,
  platform: TaskPlatform.ALL,
  exec_user: "",
  timeout: 60,
  retry: 0,
  interval: 0,
  sort: 0
});

// 表单验证规则
const taskRules = reactive({
  name: [
    { required: true, message: "请输入任务名称", trigger: "blur" },
    { max: 100, message: "任务名称不能超过100个字符", trigger: "blur" }
  ],
  type: [
    { required: true, message: "请选择任务类型", trigger: "change" }
  ],
  platform: [
    { required: true, message: "请选择运行平台", trigger: "change" }
  ],
  schedule: [
    { required: true, message: "请输入调度规则", trigger: "blur" },
    { pattern: /^(\*|(\d+|\*\/\d+)(,\d+|\*\/\d+)*)\s+(\*|(\d+|\*\/\d+)(,\d+|\*\/\d+)*)\s+(\*|(\d+|\*\/\d+)(,\d+|\*\/\d+)*)\s+(\*|(\d+|\*\/\d+)(,\d+|\*\/\d+)*)\s+(\*|(\d+|\*\/\d+)(,\d+|\*\/\d+)*)$/, message: "请输入有效的crontab表达式", trigger: "blur" }
  ],
  content: [
    { required: true, message: "请输入任务内容", trigger: "blur" }
  ],
  timeout: [
    { required: true, message: "请输入超时时间", trigger: "blur" },
    { type: "number", min: 1, message: "超时时间必须大于0", trigger: "blur" }
  ],
  retry: [
    { type: "number", min: 0, max: 10, message: "重试次数必须在0-10之间", trigger: "blur" }
  ],
  interval: [
    { type: "number", min: 1, message: "重试间隔必须大于0", trigger: "blur" }
  ]
});

// 选项数据
const taskTypeOptions = ref<OptionItem[]>([]);
const platformOptions = ref<OptionItem[]>([]);

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
const getTaskList = (params: any) => {
  return getTaskListApi(params);
};

// 格式化日期时间
const formatDateTime = (dateString: string | null) => {
  if (!dateString) return "-";
  return new Date(dateString).toLocaleString();
};

// 获取任务类型名称
const getTypeName = (type: TaskType) => {
  const typeMap: Record<TaskType, string> = {
    [TaskType.COMMAND]: "命令行",
    [TaskType.URL]: "URL请求",
    [TaskType.PHP_METHOD]: "PHP方法"
  };
  return typeMap[type] || "未知";
};

// 获取任务类型标签样式
const getTypeTagType = (type: TaskType) => {
  const typeMap: Record<TaskType, string> = {
    [TaskType.COMMAND]: "warning",
    [TaskType.URL]: "info",
    [TaskType.PHP_METHOD]: "primary"
  };
  return typeMap[type] || "default";
};

// 获取平台名称
const getPlatformName = (platform: TaskPlatform) => {
  const platformMap: Record<TaskPlatform, string> = {
    [TaskPlatform.ALL]: "全部",
    [TaskPlatform.LINUX]: "Linux",
    [TaskPlatform.WINDOWS]: "Windows"
  };
  return platformMap[platform] || "未知";
};

// 获取日志状态名称
const getLogStatusName = (status: LogStatus) => {
  const statusMap: Record<LogStatus, string> = {
    [LogStatus.FAILED]: "失败",
    [LogStatus.SUCCESS]: "成功",
    [LogStatus.TIMEOUT]: "超时",
    [LogStatus.CANCELED]: "取消"
  };
  return statusMap[status] || "未知";
};

// 获取任务内容占位符
const getTaskContentPlaceholder = () => {
  switch (taskForm.value.type) {
    case TaskType.COMMAND:
      return "请输入命令行命令，如：ls -l";
    case TaskType.URL:
      return "请输入URL地址，如：https://example.com/api";
    case TaskType.PHP_METHOD:
      return "请输入类和方法，格式：App\\Service\\DemoService@methodName";
    default:
      return "请输入任务内容";
  }
};

// 删除单个任务
const deleteTask = async (row: TaskItem) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除任务"${row.name}"吗?`,
      "提示",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }
    );
    await deleteTaskApi(row.id);
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
const batchDelete = async () => {
  if (selectedList.value.length === 0) {
    ElMessage.warning("请选择需要删除的任务");
    return;
  }

  try {
    await ElMessageBox.confirm(
      `确定要删除选中的 ${selectedList.value.length} 个任务吗?`,
      "提示",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }
    );

    await batchDeleteTaskApi({ ids: selectedList.value });
    ElMessage.success(`成功删除 ${selectedList.value.length} 个任务`);
    selectedList.value = [];
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
    currentTaskId.value = id;
    logPage.value = 1;
    await loadTaskDetail(id);
    detailDialogVisible.value = true;
  } catch (error) {
    ElMessage.error("获取任务详情失败");
  }
};

// 加载任务详情
const loadTaskDetail = async (id: number) => {
  const res = await getTaskReadApi(id, {
    log_page: logPage.value,
    log_limit: logLimit.value
  });

  const data = res.data || {};
  taskDetail.value = data.task || {};
  taskLogs.value = data.logs || {
    list: [],
    total: 0,
    page: 1,
    limit: 10,
    pages: 0
  };
};

// 打开编辑弹窗
const openEditDialog = (id?: number) => {
  if (id) {
    // 编辑模式
    currentTaskId.value = id;
    editDialogTitle.value = "编辑任务";
    // 从接口获取任务详情
    getTaskReadApi(id).then(res => {
      const data = res.data || {};
      const task = data.task || {};
      taskForm.value = {
        name: task.name || "",
        description: task.description || "",
        type: task.type || TaskType.COMMAND,
        content: task.content || "",
        schedule: task.schedule || "",
        status: task.status || TaskStatus.ENABLED,
        platform: task.platform || TaskPlatform.ALL,
        exec_user: task.exec_user || "",
        timeout: task.timeout || 60,
        retry: task.retry || 0,
        interval: task.interval || 0,
        sort: task.sort || 0
      };
    });
  } else {
    // 新增模式
    currentTaskId.value = null;
    editDialogTitle.value = "新增任务";
    // 重置表单
    taskForm.value = {
      name: "",
      description: "",
      type: TaskType.COMMAND,
      content: "",
      schedule: "",
      status: TaskStatus.ENABLED,
      platform: TaskPlatform.ALL,
      exec_user: "",
      timeout: 60,
      retry: 0,
      interval: 0,
      sort: 0
    };
  }
  editDialogVisible.value = true;
};

// 提交任务表单
const submitTaskForm = async () => {
  try {
    // 表单验证
    await taskFormRef.value?.validate();

    if (currentTaskId.value) {
      // 更新任务
      await putTaskUpdateApi(currentTaskId.value, taskForm.value);
      ElMessage.success("任务更新成功");
    } else {
      // 新增任务
      await postTaskCreateApi(taskForm.value);
      ElMessage.success("任务创建成功");
    }

    editDialogVisible.value = false;
    proTable.value?.getTableList();
  } catch (error) {
    if (error instanceof Error) {
      ElMessage.error(error.message);
    } else {
      ElMessage.error("操作失败，请重试");
    }
  }
};

// 切换任务状态
const toggleTaskStatus = async (row: TaskItem) => {
  try {
    const confirmText = row.status === TaskStatus.ENABLED ? "停止" : "开启";
    await ElMessageBox.confirm(
      `确定要${confirmText}任务"${row.name}"吗?`,
      "提示",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }
    );

    await toggleTaskStatusApi(row.id);
    ElMessage.success(`${confirmText}成功`);
    proTable.value?.getTableList();
  } catch (error) {
    // 忽略取消操作的错误
    if (error instanceof Error && !error.message.includes('取消')) {
      ElMessage.error("操作失败：" + error.message);
    }
  }
};

// 立即执行任务
const executeTaskNow = async (id: number) => {
  try {
    await ElMessageBox.confirm(
      "确定要立即执行该任务吗?",
      "提示",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "info"
      }
    );

    await executeTaskNowApi(id);
    ElMessage.success("任务已触发执行");
    // 如果当前在详情页，刷新日志
    if (detailDialogVisible.value && currentTaskId.value === id) {
      await loadTaskDetail(id);
    }
  } catch (error) {
    // 忽略取消操作的错误
    if (error instanceof Error && !error.message.includes('取消')) {
      ElMessage.error("操作失败：" + error.message);
    }
  }
};

// 显示日志详情
const showLogDetail = (log: TaskLogItem) => {
  currentLog.value = { ...log };
  logDetailDialogVisible.value = true;
};

// 日志分页处理
const handleLogSizeChange = async (val: number) => {
  logLimit.value = val;
  if (currentTaskId.value) {
    await loadTaskDetail(currentTaskId.value);
  }
};

const handleLogCurrentChange = async (val: number) => {
  logPage.value = val;
  if (currentTaskId.value) {
    await loadTaskDetail(currentTaskId.value);
  }
};

// 加载选项数据
const loadOptions = async () => {
  try {
    const [typeRes, platformRes] = await Promise.all([
      getTaskTypeOptionsApi(),
      getPlatformOptionsApi()
    ]);
    taskTypeOptions.value = typeRes.data || [];
    platformOptions.value = platformRes.data || [];
  } catch (error) {
    ElMessage.error("加载选项数据失败");
  }
};

// 表格列定义
const columns = reactive<ColumnProps[]>([
  { type: "selection", fixed: "left", width: 70, onSelect: (row: TaskItem, selected: boolean, selectedRows: TaskItem[]) => {
      selectedList.value = selectedRows.map(item => item.id);
    }} ,
  { prop: "id", label: "ID", width: 80, sortable: true },
  {
    prop: "name",
    label: "任务名称",
    search: { el: "input" },
    width: 180
  },
  {
    prop: "type",
    label: "任务类型",
    enum: [
      { label: "命令行", value: TaskType.COMMAND },
      { label: "URL请求", value: TaskType.URL },
      { label: "PHP方法", value: TaskType.PHP_METHOD }
    ],
    search: {
      el: "select",
      props: { filterable: true }
    },
    width: 120
  },
  {
    prop: "platform",
    label: "运行平台",
    enum: [
      { label: "全部", value: TaskPlatform.ALL },
      { label: "Linux", value: TaskPlatform.LINUX },
      { label: "Windows", value: TaskPlatform.WINDOWS }
    ],
    search: {
      el: "select",
      props: { filterable: true }
    },
    width: 120
  },
  {
    prop: "schedule",
    label: "调度规则",
    search: { el: "input" },
    width: 200
  },
  {
    prop: "status",
    label: "状态",
    enum: [
      { label: "启用", value: TaskStatus.ENABLED },
      { label: "禁用", value: TaskStatus.DISABLED }
    ],
    search: {
      el: "select",
      props: { filterable: true }
    },
    width: 100
  },
  {
    prop: "last_exec_time",
    label: "最后执行时间",
    search: {
      el: "date-picker",
      props: {
        type: "datetimerange",
        valueFormat: "YYYY-MM-DD HH:mm:ss",
        max: new Date()
      }
    },
    width: 180,
    formatter: (row: TaskItem) => formatDateTime(row.last_exec_time)
  },
  {
    prop: "next_exec_time",
    label: "下次执行时间",
    search: {
      el: "date-picker",
      props: {
        type: "datetimerange",
        valueFormat: "YYYY-MM-DD HH:mm:ss"
      }
    },
    width: 180,
    formatter: (row: TaskItem) => formatDateTime(row.next_exec_time)
  },
  {
    prop: "created_at",
    label: "创建时间",
    search: {
      el: "date-picker",
      props: {
        type: "datetimerange",
        valueFormat: "YYYY-MM-DD HH:mm:ss",
        max: new Date()
      }
    },
    width: 180,
    sortable: true,
    formatter: (row: TaskItem) => formatDateTime(row.created_at)
  },
  {
    prop: "operation",
    label: "操作",
    fixed: "right",
    width: 350
  }
]);

// 组件挂载时加载选项数据
onMounted(() => {
  loadOptions();
});
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
.task-content, .log-content, .log-error {
  white-space: pre-wrap;
  word-wrap: break-word;
  padding: 10px;
  border-radius: 4px;
  font-family: monospace;
  font-size: 13px;
  margin: 0;
  max-height: 300px;
  overflow-y: auto;
}

.task-content, .log-content {
  background-color: #f5f7fa;
}

.log-error {
  background-color: #fef0f0;
  color: #f56c6c;
}

.logs-title {
  font-size: 16px;
  font-weight: bold;
  margin: 20px 0 10px;
  padding-left: 5px;
  border-left: 3px solid #409eff;
}

.info-icon {
  margin-left: 10px;
  color: #409eff;
  cursor: pointer;
}
</style>
