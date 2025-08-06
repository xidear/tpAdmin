<template>
  <div class="table-box">
    <ProTable
      ref="proTable"
      title="省市区管理"
      row-key="region_id"
      :indent="20"
      :columns="columns"
      :data="filteredRegionData"
      :tree-props="{
        children: 'children',
        hasChildren: 'hasChildren',
        label: 'name'
      }"
      :pagination="false"
      lazy
      @node-expand="handleNodeExpand"
      @select="handleTableSelect"
      @select-all="handleTableSelectAll"
    >
      <!-- 表格 header 按钮 -->
      <template #tableHeader>
        <div class="flex items-center flex-wrap gap-2">
          <div class="search-box flex items-center gap-3 flex-wrap">
            <el-button
              type="primary"
              :icon="CirclePlus"
              @click="handleAdd"
            >
              新增地区
            </el-button>

            <el-button
              type="warning"
              :icon="Search"
              @click="handleMerge"
              :disabled="!canMerge"
            >
              合并地区
            </el-button>

            <el-button
              type="info"
              :icon="CirclePlus"
              @click="handleSplit"
              :disabled="!selectedRegion"
            >
              拆分地区
            </el-button>

            <!-- 搜索框 -->
            <el-input
              v-model="searchParams.name"
              placeholder="地区名称"
              clearable
              style="width: 200px"
            />
            <el-input
              v-model="searchParams.code"
              placeholder="地区编码"
              clearable
              style="width: 200px"
            />
            <el-select
              v-model="searchParams.type"
              placeholder="地区类型"
              clearable
              style="width: 150px"
            >
              <el-option label="省份" value="province" />
              <el-option label="城市" value="city" />
              <el-option label="区县" value="district" />
              <el-option label="街道" value="street" />
              <el-option label="乡镇" value="town" />
            </el-select>
            <el-button-group>
              <el-button type="primary" @click="handleSearch">搜索</el-button>
              <el-button @click="resetSearch">重置</el-button>
            </el-button-group>
          </div>
        </div>
      </template>

      <!-- 地区类型 -->
      <template #type="scope">
        <el-tag :type="getTypeTagType(scope.row.type)">
          {{ getTypeName(scope.row.type) }}
        </el-tag>
      </template>

      <!-- 状态显示 -->
      <template #status="scope">
        <el-tag :type="scope.row.deleted_at ? 'danger' : 'success'">
          {{ scope.row.deleted_at ? '已删除' : '正常' }}
        </el-tag>
      </template>

      <!-- 操作按钮 -->
      <template #operation="scope">
        <el-button
          @click="handleView(scope.row)"
          type="primary"
          link
          :icon="View"
          :disabled="scope.row.deleted_at"
        >
          查看
        </el-button>
        <el-button
          @click="handleEdit(scope.row)"
          type="primary"
          link
          :icon="EditPen"
          :disabled="scope.row.deleted_at"
        >
          编辑
        </el-button>
        <el-button
          @click="handleDelete(scope.row)"
          type="primary"
          link
          :icon="Delete"
          :disabled="scope.row.deleted_at"
        >
          删除
        </el-button>
        <el-button
          @click="handleRestore(scope.row)"
          type="primary"
          link
          :icon="Refresh"
          v-if="scope.row.deleted_at"
        >
          恢复
        </el-button>
        <el-button
          @click="handleSelectForMerge(scope.row)"
          type="primary"
          link
          :icon="Check"
          :disabled="scope.row.deleted_at || isSelected(scope.row.region_id)"
        >
          {{ isSelected(scope.row.region_id) ? '已选择' : '选择合并' }}
        </el-button>
      </template>
    </ProTable>

    <!-- 地区表单对话框：支持查看/编辑/新增 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="60%"
      destroy-on-close
    >
      <el-form
        ref="regionFormRef"
        :model="regionForm"
        :rules="formRules"
        label-width="120px"
        :disabled="mode === 'view'"
      >
        <!-- 父级地区选择 -->
        <el-form-item label="父级地区" prop="parent_id">
          <el-cascader
            v-model="regionForm.parent_id"
            :props="cascadeProps"
            placeholder="选择父级地区"
            clearable
            filterable
            :disabled="mode === 'view'"
            :load="loadCascadeChildren"
            lazy
          />
          <div class="text-gray-500 text-xs mt-1">
            顶级地区（省份）请留空或选择空值
          </div>
        </el-form-item>

        <!-- 地区名称 -->
        <el-form-item label="地区名称" prop="name">
          <el-input
            v-model="regionForm.name"
            placeholder="地区名称"
            :disabled="mode === 'view'"
          />
        </el-form-item>

        <!-- 地区类型 -->
        <el-form-item label="地区类型" prop="type">
          <el-select
            v-model="regionForm.type"
            placeholder="选择地区类型"
            :disabled="mode === 'view'"
          >
            <el-option label="省份" value="province" />
            <el-option label="城市" value="city" />
            <el-option label="区县" value="district" />
            <el-option label="街道" value="street" />
            <el-option label="乡镇" value="town" />
          </el-select>
        </el-form-item>

        <!-- 地区编码 -->
        <el-form-item label="地区编码" prop="code">
          <el-input
            v-model="regionForm.code"
            placeholder="地区编码"
            :disabled="mode === 'view'"
          />
          <div class="text-gray-500 text-xs mt-1">
            行政区域代码，如：110102001000
          </div>
        </el-form-item>

        <!-- 排序号 -->
        <el-form-item label="排序号" prop="snum">
          <el-input-number
            v-model="regionForm.snum"
            :min="0"
            :max="9999"
            placeholder="数字越小越靠前"
            :disabled="mode === 'view'"
          />
        </el-form-item>

        <!-- 路径信息 -->
        <el-form-item label="路径信息" v-if="mode === 'view'">
          <el-input
            v-model="regionForm.path"
            readonly
            class="readonly-input"
          />
        </el-form-item>

        <!-- 层级信息 -->
        <el-form-item label="层级信息" v-if="mode === 'view'">
          <el-input
            v-model="regionLevel"
            readonly
            class="readonly-input"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">关闭</el-button>
        <el-button
          type="primary"
          @click="submitRegionForm"
          v-if="mode !== 'view'"
        >
          {{ mode === 'edit' ? '更新' : '创建' }}
        </el-button>
      </template>
    </el-dialog>

    <!-- 合并地区对话框 -->
    <el-dialog
      v-model="mergeDialogVisible"
      title="合并地区"
      width="50%"
      destroy-on-close
    >
      <el-form
        ref="mergeFormRef"
        :model="mergeForm"
        :rules="mergeFormRules"
        label-width="120px"
      >
        <el-form-item label="目标地区" prop="target_region_id">
          <el-select
            v-model="mergeForm.target_region_id"
            placeholder="选择合并到的目标地区"
            filterable
            clearable
            style="width: 100%"
          >
            <el-option
              v-for="region in filteredRegionOptions"
              :key="region.region_id"
              :label="region.name"
              :value="region.region_id"
              :disabled="region.deleted_at || isSelected(region.region_id)"
            />
          </el-select>
          <div class="text-gray-500 text-xs mt-1">
            被合并的地区将成为该地区的子地区
          </div>
        </el-form-item>

        <el-form-item label="已选择地区" prop="source_region_ids">
          <el-tag
            v-for="id in selectedRegionIds"
            :key="id"
            closable
            :disable-transitions="false"
            @close="removeFromMerge(id)"
            style="margin-right: 5px; margin-bottom: 5px"
          >
            {{ getRegionNameById(id) }}
          </el-tag>
          <div v-if="selectedRegionIds.length === 0" class="text-gray-500 text-xs">
            请从列表中选择要合并的地区
          </div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="mergeDialogVisible = false">取消</el-button>
        <el-button
          type="primary"
          @click="submitMergeForm"
          :disabled="selectedRegionIds.length === 0 || !mergeForm.target_region_id"
        >
          确认合并
        </el-button>
      </template>
    </el-dialog>

    <!-- 拆分地区对话框 -->
    <el-dialog
      v-model="splitDialogVisible"
      title="拆分地区"
      width="60%"
      destroy-on-close
    >
      <el-form
        ref="splitFormRef"
        :model="splitForm"
        :rules="splitFormRules"
        label-width="120px"
      >
        <el-form-item label="父级地区" prop="parent_region_id">
          <el-input
            v-model="splitParentRegionName"
            readonly
            class="readonly-input"
          />
        </el-form-item>

        <el-form-item label="新增子地区" prop="new_regions">
          <el-table
            :data="splitForm.new_regions"
            border
            row-key="key"
            style="width: 100%; margin-bottom: 10px"
          >
            <el-table-column prop="name" label="地区名称" width="200">
              <template #default="scope">
                <el-input v-model="scope.row.name" placeholder="请输入地区名称" />
              </template>
            </el-table-column>
            <el-table-column prop="type" label="地区类型" width="150">
              <template #default="scope">
                <el-select v-model="scope.row.type" placeholder="选择类型">
                  <el-option label="省份" value="province" />
                  <el-option label="城市" value="city" />
                  <el-option label="区县" value="district" />
                  <el-option label="街道" value="street" />
                  <el-option label="乡镇" value="town" />
                </el-select>
              </template>
            </el-table-column>
            <el-table-column prop="code" label="地区编码" width="200">
              <template #default="scope">
                <el-input v-model="scope.row.code" placeholder="请输入编码" />
              </template>
            </el-table-column>
            <el-table-column prop="snum" label="排序号" width="100">
              <template #default="scope">
                <el-input-number
                  v-model="scope.row.snum"
                  :min="0"
                  :max="9999"
                  size="small"
                />
              </template>
            </el-table-column>
            <el-table-column label="操作" width="100">
              <template #default="scope">
                <el-button
                  type="primary"
                  link
                  size="small"
                  :icon="Delete"
                  @click="removeSplitRegion(scope.$index)"
                >
                  删除
                </el-button>
              </template>
            </el-table-column>
          </el-table>

          <el-button
            type="primary"
            size="small"
            :icon="CirclePlus"
            @click="addSplitRegion"
          >
            添加子地区
          </el-button>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="splitDialogVisible = false">取消</el-button>
        <el-button
          type="primary"
          @click="submitSplitForm"
          :disabled="splitForm.new_regions.length === 0"
        >
          确认拆分
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="regionManage">
import { onMounted, ref, reactive, computed, watch } from "vue";
import { ColumnProps } from "@/components/ProTable/interface";
import {
  Delete,
  EditPen,
  CirclePlus,
  Search,
  View,
  Refresh,
  Check
} from "@element-plus/icons-vue";
import {
  getTreeApi,
  getReadApi,
  postCreateApi,
  putUpdateApi,
  deleteDeleteApi,
  postRestoreApi,
  postMergeApi,
  postSplitApi
} from "@/api/modules/region";
import type { FormInstance, FormRules } from "element-plus";
import ProTable from "@/components/ProTable/index.vue";
import { ElMessage, ElMessageBox } from "element-plus";

