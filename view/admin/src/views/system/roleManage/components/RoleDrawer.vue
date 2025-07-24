<template>
  <el-drawer
    v-model="visible"
    :title="title"
    :close-on-click-modal="false"
    @close="handleClose"
  >
    <!-- 加载状态显示 -->
    <div v-if="loading" class="loading-container">
      <el-loading loading="true" />
    </div>

    <el-form
      v-else
      ref="formRef"
      :model="form"
      :rules="rules"
      label-width="100px"
      class="mt-4"
    >
      <!-- 通用字段（新增/编辑/查看都显示） -->
      <el-form-item label="角色名称" prop="name">
        <el-input
          v-model="form.name"
          placeholder="请输入角色名称"
          :disabled="isView"
        />
      </el-form-item>

      <el-form-item label="角色描述" prop="description">
        <el-input
          v-model="form.description"
          placeholder="请输入角色描述"
          type="textarea"
          :rows="4"
          :disabled="isView"
        />
      </el-form-item>

      <!-- 管理员关联部分 -->
      <el-form-item label="关联管理员">
        <!-- 查看模式 -->
        <template v-if="isView">
          <div v-if="form.admins && form.admins.length">
            <el-tag
              v-for="admin in form.admins"
              :key="admin.admin_id"
              class="mr-2 mb-2"
            >
              {{ admin.real_name }}({{ admin.username }})
            </el-tag>
          </div>
          <div v-else class="text-gray-500">无关联管理员</div>
        </template>

        <!-- 编辑/新增模式 -->
        <template v-if="!isView">
          <!-- 已关联管理员表格 -->
          <div class="mb-4">
            <el-table
              :data="form.admins"
              border
              style="width: 100%; margin-bottom: 10px;"
              max-height="200"
            >
              <el-table-column prop="real_name" label="姓名" width="120" />
              <el-table-column prop="username" label="用户名" width="150" />
              <el-table-column label="操作" width="80">
                <template #default="scope">
                  <el-button
                    type="text"
                    size="small"
                    text-color="#ff4d4f"
                    @click="removeAdmin(scope.row.admin_id)"
                  >
                    移除
                  </el-button>
                </template>
              </el-table-column>
            </el-table>

            <!-- 添加管理员按钮 -->
            <el-button
              type="primary"
              size="small"
              @click="showAdminSelector = true"
              icon="CirclePlus"
            >
              添加管理员
            </el-button>
          </div>
        </template>
      </el-form-item>

      <!-- 菜单权限树 -->
      <el-form-item label="菜单权限" class="menu-tree-item">
        <el-tree
          :data="form.menu_tree_with_permission"
          :props="treeProps"
          :expand-on-click-node="false"
          :default-expand-all="true"
          node-key="id"
          :disabled="!isView"
        >
          <template #default="{ node, data }">
            <span class="flex items-center">
              <span>{{ node.label }}</span>
              <span v-if="data.permissions && data.permissions.length" class="ml-2 text-xs text-blue-500">
                ({{ data.permissions.join(', ') }})
              </span>
            </span>
          </template>
        </el-tree>
      </el-form-item>

      <template v-if="isView">
        <el-form-item label="创建时间">
          <el-input v-model="form.created_at" disabled />
        </el-form-item>
        <el-form-item label="更新时间">
          <el-input v-model="form.updated_at" disabled />
        </el-form-item>
        <el-form-item>
          <el-button @click="handleClose">关闭</el-button>
        </el-form-item>
      </template>

      <!-- 编辑/新增模式按钮 -->
      <template v-if="!isView">
        <el-form-item>
          <el-button type="primary" @click="handleSubmit">提交</el-button>
          <el-button @click="handleClose">取消</el-button>
        </el-form-item>
      </template>
    </el-form>

    <!-- 管理员选择弹窗 -->
    <el-dialog
      v-model="showAdminSelector"
      title="选择管理员"
      width="600px"
      @close="handleAdminSelectorClose"
    >
      <el-input
        v-model="adminSearchKeyword"
        placeholder="本地搜索用户名或姓名"
        class="mb-4"
        clearable
        @input="handleAdminLocalSearch"
      />

      <el-table
        :data="filteredAdmins"
        border
        style="width: 100%;"
        max-height="400"
        @selection-change="handleAdminSelectionChange"
      >
        <el-table-column type="selection" width="55" />
        <el-table-column prop="admin_id" label="ID" width="80" />
        <el-table-column prop="real_name" label="姓名" width="120" />
        <el-table-column prop="username" label="用户名" width="150" />
        <el-table-column prop="status" label="状态" width="80">
          <template #default="scope">
            <el-switch
              v-model="scope.row.status"
              active-value="1"
              inactive-value="0"
              disabled
            />
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页控件 - 支持自定义每页数据量 -->
      <div class="pagination-container">
        <el-select
          v-model="adminPageSize"
          size="small"
          class="page-size-select"
          @change="handlePageSizeChange"
        >
          <el-option label="10条/页" value="10" />
          <el-option label="20条/页" value="20" />
          <el-option label="50条/页" value="50" />
          <el-option label="100条/页" value="100" />
        </el-select>

        <el-pagination
          v-model:current-page="adminPage"
          :page-size="adminPageSize"
          :total="adminTotal"
          class="pagination"
          @current-change="handleAdminPageChange"
        />
      </div>

      <template #footer>
        <el-button @click="showAdminSelector = false">取消</el-button>
        <el-button type="primary" @click="confirmAddAdmins">确认添加</el-button>
      </template>
    </el-dialog>
  </el-drawer>
