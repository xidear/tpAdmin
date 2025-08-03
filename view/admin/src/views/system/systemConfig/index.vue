<template>
  <div class="config-management-page">
    <ProTable
      ref="proTable"
      title="配置项管理"
      row-key="system_config_id"
      :columns="columns"
      :request-api="getConfigList"
      :init-param="initParam"
      :data-callback="dataCallback"
      :pagination="true"
      :loading="pageLoading"
    >
      <!-- 表格头部按钮 -->
      <template #tableHeader>
        <el-button
          type="primary"
          :icon="Plus"
          @click="handleAdd"
          v-auth="'create'"
        >
          新增配置项
        </el-button>
      </template>

      <!-- 操作列 -->
      <template #operation="scope">
        <el-button
          type="primary"
          link
          :icon="Edit"
          @click="handleEdit(scope.row)"
          v-auth="'update'"
        >
          编辑
        </el-button>
        <el-button
          type="primary"
          link
          :icon="View"
          @click="handleView(scope.row.system_config_id)"
          v-auth="'read'"
        >
          查看
        </el-button>
        <el-button
          type="danger"
          link
          :icon="Delete"
          @click="handleDelete(scope.row)"
          v-auth="'delete'"
        >
          删除
        </el-button>
      </template>

      <!-- 配置类型列 -->
      <template #config_type="scope">
        <el-tag
          :type="getEnumTagType(scope.row.config_type, enumData.ConfigType)"
          size="small"
        >
          {{ getEnumLabelByValue('ConfigType', scope.row.config_type) || '未知类型' }}
        </el-tag>
      </template>

      <!-- 启用状态列 -->
      <template #is_enabled="scope">
        <el-tag
          :type="scope.row.is_enabled === yesValue ? 'warning' : 'success'"
          size="small"
        >
          {{ getEnumLabelByValue('YesOrNo', scope.row.is_enabled) || '未知' }}
        </el-tag>


      </template>

      <!-- 系统配置标识 -->
      <template #is_system="scope">
        <el-tag
          :type="scope.row.is_system === yesValue ? 'warning' : 'success'"
          size="small"
        >
          {{ getEnumLabelByValue('YesOrNo', scope.row.is_system) || '未知' }}
        </el-tag>
      </template>
    </ProTable>

    <!-- 新增/编辑配置项弹窗 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="600px"
      :close-on-click-modal="false"
    >
      <el-form
        ref="configForm"
        :model="formData"
        :rules="formRules"
        label-width="120px"
        size="default"
      >
        <el-form-item label="配置键名" prop="config_key">
          <el-input
            v-model="formData.config_key"
            placeholder="请输入配置键名"
            :disabled="isEdit && isSystemConfig(formData)"
          />
        </el-form-item>

        <el-form-item label="配置名称" prop="config_name">
          <el-input
            v-model="formData.config_name"
            placeholder="请输入配置名称"
          />
        </el-form-item>

        <el-form-item label="配置分组" prop="system_config_group_id">

          <RemoteSelect
            v-model="formData.system_config_group_id"
            :remoteMethod="handleFormGroupRemoteSearch"
            placeholder="请选择分组"
            :pageSize="200"
            labelKey="group_name"
            valueKey="system_config_group_id"
          />


        </el-form-item>

        <el-form-item label="配置类型" prop="config_type">
          <el-select
            v-model="formData.config_type"
            placeholder="请选择配置类型"
            :disabled="isEdit && isSystemConfig(formData)"
            @change="handleConfigTypeChange"
          >
            <el-option
              v-for="item in enumData.ConfigType"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>

        <!-- 选项配置（扩展支持多种类型） -->
        <el-form-item label="选项配置" prop="options" v-if="needsOptions(formData.config_type)">
          <el-input
            v-model="optionsText"
            placeholder="请输入选项配置，格式：label:value;label2:value2"
            type="textarea"
            :rows="4"
          />
          <el-text type="info" size="small" class="mt-1">
            例如: 开启:1;关闭:2
          </el-text>
        </el-form-item>
        <!-- 验证规则配置 -->
        <el-form-item label="验证规则" prop="rules">
          <el-input
            v-model="rulesText"
            placeholder="请输入验证规则，格式：规则名:规则值;规则名2:规则值2"
            type="textarea"
            :rows="4"
          />
          <el-text type="info" size="small" class="mt-1">
            例如: required:true;min:1;max:15;pattern:^[a-zA-Z]+$<br>
            支持规则：required(必填:true/false)、min(最小值)、max(最大值)、pattern(正则表达式)
          </el-text>
        </el-form-item>


        <el-form-item label="排序" prop="sort">
          <el-input-number
            v-model="formData.sort"
            :min="0"
            :max="999"
            placeholder="请输入排序号"
          />
        </el-form-item>

        <el-form-item label="状态" prop="is_enabled">
          <el-radio-group v-model="formData.is_enabled">
            <el-radio :value="yesValue">{{ getEnumLabelByValue('YesOrNo', yesValue) }}</el-radio>
            <el-radio :value="noValue">{{ getEnumLabelByValue('YesOrNo', noValue) }}</el-radio>
          </el-radio-group>
        </el-form-item>


        <el-form-item label="是否系统" prop="is_system">
          <el-radio-group v-model="formData.is_system">
            <el-radio :value="yesValue">{{ getEnumLabelByValue('YesOrNo', yesValue) }}</el-radio>
            <el-radio :value="noValue">{{ getEnumLabelByValue('YesOrNo', noValue) }}</el-radio>
          </el-radio-group>
        </el-form-item>


        <el-form-item label="备注" prop="remark">
          <el-input
            v-model="formData.remark"
            placeholder="请输入备注信息"
            type="textarea"
            :rows="3"
          />
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>

    <!-- 配置项详情弹窗 -->
    <el-dialog
      v-model="detailVisible"
      title="配置项详情"
      width="600px"
      :close-on-click-modal="false"
    >
      <el-descriptions :column="1" border v-if="Object.keys(detailData).length">
        <el-descriptions-item label="配置ID">{{ detailData.system_config_id }}</el-descriptions-item>
        <el-descriptions-item label="配置键名">{{ detailData.config_key }}</el-descriptions-item>
        <el-descriptions-item label="配置名称">{{ detailData.config_name }}</el-descriptions-item>
        <el-descriptions-item label="配置分组">{{ detailData.config_group?.group_name || '-' }}</el-descriptions-item>
        <el-descriptions-item label="配置类型">
          <el-tag
            :type="getEnumTagType(detailData.config_type, enumData.ConfigType)"
            size="small"
          >
            {{ getEnumLabelByValue('ConfigType', detailData.config_type) || '未知类型' }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="配置值">
          <ConfigValueDisplay
            :type="detailData.config_type"
            :value="detailData.config_value"
            :options="detailData.options"
          />
        </el-descriptions-item>
        <el-descriptions-item label="状态">
          <el-tag
            :type="detailData.is_enabled === yesValue ? 'success' : 'danger'"
          >
            {{ getEnumLabelByValue('YesOrNo', detailData.is_enabled) || '未知' }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="是否系统配置">
          <el-tag
            :type="detailData.is_system === yesValue ? 'warning' : 'success'"
            size="small"
          >
            {{ getEnumLabelByValue('YesOrNo', detailData.is_system) || '未知' }}
          </el-tag>
        </el-descriptions-item>
        <el-descriptions-item label="排序">{{ detailData.sort }}</el-descriptions-item>
        <el-descriptions-item label="备注">{{ detailData.remark || '-' }}</el-descriptions-item>
        <el-descriptions-item label="创建时间">{{ formatDate(detailData.created_at) }}</el-descriptions-item>
        <el-descriptions-item label="更新时间">{{ formatDate(detailData.updated_at) }}</el-descriptions-item>
        <el-descriptions-item label="创建人">{{ detailData.created_by || '-' }}</el-descriptions-item>
        <el-descriptions-item label="更新人">{{ detailData.updated_by || '-' }}</el-descriptions-item>
      </el-descriptions>

      <template #footer>
        <el-button @click="detailVisible = false">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">

import { useAuthButtons } from "@/hooks/useAuthButtons";
import { computed, onMounted, reactive, ref, watch } from "vue";
import { ElMessage, ElMessageBox, type FormInstance } from "element-plus";
import ProTable from "@/components/ProTable/index.vue";
import { ColumnProps } from "@/components/ProTable/interface";
import { Delete, Edit, Plus, View } from "@element-plus/icons-vue";

// 导入配置项API
import { createConfigApi, deleteConfigApi, getConfigDetailApi, getConfigListApi, updateConfigApi } from "@/api/modules/config";

// 导入枚举API
import { clearEnumCache, getBatchEnumDataApi, getEnumLabelByValue } from "@/api/modules/enum";

// 导入配置分组API
import { getListApi as getConfigGroupsApi } from "@/api/modules/configGroup";

// 导入类型
import type { Config } from "@/typings/config";
import type { EnumDict, EnumItem } from "@/typings/enum";
import RemoteSelect from "@/components/ProTable/components/RemoteSelect.vue";
import ConfigValueDisplay from "@/views/system/systemConfig/components/ConfigValueDisplay.vue";

const { BUTTONS } = useAuthButtons();
const rulesText = ref("");
// 表格实例
const proTable = ref<InstanceType<typeof ProTable>>();

// 页面加载状态
const pageLoading = ref(true);

const initParam = reactive({});

// 弹窗状态
const dialogVisible = ref(false);
const detailVisible = ref(false);
const dialogTitle = ref("新增配置项");
const isEdit = ref(false);
const configForm = ref<FormInstance>();

// 表单数据
const formData = ref<Partial<Config.ConfigOptions & Config.ConfigFormData>>({
  config_key: "",
  config_value: "",
  config_name: "",
  config_type: undefined,
  system_config_group_id: undefined,
  options: [],
  rules: [],
  sort: 0,
  is_enabled: undefined,
  is_system: undefined,
  remark: ""
});

// 选项文本（用于编辑时的转换）
const optionsText = ref("");

// 从后端获取的枚举数据
const enumData = ref<EnumDict>({
  ConfigType: [], // 配置类型枚举
  YesOrNo: []     // 是/否枚举
});

// 配置分组列表（表格搜索用）
const configGroups = ref<any[]>([]);
// 表单远程搜索配置分组列表
const formConfigGroups = ref<any[]>([]);
// 表单分组搜索加载状态
const formGroupLoading = ref(false);

// 详情数据
const detailData = ref<Config.ConfigOptions>({} as Config.ConfigOptions);

// 枚举标签类型映射
const enumTagTypeMap = {
  1: "primary",
  2: "success",
  3: "warning",
  4: "info",
  5: "danger"
};

// 表单验证规则
const formRules = reactive({
  config_key: [
    { required: true, message: "请输入配置键名", trigger: "blur" },
    { pattern: /^[a-zA-Z_]+$/, message: "配置键名只能包含字母和下划线", trigger: "blur" }
  ],
  config_name: [
    { required: true, message: "请输入配置名称", trigger: "blur" }
  ],
  system_config_group_id: [
    { required: true, message: "请选择配置分组", trigger: "change" }
  ],
  config_type: [
    { required: true, message: "请选择配置类型", trigger: "change" }
  ],
  sort: [
    { required: true, message: "请输入排序号", trigger: "blur" }
  ],
  is_enabled: [
    { required: true, message: "请选择状态", trigger: "change" }
  ],
  is_system: [
    { required: true, message: "是否系统内置", trigger: "change" }
  ],
  options: [
    {
      validator: (rule: any, value: any, callback: any) => {
        if (needsOptions(formData.value.config_type)) {
          if (!optionsText.value) {
            return callback(new Error("请输入选项配置"));
          }

          try {
            // 解析选项文本为EnumItem数组
            const options: EnumItem[] = optionsText.value.split(';').map(item => {
              const [label, val] = item.split(':');
              if (!label || val === undefined) {
                throw new Error('格式错误');
              }
              return { label, value: val };
            });

            formData.value.options = options;
            callback();
          } catch (error) {
            callback(new Error("选项配置格式错误，请使用label:value;形式"));
          }
        } else {
          callback();
        }
      },
      trigger: "blur"
    }
  ],
  rules: [
    {
      validator: (rule: any, value: any, callback: any) => {
        if (rulesText.value) { // 有输入时才验证格式
          try {
            // 解析规则文本为EnumItem数组
            const rules: EnumItem[] = rulesText.value.split(';').map(item => {
              const [label, val] = item.split(':');
              if (!label || val === undefined) {
                throw new Error('格式错误');
              }
              // 基础规则名校验
              const validRuleNames = ['required', 'min', 'max', 'pattern'];
              if (!validRuleNames.includes(label) && !label.startsWith('custom:')) {
                throw new Error(`不支持的规则名: ${label}`);
              }
              return { label, value: val };
            });

            formData.value.rules = rules;
            callback();
          } catch (error: any) {
            callback(new Error(`规则格式错误: ${error.message}，请使用规则名:规则值;形式`));
          }
        } else {
          // 允许为空（非必填）
          formData.value.rules = [];
          callback();
        }
      },
      trigger: "blur"
    }
  ]
});

// 数据处理回调
const dataCallback = (res: any) => {
  const safeData = res || {};
  return {
    list: safeData.list || [],
    total: safeData.total || 0
  };
};

// 获取配置项列表
const getConfigList = (params: any) => {
  return getConfigListApi(params);
};

// 加载基础数据（枚举和配置分组）
const loadBaseData = async () => {
  try {
    pageLoading.value = true;

    // 1. 批量获取所需枚举
    enumData.value = await getBatchEnumDataApi(['ConfigType', 'YesOrNo']);

    // 2. 初始化配置分组
    const groupRes = await getConfigGroupsApi({ page: 1, list_rows: 15 });
    if (groupRes.data?.list) {
      configGroups.value = groupRes.data.list;
      formConfigGroups.value = groupRes.data.list;
    }

    // 3. 初始化表单默认值
    if (enumData.value.ConfigType.length) {
      formData.value.config_type = enumData.value.ConfigType[0].value;
    }
    if (enumData.value.YesOrNo.length) {
      formData.value.is_enabled = yesValue.value;
      formData.value.is_system = noValue.value;
    }

  } catch (error) {
    console.error("加载基础数据失败:", error);
    ElMessage.error("加载基础数据失败");
  } finally {
    pageLoading.value = false;
  }
};

// 统一的配置分组远程搜索方法
const fetchConfigGroups = async (query: string, page: number, list_rows: number) => {
  try {
    const res = await getConfigGroupsApi({
      page,
      list_rows,
      keyword: query
    });
    return {
      list: res.data?.list || [],
      total: res.data?.total || 0
    };
  } catch (error) {
    return { list: [], total: 0 };
  }
};

// 表格筛选使用
const handleTableGroupRemoteSearch = async (query: string, page: number, list_rows: number) => {
  const result = await fetchConfigGroups(query, page, list_rows);
  return {
    list: result.list.map((group: any) => ({
      label: group.group_name,
      value: group.system_config_group_id
    })),
    total: result.total
  };
};

// 表单搜索使用
const handleFormGroupRemoteSearch = async (params: any) => {
  return fetchConfigGroups(params.query, params.page, params.pageSize);
};

// 判断是否为系统配置
const isSystemConfig = (config: Partial<Config.ConfigOptions>) => {
  return config.is_system === yesValue.value;
};

// 判断配置类型是否匹配指定的label
const isConfigType = (configType: number | string | undefined, label: string) => {
  if (!configType || !enumData.value.ConfigType.length) return false;
  return enumData.value.ConfigType.some(item => item.value === configType && item.label === label);
};

const OPTION_NEEDED_VALUES = [10, 11, 12, 13, 14]; // 这里填写实际值

// 判断是否需要选项配置（使用实际枚举值）
const needsOptions = (configType: number | string | undefined) => {
  if (configType === undefined) return false;
  // 转换为数字进行比较（确保类型一致）
  const typeValue = typeof configType === 'number' ? configType : Number(configType);
  return !isNaN(typeValue) && OPTION_NEEDED_VALUES.includes(typeValue);
};

// 根据枚举值获取标签类型
const getEnumTagType = (value: number | string, enumItems: EnumItem[]) => {
  const index = enumItems.findIndex(item => item.value === value);
  return enumTagTypeMap[(index % 5) + 1] || "info";
};

// 处理配置类型变更
const handleConfigTypeChange = () => {
  if (needsOptions(formData.value.config_type) &&
    (!formData.value.options || !formData.value.options.length)) {
    const yesLabel = getEnumLabelByValue('YesOrNo', yesValue.value);
    const noLabel = getEnumLabelByValue('YesOrNo', noValue.value);
    optionsText.value = `${yesLabel}:${yesValue.value};${noLabel}:${noValue.value}`;
    formData.value.options = [
      { label: yesLabel, value: yesValue.value },
      { label: noLabel, value: noValue.value }
    ];
  } else if (!needsOptions(formData.value.config_type)) {
    formData.value.options = [];
    optionsText.value = "";
  }
};

// 监听选项文本变化
watch(
  () => optionsText.value,
  (newVal) => {
    if (needsOptions(formData.value.config_type) && newVal) {
      try {
        formData.value.options = newVal.split(';').map(item => {
          const [label, value] = item.split(':');
          return { label: label || value, value } as EnumItem;
        });
      } catch (error) {
        // 格式错误时不更新
      }
    }
  }
);
watch(
  () => rulesText.value,
  (newVal) => {
    if (newVal) {
      try {
        formData.value.rules = newVal.split(';').map(item => {
          const [label, value] = item.split(':');
          return { label: label || value, value } as EnumItem;
        });
      } catch (error) {
        // 格式错误时不更新
      }
    } else {
      formData.value.rules = [];
    }
  }
);

// 新增配置项
const handleAdd = () => {
  dialogTitle.value = "新增配置项";
  isEdit.value = false;
  formData.value = {
    config_key: "",
    config_value: "",
    config_name: "",
    config_type: enumData.value.ConfigType[0]?.value || "",
    system_config_group_id: undefined,
    options: [],
    sort: 99,
    is_enabled: yesValue.value,
    is_system: noValue.value,
    remark: ""
  };
  optionsText.value = "";
  formConfigGroups.value = [];
  dialogVisible.value = true;
  handleConfigTypeChange();
};

// 编辑配置项
const handleEdit = async (row: Config.ConfigOptions) => {
  dialogTitle.value = "编辑配置项";
  isEdit.value = true;

  try {
    const res = await getConfigDetailApi(row.system_config_id);
    formData.value = { ...res.data };

    // 转换选项为文本格式
    if (needsOptions(formData.value.config_type) && formData.value.options && formData.value.options.length) {
      optionsText.value = formData.value.options
        .map((opt: EnumItem) => `${opt.label}:${opt.value}`)
        .join(';');
    } else {
      optionsText.value = "";
    }

    // 初始化配置分组选项
    if (formData.value.system_config_group_id) {
      formConfigGroups.value = [{
        system_config_group_id: formData.value.system_config_group_id,
        group_name: row.config_group?.group_name || ''
      }];
    }

    // 转换规则为文本格式（新增这段）
    if (formData.value.rules && formData.value.rules.length) {
      rulesText.value = formData.value.rules
        .map((rule: EnumItem) => `${rule.label}:${rule.value}`)
        .join(';');
    } else {
      rulesText.value = "";
    }


    dialogVisible.value = true;
  } catch (error) {
    ElMessage.error("获取配置项详情失败");
    console.error(error);
  }
};

// 查看配置项详情
const handleView = async (system_config_id: number) => {
  try {
    const res = await getConfigDetailApi(system_config_id);
    detailData.value = res.data;
    detailVisible.value = true;
  } catch (error) {
    ElMessage.error("获取配置项详情失败");
    console.error(error);
  }
};

// 删除配置项
const handleDelete = async (row: Config.ConfigOptions) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除配置项【${row.config_name}】吗？`,
      "删除确认",
      {
        confirmButtonText: "确认删除",
        cancelButtonText: "取消",
        type: "warning"
      }
    );

    await deleteConfigApi(row.system_config_id);
    ElMessage.success("删除成功");
    proTable.value?.getTableList();
  } catch (error) {
    // 忽略取消操作
  }
};

// 提交表单
const handleSubmit = async () => {
  if (!configForm.value) return;

  try {
    await configForm.value.validate();

    // 处理提交数据
    const submitData = { ...formData.value };
    if (isEdit.value && submitData.system_config_id) {
      // 更新操作
      await updateConfigApi(submitData.system_config_id, submitData);
      ElMessage.success("配置项更新成功");
    } else {
      // 新增操作
      await createConfigApi(submitData as Config.ConfigFormData);
      ElMessage.success("配置项新增成功");
    }

    dialogVisible.value = false;
    proTable.value?.getTableList();
  } catch (error: any) {
    if (error.name !== 'Error') {
      // 表单验证失败
      return;
    }
    ElMessage.error(isEdit.value ? "配置项更新失败" : "配置项新增失败");
    console.error(error);
  }
};

// 刷新页面数据
const refreshPage = async () => {
  try {
    pageLoading.value = true;
    clearEnumCache();
    await loadBaseData();
    proTable.value?.getTableList();
  } catch (error) {
    console.error("刷新数据失败:", error);
  } finally {
    pageLoading.value = false;
  }
};

// 日期格式化
const formatDate = (dateString: string | undefined) => {
  if (!dateString) return "-";
  try {
    return new Date(dateString).toLocaleString();
  } catch (e) {
    return dateString || "-";
  }
};

// "是/否"枚举值快捷访问
const yesValue = computed(() => {
  const yesItem = enumData.value.YesOrNo.find(item => item.label === "是");
  return yesItem?.value || 1;
});
const noValue = computed(() => {
  const noItem = enumData.value.YesOrNo.find(item => item.label === "否");
  return noItem?.value || 2;
});

// 表格列配置
const columns = reactive<ColumnProps[]>([
  {
    prop: "config_key",
    label: "配置键名",
    search: { el: "input" }
  },
  {
    prop: "config_name",
    label: "配置名称",
    search: { el: "input" }
  },
  {
    prop: "config_group.group_name",
    label: "配置分组",
    search: {
      key: "system_config_group_id",
      el: "select",
      props: {
        remote: true,
        filterable: true,
        listRows:200,
      },
      remoteMethod: handleTableGroupRemoteSearch
    }
  },
  {
    prop: "config_type",
    label: "配置类型",
    width: 120
  },
  {
    prop: "is_enabled",
    label: "状态",
    width: 100
  },
  {
    prop: "is_system",
    label: "是否系统配置",
    width: 140
  },
  {
    prop: "sort",
    label: "排序",
    width: 80
  },
  {
    prop: "updated_at",
    label: "更新时间",
    width: 160
  },
  {
    prop: "operation",
    label: "操作",
    fixed: "right",
    width: 220
  }
]);

// 页面挂载时加载数据
onMounted(() => {
  loadBaseData();
});
</script>

<style scoped>
.config-management-page {
  padding: 16px;
  background-color: #fff;
  min-height: calc(100vh - 120px);
}

:deep(.el-descriptions-item__label) {
  font-weight: 500;
  background-color: #f5f7fa;
}

:deep(.el-dialog__body) {
  max-height: 60vh;
  overflow-y: auto;
}

.mt-1 {
  margin-top: 8px;
}
</style>