// 类型定义
interface RegionItem {
  region_id: number;
  parent_id: number;
  name: string;
  type: 'province' | 'city' | 'district' | 'street' | 'town';
  code: string;
  snum: number;
  level: number;
  path: string;
  deleted_at: string | null;
  children?: RegionItem[];
  hasChildren?: boolean;
  updated_at: string;
  created_at: string;
}

interface RegionForm {
  region_id?: number;
  parent_id: number;
  type: 'province' | 'city' | 'district' | 'street' | 'town';
  name: string;
  code: string;
  snum: number;
  path?: string;
}

interface MergeParams {
  target_region_id: number;
  source_region_ids: number[];
}

interface SplitParams {
  parent_region_id: number;
  new_regions: Array<{
    key: number;
    parent_id: number;
    type: string;
    name: string;
    code: string;
    snum: number;
  }>;
}

// 表单引用
const regionFormRef = ref<FormInstance>();
const mergeFormRef = ref<FormInstance>();
const splitFormRef = ref<FormInstance>();
const proTable = ref<InstanceType<typeof ProTable>>();

// 对话框状态和模式控制
const dialogVisible = ref(false);
const mergeDialogVisible = ref(false);
const splitDialogVisible = ref(false);
const mode = ref<'view' | 'edit' | 'create'>('create');

