<template>
  <div class="table-box">
    <ProTable
      ref="proTable"
      title="定时任务管理"
      row-key="task_id"
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
        <el-button type="primary" v-auth="'read'" link :icon="View" @click="openDetailDialog(scope.row.task_id)">详情</el-button>
        <el-button type="primary" v-auth="'update'" link :icon="Edit" @click="openEditDialog(scope.row.task_id)">编辑</el-button>
        <el-button
          type="primary"
          v-auth="'toggleStatus'"
          link
          :icon="scope.row.status === getEnumValue('TaskStatus', '启用') ? CircleClose : CircleCheck"
          @click="toggleTaskStatus(scope.row)"
        >
          {{ scope.row.status === getEnumValue('TaskStatus', '启用') ? '停止' : '开启' }}
        </el-button>
        <el-button type="primary" v-auth="'executeNow'" link :icon="RefreshRight" @click="executeTaskNow(scope.row.task_id)">立即执行</el-button>
        <el-button type="primary" v-auth="'delete'" link :icon="Delete" @click="deleteTask(scope.row)">删除</el-button>
      </template>

      <!-- 状态列 -->
      <template #status="scope">
        <el-tag v-if="scope.row.status === getEnumValue('Status', '启用')" type="success">启用</el-tag>
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
            <el-descriptions-item label="任务ID">{{ taskDetail.task_id }}</el-descriptions-item>
            <el-descriptions-item label="任务名称">{{ taskDetail.name }}</el-descriptions-item>
            <el-descriptions-item label="任务类型">{{ getTypeName(taskDetail.type) }}</el-descriptions-item>
            <el-descriptions-item label="运行平台">{{ getPlatformName(taskDetail.platform) }}</el-descriptions-item>
            <el-descriptions-item label="状态">{{ taskDetail.status === getEnumValue('TaskStatus', '启用') ? '启用' : '禁用' }}</el-descriptions-item>
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
        <el-table-column prop="task_log_id" label="ID" width="80"></el-table-column>
        <el-table-column prop="start_time" label="开始时间" width="180" :formatter="(row) => formatDateTime(row.start_time)"></el-table-column>
        <el-table-column prop="end_time" label="结束时间" width="180" :formatter="(row) => formatDateTime(row.end_time)"></el-table-column>
        <el-table-column prop="duration" label="执行时长(ms)" width="120"></el-table-column>
        <el-table-column prop="status" label="状态" width="100">
          <template #default="scope">
            <el-tag
              :type="scope.row.status === getEnumValue('SuccessOrFail', '成功') ? 'success' :
                     scope.row.status === getEnumValue('SuccessOrFail', '失败') ? 'danger' :
                     scope.row.status === getEnumValue('SuccessOrFail', '超时') ? 'warning' : 'info'"
            >
              {{ getLogStatusName(scope.row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="server_ip" label="执行IP" width="120"></el-table-column>
        <el-table-column prop="operation" label="操作" width="120">
          <template #default="scope">
            <el-button
              type="primary"
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
        <el-descriptions-item label="日志ID">{{ currentLog.task_log_id }}</el-descriptions-item>
        <el-descriptions-item label="任务ID">{{ currentLog.task_id }}</el-descriptions-item>
        <el-descriptions-item label="任务名称">{{ currentLog.task_name }}</el-descriptions-item>
        <el-descriptions-item label="开始时间">{{ formatDateTime(currentLog.start_time) }}</el-descriptions-item>
        <el-descriptions-item label="结束时间">{{ formatDateTime(currentLog.end_time) }}</el-descriptions-item>
        <el-descriptions-item label="执行时长">{{ currentLog.duration }}毫秒</el-descriptions-item>
        <el-descriptions-item label="状态">
          <el-tag
            :type="currentLog.status === getEnumValue('SuccessOrFail', '成功') ? 'success' :
                   currentLog.status === getEnumValue('SuccessOrFail', '失败') ? 'danger' :
                   currentLog.status === getEnumValue('SuccessOrFail', '超时') ? 'warning' : 'info'"
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
      @close="handleDialogClose"
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
          <el-input type="textarea" v-model="taskForm.description" placeholder="请输入任务描述" :rows="3"></el-input>
        </el-form-item>
        <el-form-item label="任务类型" prop="type">
          <el-select v-model="taskForm.type" placeholder="请选择任务类型">
            <el-option 
              v-for="item in taskTypeOptions" 
              :key="item.key" 
              :label="item.value" 
              :value="item.key"
            />
          </el-select>
        </el-form-item>
        
        <el-form-item label="运行平台" prop="platform">
          <el-select v-model="taskForm.platform" placeholder="请选择运行平台">
            <el-option 
              v-for="item in platformOptions" 
              :key="item.key" 
              :label="item.value" 
              :value="item.key"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="执行用户" prop="exec_user" v-if="taskForm.platform === getEnumValue('TaskPlatform', 'Linux')">
          <el-input v-model="taskForm.exec_user" placeholder="请输入Linux执行用户"></el-input>
        </el-form-item>
        <el-form-item label="执行模式" prop="execute_mode">
          <el-radio-group v-model="taskForm.execute_mode">
            <el-radio 
              v-for="item in executeModeOptions" 
              :key="item.value" 
              :value="item.value"
            >
              {{ item.label }}
            </el-radio>
          </el-radio-group>
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="taskForm.status">
            <el-radio 
              v-for="item in taskStatusOptions" 
              :key="item.key" 
              :value="item.key"
            >
              {{ item.value }}
            </el-radio>
          </el-radio-group>
        </el-form-item>
        
        <el-form-item label="执行时间" prop="execute_at" v-if="taskForm.execute_mode === 2">
          <el-date-picker
            v-model="taskForm.execute_at"
            type="datetime"
            placeholder="请选择执行时间"
            format="YYYY-MM-DD HH:mm:ss"
            value-format="YYYY-MM-DD HH:mm:ss"
            :min="new Date()"
          />
        </el-form-item>
        <el-form-item label="调度规则" prop="schedule" v-if="taskForm.execute_mode === 1">
          <div class="cron-config">
            <!-- 预设选项 -->
            <div class="preset-options">
              <el-button-group>
                <el-button size="small" @click="setPresetCron('every-minute')">每分钟</el-button>
                <el-button size="small" @click="setPresetCron('every-hour')">每小时</el-button>
                <el-button size="small" @click="setPresetCron('every-day')">每天</el-button>
                <el-button size="small" @click="setPresetCron('every-week')">每周</el-button>
                <el-button size="small" @click="setPresetCron('every-month')">每月</el-button>
                <el-button size="small" @click="setPresetCron('every-year')">每年</el-button>
              </el-button-group>
            </div>
            
            <!-- 高级配置 -->
            <div class="advanced-config">
              <el-collapse v-model="activeCollapse">
                <el-collapse-item title="高级配置" name="advanced">
                  <div class="cron-fields">
                    <div class="cron-field">
                      <label>分钟</label>
                      <el-input v-model="cronConfig.minute" placeholder="*" @change="generateCronExpression">
                        <template #append>
                          <el-dropdown @command="(cmd) => setFieldPreset('minute', cmd)">
                            <el-button>
                              <el-icon><ArrowDown /></el-icon>
                            </el-button>
                            <template #dropdown>
                              <el-dropdown-menu>
                                <el-dropdown-item command="*">每分钟</el-dropdown-item>
                                <el-dropdown-item command="*/5">每5分钟</el-dropdown-item>
                                <el-dropdown-item command="*/10">每10分钟</el-dropdown-item>
                                <el-dropdown-item command="*/15">每15分钟</el-dropdown-item>
                                <el-dropdown-item command="0">0分</el-dropdown-item>
                              </el-dropdown-menu>
                            </template>
                          </el-dropdown>
                        </template>
                      </el-input>
                    </div>
                    
                    <div class="cron-field">
                      <label>小时</label>
                      <el-input v-model="cronConfig.hour" placeholder="*" @change="generateCronExpression">
                        <template #append>
                          <el-dropdown @command="(cmd) => setFieldPreset('hour', cmd)">
                            <el-button>
                              <el-icon><ArrowDown /></el-icon>
                            </el-button>
                            <template #dropdown>
                              <el-dropdown-menu>
                                <el-dropdown-item command="*">每小时</el-dropdown-item>
                                <el-dropdown-item command="*/2">每2小时</el-dropdown-item>
                                <el-dropdown-item command="*/6">每6小时</el-dropdown-item>
                                <el-dropdown-item command="0">0点</el-dropdown-item>
                                <el-dropdown-item command="9">9点</el-dropdown-item>
                              </el-dropdown-menu>
                            </template>
                          </el-dropdown>
                        </template>
                      </el-input>
                    </div>
                    
                    <div class="cron-field">
                      <label>日</label>
                      <el-input v-model="cronConfig.day" placeholder="*" @change="generateCronExpression">
                        <template #append>
                          <el-dropdown @command="(cmd) => setFieldPreset('day', cmd)">
                            <el-button>
                              <el-icon><ArrowDown /></el-icon>
                            </el-button>
                            <template #dropdown>
                              <el-dropdown-menu>
                                <el-dropdown-item command="*">每天</el-dropdown-item>
                                <el-dropdown-item command="1">1号</el-dropdown-item>
                                <el-dropdown-item command="15">15号</el-dropdown-item>
                                <el-dropdown-item command="L">最后一天</el-dropdown-item>
                              </el-dropdown-menu>
                            </template>
                          </el-dropdown>
                        </template>
                      </el-input>
                    </div>
                    
                    <div class="cron-field">
                      <label>月</label>
                      <el-input v-model="cronConfig.month" placeholder="*" @change="generateCronExpression">
                        <template #append>
                          <el-dropdown @command="(cmd) => setFieldPreset('month', cmd)">
                            <el-button>
                              <el-icon><ArrowDown /></el-icon>
                            </el-button>
                            <template #dropdown>
                              <el-dropdown-menu>
                                <el-dropdown-item command="*">每月</el-dropdown-item>
                                <el-dropdown-item command="1">一月</el-dropdown-item>
                                <el-dropdown-item command="6">六月</el-dropdown-item>
                                <el-dropdown-item command="12">十二月</el-dropdown-item>
                              </el-dropdown-menu>
                            </template>
                          </el-dropdown>
                        </template>
                      </el-input>
                    </div>
                    
                    <div class="cron-field">
                      <label>周</label>
                      <el-input v-model="cronConfig.week" placeholder="*" @change="generateCronExpression">
                        <template #append>
                          <el-dropdown @command="(cmd) => setFieldPreset('week', cmd)">
                            <el-button>
                              <el-icon><ArrowDown /></el-icon>
                            </el-button>
                            <template #dropdown>
                              <el-dropdown-menu>
                                <el-dropdown-item command="*">每天</el-dropdown-item>
                                <el-dropdown-item command="1">周一</el-dropdown-item>
                                <el-dropdown-item command="2">周二</el-dropdown-item>
                                <el-dropdown-item command="3">周三</el-dropdown-item>
                                <el-dropdown-item command="4">周四</el-dropdown-item>
                                <el-dropdown-item command="5">周五</el-dropdown-item>
                                <el-dropdown-item command="6">周六</el-dropdown-item>
                                <el-dropdown-item command="0">周日</el-dropdown-item>
                              </el-dropdown-menu>
                            </template>
                          </el-dropdown>
                        </template>
                      </el-input>
                    </div>
                  </div>
                </el-collapse-item>
              </el-collapse>
            </div>
            
            <!-- 预览区域 -->
            <div class="cron-preview">
              <div class="preview-item">
                <label>Crontab表达式：</label>
                <el-input v-model="taskForm.schedule" readonly placeholder="生成的crontab表达式"></el-input>
              </div>
              <div class="preview-item">
                <label>下次执行时间：</label>
                <el-input v-model="nextExecTime" readonly placeholder="计算下次执行时间"></el-input>
              </div>
            </div>
          </div>
        </el-form-item>
        <el-form-item label="任务内容" prop="content">
          <el-input
            type="textarea"
            v-model="taskForm.content"
            :rows="4"
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
  Plus, Delete, Edit, View, Open,TurnOff, RefreshRight, InfoFilled ,CircleClose,CircleCheck, ArrowDown
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
  type TaskItem,
  type TaskOptions,
  type TaskLogItem,
  type TaskLogListResponse,
  type OptionItem
} from "@/api/modules/task";

// 添加enum相关的导入
import { getBatchEnumDataApi } from "@/api/modules/enum";

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

// 优化预设选项，避免逻辑冲突
const presetCrons = {
  "every-minute": "* * * * *",
  "every-hour": "0 * * * *",
  "every-day": "0 0 * * *",  // 每天，忽略周
  "every-week": "0 0 * * 1",  // 每周一，忽略日
  "every-month": "0 0 1 * *", // 每月1号，忽略周
  "every-year": "0 0 1 1 *",   // 每年1月1号，忽略周
  // 新增一次性任务预设
  "specific-date": "0 9 15 12 *",     // 指定日期：12月15日9点
  "specific-week": "0 9 * * 1",      // 指定周：每周一9点
  "yearly-week": "0 9 * 1 1"         // 每年1月第1个周一
};
// 任务详情数据
const taskDetail = ref<TaskItem>({
  task_id: 0,
  name: "",
  description: "",
  type: 1, // 使用默认值，后面会从后端获取
  content: "",
  schedule: "",
  status: 1, // 使用默认值，后面会从后端获取
  platform: 0, // 使用默认值，后面会从后端获取
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
  task_log_id: 0,
  task_id: 0,
  task_name: "",
  start_time: "",
  end_time: null,
  duration: 0,
  status: 1, // 使用默认值，后面会从后端获取
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
  type: 1, // 使用默认值，后面会从后端获取
  content: "",
  schedule: "",
  status: 1, // 使用默认值，后面会从后端获取
  platform: 0, // 使用默认值，后面会从后端获取
  exec_user: "",
  timeout: 60,
  retry: 0,
  interval: 0,
  sort: 0,
  execute_mode: 1 // 默认为循环执行
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
  execute_mode: [
    { required: true, message: "请选择执行模式", trigger: "change" }
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

// 从后端获取的枚举数据
const enumData = ref<Record<string, Array<{label: string, value: number}>>>({});
const taskTypeOptions = ref<OptionItem[]>([]);
const platformOptions = ref<OptionItem[]>([]);
const executeModeOptions = ref<OptionItem[]>([]);
const taskStatusOptions = ref<OptionItem[]>([]);
const executeStatusOptions = ref<OptionItem[]>([]);

// Crontab配置数据
const cronConfig = reactive({
  minute: "*",
  hour: "*",
  day: "*",
  month: "*",
  week: "*"
});

const activeCollapse = ref<string[]>([]);
const nextExecTime = ref("");



// 设置预设crontab
const setPresetCron = (preset: keyof typeof presetCrons) => {
  const cronExpression = presetCrons[preset];
  taskForm.value.schedule = cronExpression;
  parseCronExpression(cronExpression);
  calculateNextExecTime();
  
  // 添加调试日志
  console.log('设置预设crontab:', preset, '->', cronExpression);
  console.log('当前taskForm.schedule:', taskForm.value.schedule);
};

// 解析crontab表达式到配置对象
const parseCronExpression = (expression: string) => {
  const parts = expression.split(" ");
  if (parts.length === 5) {
    // 处理带有 */ 格式的表达式，提取数值用于显示
    cronConfig.minute = parseCronField(parts[0]);
    cronConfig.hour = parseCronField(parts[1]);
    cronConfig.day = parseCronField(parts[2]);
    cronConfig.month = parseCronField(parts[3]);
    cronConfig.week = parseCronField(parts[4]);
  }
};

// 解析单个crontab字段
const parseCronField = (field: string): string => {
  // 如果是 */N 格式，提取 N
  const stepMatch = field.match(/^\*\/(\d+)$/);
  if (stepMatch) {
    return stepMatch[1]; // 返回数值部分
  }
  
  // 如果是逗号分隔的列表，且包含 */N 格式
  const parts = field.split(',');
  if (parts.length > 1) {
    const stepParts = parts.filter(part => /^\*\/\d+$/.test(part));
    if (stepParts.length === 1) {
      const stepMatch = stepParts[0].match(/^\*\/(\d+)$/);
      if (stepMatch) {
        return stepMatch[1]; // 返回主要步进值
      }
    }
  }
  
  // 其他情况返回原值
  return field;
};

// 生成crontab表达式时添加逻辑验证
const generateCronExpression = () => {
  const { minute, hour, day, month, week } = cronConfig;
  
  // 验证日和周字段的逻辑关系
  if (day !== "*" && week !== "*") {
    ElMessage.warning('注意：日字段和周字段是"或"关系，不是"且"关系');
  }
  
  // 将显示值转换回crontab格式
  const minuteCron = convertToCronFormat(minute);
  const hourCron = convertToCronFormat(hour);
  const dayCron = convertToCronFormat(day);
  const monthCron = convertToCronFormat(month);
  const weekCron = convertToCronFormat(week);
  
  const expression = `${minuteCron} ${hourCron} ${dayCron} ${monthCron} ${weekCron}`;
  taskForm.value.schedule = expression;
  calculateNextExecTime();
};

// 将显示值转换回crontab格式
const convertToCronFormat = (value: string): string => {
  // 如果是纯数字且不是特殊值，转换为 */N 格式
  if (/^\d+$/.test(value) && value !== "0" && value !== "L") {
    return `*/${value}`;
  }
  
  // 其他情况返回原值
  return value;
};

// 添加字段预设时的逻辑验证
const setFieldPreset = (field: keyof typeof cronConfig, value: string) => {
  cronConfig[field] = value;
  
  // 如果设置了日字段，清空周字段（避免逻辑冲突）
  if (field === 'day' && value !== '*') {
    cronConfig.week = '*';
  }
  
  // 如果设置了周字段，清空日字段（避免逻辑冲突）
  if (field === 'week' && value !== '*') {
    cronConfig.day = '*';
  }
  
  generateCronExpression();
};

// 计算下次执行时间
const calculateNextExecTime = () => {
  if (!taskForm.value.schedule) {
    nextExecTime.value = "";
    return;
  }
  
  try {
    const now = new Date();
    const parts = taskForm.value.schedule.split(" ");
    
    if (parts.length === 5) {
      const [minute, hour, day, month, week] = parts;
      const nextDate = new Date(now);
      
      // 处理分钟字段
      if (minute === "*") {
        // 每分钟执行，设置为下一分钟
        nextDate.setMinutes(nextDate.getMinutes() + 1);
      } else if (minute.startsWith("*/")) {
        // 处理 */N 格式
        const step = parseInt(minute.substring(2));
        if (!isNaN(step)) {
          const currentMinute = nextDate.getMinutes();
          const nextMinute = Math.floor(currentMinute / step) * step + step;
          if (nextMinute >= 60) {
            nextDate.setHours(nextDate.getHours() + 1);
            nextDate.setMinutes(nextMinute % 60);
          } else {
            nextDate.setMinutes(nextMinute);
          }
        }
      } else {
        // 处理具体分钟数
        const minuteNum = parseInt(minute);
        if (!isNaN(minuteNum)) {
          nextDate.setMinutes(minuteNum);
          // 如果设置的时间已经过去，加到下一个小时
          if (nextDate <= now) {
            nextDate.setHours(nextDate.getHours() + 1);
          }
        }
      }
      
      // 处理小时字段
      if (hour === "*") {
        // 每小时执行，不需要特别处理
      } else if (hour.startsWith("*/")) {
        // 处理 */N 格式
        const step = parseInt(hour.substring(2));
        if (!isNaN(step)) {
          const currentHour = nextDate.getHours();
          const nextHour = Math.floor(currentHour / step) * step + step;
          if (nextHour >= 24) {
            nextDate.setDate(nextDate.getDate() + 1);
            nextDate.setHours(nextHour % 24);
          } else {
            nextDate.setHours(nextHour);
          }
          nextDate.setMinutes(0); // 重置分钟
        }
      } else {
        // 处理具体小时数
        const hourNum = parseInt(hour);
        if (!isNaN(hourNum)) {
          nextDate.setHours(hourNum);
          // 如果设置的时间已经过去，加到下一天
          if (nextDate <= now) {
            nextDate.setDate(nextDate.getDate() + 1);
          }
        }
      }
      
      // 重置秒数为0
      nextDate.setSeconds(0);
      nextDate.setMilliseconds(0);
      
      // 如果计算的时间已经过去，加到下一个周期
      if (nextDate <= now) {
        if (day !== "*" || month !== "*") {
          // 如果有日或月限制，加一天
          nextDate.setDate(nextDate.getDate() + 1);
        } else {
          // 否则加一小时
          nextDate.setHours(nextDate.getHours() + 1);
        }
      }
      
      nextExecTime.value = nextDate.toLocaleString();
    }
  } catch (error) {
    console.error('计算下次执行时间失败:', error);
    nextExecTime.value = "计算失败";
  }
};
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
  try {
    const date = new Date(dateString);
    if (isNaN(date.getTime())) {
      return dateString; // 如果无法解析，返回原始字符串
    }
    return date.toLocaleString();
  } catch (error) {
    console.error('日期格式化失败:', error);
    return dateString; // 出错时返回原始字符串
  }
};

// 获取枚举值
const getEnumValue = (enumName: string, label: string): number => {
  const enumItems = enumData.value[enumName] || [];
  const item = enumItems.find(item => item.label === label);
  return item ? item.value : 1;
};

// 获取枚举标签
const getEnumLabel = (enumName: string, value: number): string => {
  const enumItems = enumData.value[enumName] || [];
  const item = enumItems.find(item => item.value === value);
  return item ? item.label : "未知";
};

// 获取任务类型名称
const getTypeName = (type: number) => {
  return getEnumLabel('TaskType', type);
};

// 获取任务类型标签样式
const getTypeTagType = (type: number) => {
  // 根据实际需求返回不同的标签样式
  const typeMap: Record<number, string> = {
    1: "warning",
    2: "info",
    3: "primary"
  };
  return typeMap[type] || "info";
};

// 获取平台名称
const getPlatformName = (platform: number) => {
  return getEnumLabel('TaskPlatform', platform);
};

// 获取日志状态名称
const getLogStatusName = (status: number) => {
  return getEnumLabel('SuccessOrFail', status);
};

// 获取执行状态名称
const getExecuteStatusName = (status: number) => {
  const option = executeStatusOptions.value.find(item => item.key === status);
  return option ? option.value : '未知状态';
};

// 获取执行状态标签类型
const getExecuteStatusTagType = (status: number) => {
  const statusName = getExecuteStatusName(status);
  switch (statusName) {
    case '成功':
      return 'success';
    case '失败':
      return 'danger';
    default:
      return 'info';
  }
};

// 获取任务内容占位符
const getTaskContentPlaceholder = () => {
  const type = taskForm.value.type;
  const typeName = getEnumLabel('TaskType', type);
  
  switch (type) {
    case getEnumValue('TaskType', '命令行'):
      return "请输入命令行命令，如：ls -l";
    case getEnumValue('TaskType', 'URL请求'):
      return "请输入URL地址，如：https://example.com/api";
    case getEnumValue('TaskType', 'PHP方法'):
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
    await deleteTaskApi(row.task_id);
    ElMessage.success("删除成功");
    proTable.value?.getTableList();
  } catch (error) {
    
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
  }
};

// 打开详情弹窗
const openDetailDialog = async (task_id: number) => {
  try {
    currentTaskId.value = task_id;
    logPage.value = 1;
    await loadTaskDetail(task_id);
    detailDialogVisible.value = true;
  } catch (error) {
  }
};

// 加载任务详情
const loadTaskDetail = async (task_id: number) => {
  const res = await getTaskReadApi(task_id, {
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
const openEditDialog = async (task_id?: number) => {
  editDialogTitle.value = task_id ? "编辑任务" : "新增任务";
  
  // 设置当前任务ID
  currentTaskId.value = task_id || null;
  
  if (task_id) {
    // 编辑模式，加载任务详情
    const res = await getTaskReadApi(task_id);
    if (res.data) {
      taskForm.value = { ...res.data.task };
      // 确保execute_mode有默认值
      if (!taskForm.value.execute_mode) {
        taskForm.value.execute_mode = 1;
      }
      // 解析crontab表达式
      if (taskForm.value.schedule) {
        parseCronExpression(taskForm.value.schedule);
        calculateNextExecTime();
      }
    }
  } else {
    // 新增模式，重置表单
    taskForm.value = {
      name: "",
      description: "",
      type: getEnumValue('TaskType', '命令行'), // 从后端获取枚举值
      content: "",
      schedule: "* * * * *",
      status: getEnumValue('Status', '启用'), // 从后端获取枚举值
      platform: getEnumValue('TaskPlatform', '全部'), // 从后端获取枚举值
      exec_user: "",
      timeout: 60,
      retry: 0,
      interval: 0,
      sort: 0,
      execute_mode: 1 // 默认为循环执行
    };
    // 重置crontab配置
    parseCronExpression("* * * * *");
    calculateNextExecTime();
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
    currentTaskId.value = null; // 重置当前任务ID
    proTable.value?.getTableList();
  } catch (error) {
  }
};

// 监听弹窗关闭事件
const handleDialogClose = () => {
  currentTaskId.value = null;
  taskFormRef.value?.resetFields();
};

// 切换任务状态
const toggleTaskStatus = async (row: TaskItem) => {
  try {
    const enabledValue = getEnumValue('Status', '启用');
    const confirmText = row.status === enabledValue ? "停止" : "开启";
    await ElMessageBox.confirm(
      `确定要${confirmText}任务"${row.name}"吗?`,
      "提示",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }
    );

    await toggleTaskStatusApi(row.task_id);
    ElMessage.success(`${confirmText}成功`);
    proTable.value?.getTableList();
  } catch (error) {
  }
};

// 立即执行任务
const executeTaskNow = async (task_id: number) => {
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

    await executeTaskNowApi(task_id);
    ElMessage.success("任务已触发执行");
    // 如果当前在详情页，刷新日志
    if (detailDialogVisible.value && currentTaskId.value === task_id) {
      await loadTaskDetail(task_id);
    }
  } catch (error) {
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

// 加载枚举数据
const loadEnumData = async () => {
  try {
    // 使用正确的枚举名称
    const result = await getBatchEnumDataApi(['TaskType', 'TaskPlatform', 'Status', 'SuccessOrFail', 'TaskExecuteMode']);
    enumData.value = result;
    
    // 转换格式以适配现有的选项数据结构
    taskTypeOptions.value = (result.TaskType || []).map(item => ({
      key: item.value,
      value: item.label
    }));
    
    platformOptions.value = (result.TaskPlatform || []).map(item => ({
      key: item.value,
      value: item.label
    }));
    
    // 使用Status而不是TaskStatus
    taskStatusOptions.value = (result.Status || []).map(item => ({
      key: item.value,
      value: item.label
    }));
    
    // 使用SuccessOrFail而不是LogStatus
    executeStatusOptions.value = (result.SuccessOrFail || []).map(item => ({
      key: item.value,
      value: item.label
    }));
    
    executeModeOptions.value = (result.TaskExecuteMode || []).map(item => ({
      value: item.value,
      label: item.label
    }));
    
    // 更新表单默认值
    if (taskForm.value.type === 1 && result.TaskType && result.TaskType.length > 0) {
      taskForm.value.type = result.TaskType[0].value;
    }
    if (taskForm.value.status === 1 && result.Status && result.Status.length > 0) {
      taskForm.value.status = result.Status[0].value;
    }
    if (taskForm.value.platform === 0 && result.TaskPlatform && result.TaskPlatform.length > 0) {
      taskForm.value.platform = result.TaskPlatform[0].value;
    }
    
  } catch (error) {
  }
};

// 加载选项数据
const loadOptions = async () => {
  try {
    await loadEnumData();
    
    // 如果需要额外的选项数据，可以在这里添加
    // const [typeRes, platformRes] = await Promise.all([
    //   getTaskTypeOptionsApi(),
    //   getPlatformOptionsApi()
    // ]);
    // taskTypeOptions.value = typeRes.data || [];
    // platformOptions.value = platformRes.data || [];
  } catch (error) {
  }
};

// 表格列定义
const columns = reactive<ColumnProps[]>([
  { type: "selection", fixed: "left", width: 70, onSelect: (row: TaskItem, selected: boolean, selectedRows: TaskItem[]) => {
      selectedList.value = selectedRows.map(item => item.task_id);
    }} ,
  { prop: "task_id", label: "ID", width: 80, sortable: true },
  {
    prop: "name",
    label: "任务名称",
    search: { el: "input" },
    width: 180
  },
   {
    prop: "type",
    label: "任务类型",
    enum: taskTypeOptions, // 使用动态获取的选项数据
    fieldNames: { label: "value", value: "key" }, // 指定字段映射
    search: {
      el: "select",
      props: { filterable: true }
    },
    width: 120
  },
  {
    prop: "platform", 
    label: "运行平台",
    enum: platformOptions, // 使用动态获取的选项数据
    fieldNames: { label: "value", value: "key" }, // 指定字段映射
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
    enum: taskStatusOptions, // 使用动态获取的选项数据
    fieldNames: { label: "value", value: "key" }, // 指定字段映射
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
  // 初始化默认crontab配置
  if (!taskForm.value.schedule) {
    taskForm.value.schedule = "* * * * *";
    parseCronExpression("* * * * *");
    calculateNextExecTime();
  }
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

/* Crontab配置样式 */
.cron-config {
  width: 100%;
}

.preset-options {
  margin-bottom: 15px;
}

.preset-options .el-button-group {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
}

.advanced-config {
  margin-bottom: 15px;
}

.cron-fields {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  padding: 10px 0;
}

.cron-field {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.cron-field label {
  font-weight: 500;
  color: #606266;
  font-size: 14px;
}

.cron-preview {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 15px;
  background-color: #f5f7fa;
  border-radius: 4px;
}

.preview-item {
  display: flex;
  align-items: center;
  gap: 10px;
}

.preview-item label {
  font-weight: 500;
  color: #606266;
  min-width: 120px;
}

.preview-item .el-input {
  flex: 1;
}
</style>