</template>

<script setup lang="ts" name="RoleDrawer">
import { ref, reactive, watch, computed } from "vue";
import { ElMessage, ElLoading } from "element-plus";
import { useHandleData } from "@/hooks/useHandleData";
import { Role } from "@/api/modules/role";
import { getListApi as getAdminListApi } from "@/api/modules/account";

// 抽屉是否可见
const visible = ref(false);
// 标题
const title = ref("");
// 是否为查看模式
const isView = ref(false);
// 加载状态
const loading = ref(false);
// 树形结构配置
const treeProps = {
  label: 'name',
  children: 'children'
};

// 表单数据 - 匹配后端返回结构
const form = reactive<Partial<Role.RoleOptions & {
  menu_tree_with_permission: Array<{
    id: number;
    name: string;
    children?: any[];
    permissions?: string[];
  }>;
  admins: Array<{
    admin_id: number;
    real_name: string;
    username: string;
  }>;
  admin_roles: Array<{
    admin_id: number;
    role_id: number;
    created_at: string;
  }>;
  role_permissions?: Array<{
    role_id: number;
    permission_id: number;
    created_at: string;
    menu_id: number;
  }>;
  role_menus?: Array<{
    role_id: number;
    menu_id: number;
  }>;
}>>({
  role_id: undefined,
  name: "",
  description: "",
  created_at: "",
  updated_at: "",
  menu_tree_with_permission: [],
  admins: [],
  admin_roles: []
});

// 管理员选择器相关
const showAdminSelector = ref(false);
const adminPage = ref(1);
const adminPageSize = ref(15); // 页面显示的每页条数
const allAdmins = ref<any[]>([]);
const adminTotal = ref(0); // 管理员总条数（来自接口）

// 计算属性：根据分页和搜索条件过滤管理员列表
const filteredAdmins = computed<any[]>(() => {
  let result = [...allAdmins.value];

  if (adminSearchKeyword.value) {
    const keyword = adminSearchKeyword.value.toLowerCase();
    result = result.filter(admin =>
      admin.username.toLowerCase().includes(keyword) ||
      admin.real_name.toLowerCase().includes(keyword)
    );
  }

  const startIndex = (adminPage.value - 1) * adminPageSize.value;
  return result.slice(startIndex, startIndex + adminPageSize.value);
});

const adminSearchKeyword = ref("");
const selectedAdmins = ref<any[]>([]);

// 表单验证规则
const rules = reactive({
  name: [
    { required: true, message: "请输入角色名称", trigger: "blur" },
    { min: 1, max: 50, message: "角色名称长度在 1 到 50 个字符", trigger: "blur" }
  ],
  description: [
    { max: 200, message: "角色描述不能超过 200 个字符", trigger: "blur" }
  ]
});

// 表单引用
const formRef = ref<any>(null);
let getTableList: () => void = () => {};
let api: any = null;
let detailApi: any = null;