// 原始地区数据
const originalRegionData = ref<RegionItem[]>([]);
// 筛选后的地区数据
const filteredRegionData = ref<RegionItem[]>([]);
// 已加载节点ID（避免重复请求）
const loadedNodeIds = ref<Set<number>>(new Set());
// 选中的地区ID（用于合并操作）
const selectedRegionIds = ref<number[]>([]);
// 当前选中的单个地区
const selectedRegion = ref<RegionItem | null>(null);

// 搜索参数
const searchParams = reactive({
  name: "",
  code: "",
  type: ""
});

// 地区表单数据
const regionForm = ref<RegionForm>({
  parent_id: 0,
  type: 'province',
  name: "",
  code: "",
  snum: 0
});

// 合并表单数据
const mergeForm = ref<MergeParams>({
  target_region_id: 0,
  source_region_ids: []
});

// 拆分表单数据
const splitForm = ref<SplitParams>({
  parent_region_id: 0,
  new_regions: []
});

// 拆分父地区名称
const splitParentRegionName = ref("");

// 级联选择器配置
const cascadeProps = {
  value: 'region_id',
  label: 'name',
  children: 'children',
  hasChildren: 'hasChildren',
  checkStrictly: true,
  emitPath: false
};

// 表单验证规则
const formRules = reactive<FormRules>({
  name: [{ required: true, message: "地区名称不能为空", trigger: "blur" }],
  type: [{ required: true, message: "请选择地区类型", trigger: "change" }],
  code: [{ required: true, message: "地区编码不能为空", trigger: "blur" }],
  snum: [{ required: true, message: "排序号不能为空", trigger: "blur" }],
  parent_id: [{ required: false, message: "请选择父级地区", trigger: "change" }]
});

// 合并表单验证规则
const mergeFormRules = reactive<FormRules>({
  target_region_id: [
    { required: true, message: "请选择目标地区", trigger: "change" }
  ],
  source_region_ids: [
    {
      required: true,
      message: "请选择至少一个要合并的地区",
      trigger: "change",
      validator: (rule, value, callback) => {
        if (selectedRegionIds.value.length === 0) {
          callback(new Error("请选择至少一个要合并的地区"));
        } else {
          callback();
        }
      }
    }
  ]
});

// 拆分表单验证规则
const splitFormRules = reactive<FormRules>({
  parent_region_id: [
    { required: true, message: "父级地区不能为空", trigger: "change" }
  ],
  new_regions: [
    {
      required: true,
      message: "请添加至少一个子地区",
      trigger: "change",
      validator: (rule, value, callback) => {
        if (splitForm.value.new_regions.length === 0) {
          callback(new Error("请添加至少一个子地区"));
        } else {
          const hasError = splitForm.value.new_regions.some(region => {
            return !region.name || !region.type || !region.code;
          });
          if (hasError) {
            callback(new Error("请完善所有子地区的信息"));
          } else {
            callback();
          }
        }
      }
    }
  ]
});

// 计算属性：是否可以合并
const canMerge = computed(() => {
  return selectedRegionIds.value.length >= 2;
});

// 计算属性：地区层级信息
const regionLevel = computed(() => {
  if (!regionForm.value.region_id) return "";
  const region = findRegionById(regionForm.value.region_id);
  return region ? `${region.level}级 (${region.path})` : "";
});

// 对话框标题计算属性
const dialogTitle = computed(() => {
  switch (mode.value) {
    case 'view': return '地区详情';
    case 'edit': return '编辑地区';
    case 'create': return '新增地区';
  }
});

// 筛选的地区选项（用于合并选择）
const filteredRegionOptions = computed(() => {
  const flattenRegions = flattenTree(originalRegionData.value);
  return flattenRegions.filter(region => !region.deleted_at);
});

// 表格列配置
const columns: ColumnProps[] = [
  { prop: "name", label: "地区名称", align: "left", width: 200 },
  { prop: "type", label: "地区类型", width: 120 },
  { prop: "code", label: "地区编码" },
  { prop: "level", label: "层级", width: 80 },
  { prop: "status", label: "状态", width: 100 },
  { prop: "operation", label: "操作", width: 400, fixed: "right" }
];