// 接收参数
const acceptParams = async (params: {
  title: string;
  isView: boolean;
  row: Partial<Role.RoleOptions>;
  api?: any;
  detailApi?: any;
  getTableList: () => void;
}) => {
  title.value = params.title;
  isView.value = params.isView;
  api = params.api;
  detailApi = params.detailApi;
  getTableList = params.getTableList;

  Object.assign(form, {
    role_id: undefined,
    name: "",
    description: "",
    created_at: "",
    updated_at: "",
    menu_tree_with_permission: [],
    admins: [],
    admin_roles: []
  });

  visible.value = true;

  if (params.title === "编辑" && params.row?.role_id && detailApi) {
    try {
      loading.value = true;
      const response = await detailApi(params.row.role_id);

      if (response?.code === 200 && response.data) {
        Object.assign(form, response.data);
      } else {
        ElMessage.error(response?.msg || "获取角色详情失败");
      }
    } catch (error) {
      console.error("获取角色详情出错:", error);
      ElMessage.error("获取角色详情时发生错误");
    } finally {
      loading.value = false;
    }
  } else if (params.row && params.row.role_id) {
    Object.assign(form, params.row);
  }
};

// 加载管理员数据（已与分页参数绑定）
const loadAllAdmins = async () => {
  try {
    // 使用页面的分页参数作为请求参数
    const params = {
      page: adminPage.value,
      list_rows: adminPageSize.value, // 关键修复：请求条数与页面选择的条数绑定
      keyword: adminSearchKeyword.value
    };

    const response = await getAdminListApi(params);

    if (response?.code === 200 && response.data) {
      // 过滤已关联的管理员
      allAdmins.value = response.data.list.filter((admin: any) => {
        return !form.admins.some(item => item.admin_id === admin.admin_id);
      });
      adminTotal.value = response.data.total; // 同步总条数
    }
  } catch (error) {
    console.error("获取管理员列表失败:", error);
    ElMessage.error("获取管理员列表失败");
  }
};

// 本地搜索处理（重新请求数据）
const handleAdminLocalSearch = () => {
  adminPage.value = 1;
};

// 处理每页条数变化（重新请求数据）
const handlePageSizeChange = (size: number) => {
  adminPageSize.value = size;
  adminPage.value = 1; // 页数变化时重置页码
  loadAllAdmins(); // 重新请求数据
};

// 处理分页变化（重新请求数据）
const handleAdminPageChange = (page: number) => {
  adminPage.value = page;
  loadAllAdmins(); // 重新请求数据
};

// 处理选择变化
const handleAdminSelectionChange = (selection: any[]) => {
  selectedAdmins.value = selection;
};

// 确认添加管理员
const confirmAddAdmins = () => {
  if (selectedAdmins.value.length === 0) {
    ElMessage.warning("请选择要添加的管理员");
    return;
  }

  selectedAdmins.value.forEach(admin => {
    const exists = form.admins.some(item => item.admin_id === admin.admin_id);
    if (!exists) {
      form.admins.push({
        admin_id: admin.admin_id,
        real_name: admin.real_name,
        username: admin.username
      });
    }
  });

  loadAllAdmins(); // 刷新可选管理员列表
  showAdminSelector.value = false;
  selectedAdmins.value = [];
};

// 移除管理员
const removeAdmin = (adminId: number) => {
  form.admins = form.admins.filter(item => item.admin_id !== adminId);
  loadAllAdmins(); // 刷新可选管理员列表
};

// 关闭管理员选择器
const handleAdminSelectorClose = () => {
  selectedAdmins.value = [];
};

// 打开管理员选择器时加载数据
watch(showAdminSelector, (newVal) => {
  if (newVal) {
    // 重置分页状态
    adminPage.value = 1;
    adminSearchKeyword.value = '';
    loadAllAdmins();
  }
});

// 关闭抽屉
const handleClose = () => {
  visible.value = false;
  showAdminSelector.value = false;
  formRef.value?.resetFields();
};

// 提交表单
const handleSubmit = async () => {
  const valid = await formRef.value.validate();
  if (!valid) return;

  try {
    const submitData = {
      name: form.name,
      description: form.description,
      admin_roles: form.admins.map(admin => ({
        admin_id: admin.admin_id,
        role_id: form.role_id
      }))
    };

    if (title.value === "新增") {
      await useHandleData(api, submitData, "新增角色成功");
    } else if (title.value === "编辑") {
      if (!form.role_id) return;
      await useHandleData(api, {
        id: form.role_id,
        ...submitData
      }, "编辑角色成功");
    }

    handleClose();
    getTableList();
  } catch (error) {
    console.error("提交失败:", error);
  }
};

defineExpose({
  acceptParams
});
</script>

<style scoped>
.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 300px;
}

.menu-tree-item {
  .el-form-item__content {
    max-height: 300px;
    overflow-y: auto;
    padding-right: 10px;
  }
}

.pagination-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 16px;
}

.page-size-select {
  width: 120px;
}

.pagination {
  margin: 0;
}
</style>