// 获取地区类型名称
const getTypeName = (type: string) => {
  const typeMap: Record<string, string> = {
    'province': '省份',
    'city': '城市',
    'district': '区县',
    'street': '街道',
    'town': '乡镇'
  };
  return typeMap[type] || type;
};

// 获取地区类型标签样式
const getTypeTagType = (type: string) => {
  const typeMap: Record<string, string> = {
    'province': 'primary',
    'city': 'success',
    'district': 'warning',
    'street': 'info',
    'town': 'info'
  };
  return typeMap[type] || "info";
};

// 查找地区通过ID
const findRegionById = (regionId: number): RegionItem | undefined => {
  const flattenRegions = flattenTree(originalRegionData.value);
  return flattenRegions.find(region => region.region_id === regionId);
};

// 获取地区名称通过ID
const getRegionNameById = (regionId: number): string => {
  const region = findRegionById(regionId);
  return region ? region.name : `未知地区(${regionId})`;
};

// 检查地区是否已选择
const isSelected = (regionId: number): boolean => {
  return selectedRegionIds.value.includes(regionId);
};

// 辅助函数：平铺树结构
const flattenTree = (nodes: RegionItem[], result: RegionItem[] = []) => {
  for (const node of nodes) {
    result.push(node);
    if (node.children && node.children.length) {
      flattenTree(node.children, result);
    }
  }
  return result;
};

// 节点展开时加载子节点（核心修正：替代load属性）
const handleNodeExpand = async (data: RegionItem) => {
  const regionId = data.region_id;

  // 避免重复加载
  if (loadedNodeIds.value.has(regionId)) return;

  try {
    // 请求子节点数据
    const res = await getTreeApi({ parent_id: regionId });
    const children = res.data || [];

    // 标记子节点是否有下一级
    children.forEach((child: any) => {
      child.hasChildren = !!child.hasChildren;
    });

    // 手动更新当前节点的children属性（触发视图更新）
    data.children = children;
    loadedNodeIds.value.add(regionId);

    // 强制刷新表格确保视图更新
    if (proTable.value?.refreshTable) {
      proTable.value.refreshTable();
    }
  } catch (error) {
    console.error('加载子节点失败:', error);
    ElMessage.error('加载子节点失败');
  }
};

// 级联选择器加载子节点
const loadCascadeChildren = async (node: any, resolve: any) => {
  try {
    const parentId = node.value === undefined ? 0 : node.value;
    const res = await getTreeApi({ parent_id: parentId });
    const children = res.data || [];

    children.forEach((child: any) => {
      child.hasChildren = child.hasChildren || false;
    });

    resolve(children);
  } catch (error) {
    console.error('级联选择器加载失败:', error);
    ElMessage.error('加载地区列表失败');
    resolve([]);
  }
};

// 搜索处理
const handleSearch = async () => {
  try {
    const res = await getTreeApi({ parent_id: 0 });
    originalRegionData.value = res.data || [];

    const allNodes = flattenTree(originalRegionData.value);
    const matchedNodes = allNodes.filter(node => {
      const matchName = searchParams.name ? (node.name || "").includes(searchParams.name) : true;
      const matchCode = searchParams.code ? (node.code || "").includes(searchParams.code) : true;
      const matchType = searchParams.type ? node.type === searchParams.type : true;
      return matchName && matchCode && matchType;
    });

    const matchedIds = new Set(matchedNodes.map(node => node.region_id));
    const buildFilteredTree = (nodes: RegionItem[]): RegionItem[] => {
      return nodes.reduce((result, node) => {
        const hasMatch = matchedIds.has(node.region_id) ||
          (node.children && node.children.some(child => matchedIds.has(child.region_id)));

        if (hasMatch) {
          const children = node.children ? buildFilteredTree(node.children) : [];
          if (children.length > 0) {
            result.push({ ...node, children });
          } else if (matchedIds.has(node.region_id)) {
            result.push({ ...node, children: [] });
          }
        }
        return result;
      }, [] as RegionItem[]);
    };

    filteredRegionData.value = buildFilteredTree(originalRegionData.value);
  } catch (error) {
    console.error('搜索失败:', error);
    ElMessage.error('搜索失败');
  }
};

// 重置搜索
const resetSearch = async () => {
  searchParams.name = "";
  searchParams.code = "";
  searchParams.type = "";
  await fetchRootRegionData();
};

// 获取根地区数据（顶级节点）
const fetchRootRegionData = async () => {
  try {
    const res = await getTreeApi({ parent_id: 0 });
    originalRegionData.value = res.data || [];
    filteredRegionData.value = [...originalRegionData.value];
    // 初始化根节点的hasChildren状态
    filteredRegionData.value.forEach(region => {
      region.hasChildren = !!region.hasChildren;
    });
    // 重置已加载节点缓存
    loadedNodeIds.value.clear();
  } catch (err) {
    console.error("获取地区数据失败", err);
    ElMessage.error("地区数据加载失败");
  }
};

// 加载地区详情
const loadRegionDetail = async (regionId: number) => {
  try {
    const res = await getReadApi(regionId);
    regionForm.value = {
      region_id: res.data.region_id,
      parent_id: res.data.parent_id || 0,
      type: res.data.type as any,
      name: res.data.name,
      code: res.data.code,
      snum: res.data.snum || 0
    };
    (regionForm.value as any).path = res.data.path;
    return res.data;
  } catch (error) {
    console.error("获取地区详情失败", error);
    ElMessage.error("加载地区详情失败");
    throw error;
  }
};

// 新增地区
const handleAdd = () => {
  mode.value = 'create';
  regionForm.value = {
    parent_id: 0,
    type: 'province',
    name: "",
    code: "",
    snum: 0
  };
  dialogVisible.value = true;
};

// 查看地区
const handleView = async (row: RegionItem) => {
  mode.value = 'view';
  await loadRegionDetail(row.region_id);
  dialogVisible.value = true;
};

// 编辑地区
const handleEdit = async (row: RegionItem) => {
  mode.value = 'edit';
  await loadRegionDetail(row.region_id);
  dialogVisible.value = true;
};

// 删除地区
const handleDelete = async (row: RegionItem) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除地区 "${row.name}" 吗? 其子地区也将被删除。`,
      "提示",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }
    );

    await deleteDeleteApi(row.region_id);
    ElMessage.success("删除成功");

    // 重新加载父节点数据
    const parentId = row.parent_id || 0;
    loadedNodeIds.value.delete(parentId);
    await fetchRootRegionData();
  } catch (error) {
    // 取消操作不处理
  }
};

// 恢复地区
const handleRestore = async (row: RegionItem) => {
  try {
    await ElMessageBox.confirm(
      `确定要恢复地区 "${row.name}" 吗?`,
      "提示",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "info"
      }
    );

    await postRestoreApi(row.region_id);
    ElMessage.success("恢复成功");

    // 重新加载父节点数据
    const parentId = row.parent_id || 0;
    loadedNodeIds.value.delete(parentId);
    await fetchRootRegionData();
  } catch (error) {
    // 取消操作不处理
  }
};

// 选择用于合并的地区
const handleSelectForMerge = (row: RegionItem) => {
  if (isSelected(row.region_id)) {
    selectedRegionIds.value = selectedRegionIds.value.filter(id => id !== row.region_id);
  } else {
    selectedRegionIds.value.push(row.region_id);
  }
};

// 从合并列表中移除
const removeFromMerge = (regionId: number) => {
  selectedRegionIds.value = selectedRegionIds.value.filter(id => id !== regionId);
};

// 打开合并对话框
const handleMerge = () => {
  mergeForm.value = {
    target_region_id: 0,
    source_region_ids: [...selectedRegionIds.value]
  };
  mergeDialogVisible.value = true;
};

// 打开拆分对话框
const handleSplit = () => {
  if (!selectedRegion.value) return;

  splitForm.value = {
    parent_region_id: selectedRegion.value.region_id,
    new_regions: []
  };
  splitParentRegionName.value = selectedRegion.value.name;
  addSplitRegion();
  splitDialogVisible.value = true;
};

// 添加拆分地区
const addSplitRegion = () => {
  splitForm.value.new_regions.push({
    key: Date.now(),
    parent_id: splitForm.value.parent_region_id,
    type: getDefaultSplitType(),
    name: "",
    code: "",
    snum: 0
  });
};

// 获取默认拆分类型
const getDefaultSplitType = () => {
  if (!selectedRegion.value) return 'district';

  const typeMap: Record<string, string> = {
    'province': 'city',
    'city': 'district',
    'district': 'street',
    'street': 'town',
    'town': 'town'
  };

  return typeMap[selectedRegion.value.type] || 'district';
};

// 移除拆分地区
const removeSplitRegion = (index: number) => {
  splitForm.value.new_regions.splice(index, 1);
};

// 提交地区表单
const submitRegionForm = async () => {
  if (!regionFormRef.value) return;

  regionFormRef.value.validate(async (valid) => {
    if (valid) {
      try {
        const payload = {
          parent_id: regionForm.value.parent_id,
          type: regionForm.value.type,
          name: regionForm.value.name,
          code: regionForm.value.code,
          snum: regionForm.value.snum
        };

        if (mode.value === 'edit' && regionForm.value.region_id) {
          await putUpdateApi(regionForm.value.region_id, payload);
          ElMessage.success("地区更新成功");
        } else {
          await postCreateApi(payload);
          ElMessage.success("地区创建成功");
        }

        dialogVisible.value = false;
        if (payload.parent_id) {
          loadedNodeIds.value.delete(payload.parent_id);
        } else {
          await fetchRootRegionData();
        }
      } catch (error) {
        console.error("提交地区失败", error);
        ElMessage.error("操作失败");
      }
    }
  });
};

// 提交合并表单
const submitMergeForm = async () => {
  if (!mergeFormRef.value) return;

  mergeFormRef.value.validate(async (valid) => {
    if (valid) {
      try {
        mergeForm.value.source_region_ids = selectedRegionIds.value;
        await postMergeApi(mergeForm.value);
        ElMessage.success("地区合并成功");

        mergeDialogVisible.value = false;
        selectedRegionIds.value = [];
        const targetRegion = findRegionById(mergeForm.value.target_region_id);
        if (targetRegion) {
          loadedNodeIds.value.delete(targetRegion.parent_id || 0);
        }
        await fetchRootRegionData();
      } catch (error) {
        console.error("合并地区失败", error);
        ElMessage.error("合并失败");
      }
    }
  });
};

// 提交拆分表单
const submitSplitForm = async () => {
  if (!splitFormRef.value) return;

  splitFormRef.value.validate(async (valid) => {
    if (valid) {
      try {
        await postSplitApi(splitForm.value);
        ElMessage.success("地区拆分成功");

        splitDialogVisible.value = false;
        loadedNodeIds.value.delete(splitForm.value.parent_region_id);
        await fetchRootRegionData();
      } catch (error) {
        console.error("拆分地区失败", error);
        ElMessage.error("拆分失败");
      }
    }
  });
};

// 处理表格选择事件
const handleTableSelect = (selection: RegionItem[], row: RegionItem) => {
  selectedRegion.value = selection.length ? selection[0] : null;
};

// 处理表格全选事件
const handleTableSelectAll = (selection: RegionItem[]) => {
  selectedRegion.value = selection.length ? selection[0] : null;
};

// 监听数据变化，清理无效选中
watch(() => filteredRegionData.value, () => {
  selectedRegionIds.value = selectedRegionIds.value.filter(id =>
    findRegionById(id) !== undefined
  );
});

// 初始化根节点数据
onMounted(async () => {
  await fetchRootRegionData();
});
</script>

<style scoped>
.search-box {
  padding: 15px 0;
}

.readonly-input .el-input__inner {
  background-color: #f5f7fa;
  cursor: not-allowed;
}

:deep(.el-form--disabled .el-form-item__label) {
  color: #606266;
  font-weight: 500;
}

:deep(.el-form--disabled .el-input__inner) {
  background-color: #f9fafb;
  color: #303133;
}

:deep(.el-tag) {
  margin-right: 4px;
}
</style>
