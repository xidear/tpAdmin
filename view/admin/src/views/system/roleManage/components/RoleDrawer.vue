<template>
  <el-drawer
    v-model="visible"
    :title="title"
    :close-on-click-modal="false"
    @close="handleClose"
  >
    <!-- 加载状态显示 -->
    <div v-if="loading" class="loading-container">
    </div>

    <el-form
      v-else
      ref="formRef"
      :model="form"
      :rules="rules"
      label-width="100px"
      class="mt-4"
    >
      <!-- 通用字段 -->
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
        <div class="mb-4">
          <el-table
            :data="form.admins"
            border
            style="width: 100%; margin-bottom: 10px;"
            max-height="200"
          >
            <el-table-column prop="admin_id" label="ID" width="80" />
            <el-table-column prop="real_name" label="姓名" width="120" />
            <el-table-column prop="username" label="用户名" width="150" />
            <!-- 操作列仅在非查看模式显示 -->
            <el-table-column 
              label="操作" 
              width="80"
              v-if="!isView"
            >
              <template #default="scope">
                <el-button
                  type="primary"
                  size="small"
                  text-color="#ff4d4f"
                  @click="removeAdmin(scope.row.admin_id)"
                >
                  移除
                </el-button>
              </template>
            </el-table-column>
          </el-table>

          <!-- 添加管理员按钮仅在非查看模式显示 -->
          <el-button
            v-if="!isView"
            type="primary"
            size="small"
            @click="showAdminSelector = true"
            icon="CirclePlus"
          >
            添加管理员
          </el-button>

          <!-- 无数据提示 -->
          <div v-if="form.admins.length === 0" class="text-gray-500 text-sm">
            暂无关联管理员
          </div>
        </div>
      </el-form-item>

      <!-- 菜单权限树 -->
      <el-form-item label="菜单权限" class="menu-tree-item">
        <el-tree
          ref="menuTreeRef"
          :data="menuTreeData"
          :props="treeProps"
          :expand-on-click-node="false"
          :default-expand-all="true"
          node-key="menu_id"
          :show-checkbox="!isView"
          :check-strictly="true"
          :default-checked-keys="defaultCheckedMenuIds"
          @check="handleMenuCheck"
          @check-change="handleCheckChange"
          :disabled="isView"
          :key="treeUpdateKey"
        >
          <template #default="{ node, data }">
            <div class="menu-item-container">
              <span class="menu-title">{{ data.title }}</span>
              
              <!-- 查看模式下显示权限按钮 -->
              <el-button
                v-if="isView && hasPermissions(data)"
                type="info"
                size="small"
                class="permission-btn"
                @click.stop="openPermissionModal(data)"
              >
                显示权限
              </el-button>
              
              <!-- 编辑/新增模式下显示权限按钮（仅当菜单被勾选） -->
              <el-button
                v-if="!isView && hasPermissions(data) && isMenuChecked(data.menu_id)"
                type="primary"
                size="small"
                class="permission-btn"
                @click.stop="openPermissionModal(data)"
              >
                勾选权限
              </el-button>
            </div>
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

      <!-- 分页控件 -->
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

    <!-- 权限选择弹窗 -->
    <el-dialog
      v-model="showPermissionModal"
      :title="`${currentMenu?.title || ''} - 权限设置`"
      width="500px"
      @close="handlePermissionModalClose"
    >
      <!-- 全选按钮 -->
      <div class="mb-4" v-if="!isView && currentMenu?.permissions && currentMenu.permissions.length > 0">
        <el-button 
          type="primary" 
          size="small"
          @click="selectAllPermissions"
        >
          全选权限
        </el-button>
        <el-button 
          type="default" 
          size="small"
          class="ml-2"
          @click="deselectAllOptionalPermissions"
        >
          取消可选权限
        </el-button>
      </div>
      
      <el-checkbox-group
        v-model="checkedPermissions[currentMenu?.menu_id || 0]"
        :disabled="isView"
        class="permission-group"
      >
        <el-checkbox
          v-for="perm in currentMenu?.permissions || []"
          :key="perm.permission_id"
          :value="perm.permission_id"
          :disabled="isRequiredPermission(perm)"
          class="permission-item"
        >
          <div class="permission-info">
            <div class="permission-name">
              {{ perm.name }}
              <span v-if="isRequiredPermission(perm)" class="required-tag">必备</span>
            </div>
            <div class="permission-desc text-sm text-gray-500">{{ perm.description }}</div>
          </div>
        </el-checkbox>
      </el-checkbox-group>
      
      <div v-if="currentMenu?.permissions && currentMenu.permissions.length === 0" class="no-permission">
        该菜单暂无可用权限
      </div>

      <template #footer>
        <el-button @click="showPermissionModal = false" v-if="!isView">取消</el-button>
        <el-button 
          type="primary" 
          @click="showPermissionModal = false" 
          v-if="!isView"
        >
          确认
        </el-button>
        <el-button 
          type="primary" 
          @click="showPermissionModal = false" 
          v-if="isView"
        >
          关闭
        </el-button>
      </template>
    </el-dialog>
  </el-drawer>
</template>

<script setup lang="ts" name="RoleDrawer">
import { ref, reactive, watch, computed, nextTick } from "vue";
import { ElMessage } from "element-plus";
import { useHandleData } from "@/hooks/useHandleData";
import { getListApi as getAdminListApi } from "@/api/modules/account";
import { getTreeApi } from "@/api/modules/menu";

// 抽屉状态
const visible = ref(false);
const title = ref("");
const isView = ref(false);
const loading = ref(false);

// 树形结构配置
const treeProps = {
  label: 'title',
  children: 'children'
};

// 用于强制更新树组件的key
const treeUpdateKey = ref(0);

// 表单数据
const form = reactive<Partial<{
  role_id: number;
  name: string;
  description: string;
  created_at: string;
  updated_at: string;
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
  role_permissions: Array<{
    role_id: number;
    permission_id: number;
    created_at: string;
    menu_id: number;
  }>;
  role_menus: Array<{
    role_id: number;
    menu_id: number;
  }>;
}>>({
  role_id: undefined,
  name: "",
  description: "",
  created_at: "",
  updated_at: "",
  admins: [],
  admin_roles: [],
  role_permissions: [],
  role_menus: []
});

// 管理员选择器相关
const showAdminSelector = ref(false);
const adminPage = ref(1);
const adminPageSize = ref(15);
const allAdmins = ref<any[]>([]);
const adminTotal = ref(0);
const adminSearchKeyword = ref("");
const selectedAdmins = ref<any[]>([]);

// 计算属性：过滤管理员列表
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

// 菜单权限核心数据
const menuTreeData = ref<any[]>([]);
const defaultCheckedMenuIds = ref<number[]>([]);
const checkedPermissions = ref<Record<number, number[]>>({});
const menuTreeRef = ref<any>(null);

// 权限弹窗相关
const showPermissionModal = ref(false);
const currentMenu = ref<any>(null);

// 表单验证规则
const rules = reactive({
  name: [
    { required: true, message: "请输入角色名称", trigger: "blur" },
    { min: 1, max: 50, message: "角色名称长度在 1 到 50 个字符", trigger: "blur" }
  ],
  description: [
    { max: 200, message: "角色描述不能超过 200 个字符", trigger: "blur" }
  ]});

// 表单引用
const formRef = ref<any>(null);
let getTableList: () => void = () => {};
let api: any = null;
let detailApi: any = null;

// 接收参数
const acceptParams = async (params: {
  title: string;
  isView: boolean;
  row: Partial<any>;
  api?: any;
  detailApi?: any;
  getTableList: () => void;
}) => {
  title.value = params.title;
  isView.value = params.isView;
  api = params.api;
  detailApi = params.detailApi;
  getTableList = params.getTableList;

  // 重置表单数据
  Object.assign(form, {
    role_id: undefined,
    name: "",
    description: "",
    created_at: "",
    updated_at: "",
    admins: [],
    admin_roles: [],
    role_permissions: [],
    role_menus: []
  });

  // 重置菜单权限数据
  menuTreeData.value = [];
  defaultCheckedMenuIds.value = [];
  checkedPermissions.value = {};
  showPermissionModal.value = false;
  currentMenu.value = null;
  treeUpdateKey.value = 0;

  visible.value = true;

  // 加载角色数据（查看/编辑）
  if ((params.title === "查看" || params.title === "编辑") && params.row?.role_id && detailApi) {
    try {
      loading.value = true;
      const roleResponse = await detailApi(params.row.role_id);
      
      if (roleResponse?.code === 200 && roleResponse.data) {
        Object.assign(form, roleResponse.data);
        await loadMenuTree();
        setCheckedStatusFromRoleData();
      } else {
        ElMessage.error(roleResponse?.msg || "获取角色详情失败");
      }
    } catch (error) {
      console.error("获取角色详情出错:", error);
      ElMessage.error("获取角色详情时发生错误");
    } finally {
      loading.value = false;
    }
  } else if (params.row && params.row.role_id && !isView.value) {
    Object.assign(form, params.row);
  }
  
  // 新增模式加载菜单树
  if (params.title === "新增") {
    await loadMenuTree();
  }
};

// 加载菜单树数据
const loadMenuTree = async () => {
  try {
    loading.value = true;
    const response = await getTreeApi();
    
    if (response?.code === 200 && response.data) {
      menuTreeData.value = response.data;
      // 加载完成后更新key，确保树正确渲染
      treeUpdateKey.value++;
    }
  } catch (error) {
    console.error("获取菜单树失败:", error);
    ElMessage.error("获取菜单权限失败");
  } finally {
    loading.value = false;
  }
};

// 判断菜单是否有权限
const hasPermissions = (data: any) => {
  return data.permissions && data.permissions.length > 0;
};

// 判断菜单是否被勾选
const isMenuChecked = (menuId: number) => {
  // 从tree组件获取实时的勾选状态，确保准确性
  const checkedKeys = menuTreeRef.value?.getCheckedKeys() || [];
  return checkedKeys.includes(menuId);
};

// 判断是否为必备权限
const isRequiredPermission = (permission: any) => {
  return permission.pivot && permission.pivot.type === "REQUIRED";
};

// 获取菜单的所有必备权限
const getRequiredPermissions = (menuId: number) => {
  const menu = findMenuById(menuTreeData.value, menuId);
  if (!menu || !menu.permissions) return [];
  
  return menu.permissions
    .filter((perm: any) => isRequiredPermission(perm))
    .map((perm: any) => perm.permission_id);
};

// 根据ID查找菜单
const findMenuById = (menuList: any[], menuId: number): any => {
  for (const menu of menuList) {
    if (menu.menu_id === menuId) {
      return menu;
    }
    if (menu.children && menu.children.length > 0) {
      const found = findMenuById(menu.children, menuId);
      if (found) return found;
    }
  }
  return null;
};

// 递归获取所有子菜单ID
const getAllChildMenuIds = (menu: any): number[] => {
  let childIds: number[] = [];
  if (menu.children && menu.children.length > 0) {
    menu.children.forEach((child: any) => {
      childIds.push(child.menu_id);
      childIds = [...childIds, ...getAllChildMenuIds(child)];
    });
  }
  return childIds;
};

// 递归清理指定菜单及其子菜单的权限数据
const cleanMenuPermissions = (menuId: number) => {
  // 1. 清理当前菜单的权限
  if (checkedPermissions.value[menuId]) {
    delete checkedPermissions.value[menuId];
  }

  // 2. 查找当前菜单
  const menu = findMenuById(menuTreeData.value, menuId);
  if (!menu || !menu.children || menu.children.length === 0) {
    return;
  }

  // 3. 递归清理所有子菜单的权限
  menu.children.forEach((child: any) => {
    cleanMenuPermissions(child.menu_id);
  });
};

// 根据role_menus和role_permissions设置勾选状态
const setCheckedStatusFromRoleData = () => {
  // 设置选中的菜单
  if (form.role_menus?.length) {
    defaultCheckedMenuIds.value = form.role_menus.map(item => item.menu_id);
  }

  // 确保所有已选菜单（包括子菜单）都有必备权限
  const allCheckedIds = [...defaultCheckedMenuIds.value];
  allCheckedIds.forEach(menuId => {
    const menu = findMenuById(menuTreeData.value, menuId);
    if (menu) {
      const childIds = getAllChildMenuIds(menu);
      childIds.forEach(childId => {
        if (!allCheckedIds.includes(childId)) {
          allCheckedIds.push(childId);
        }
      });
    }
  });

  // 为所有已勾选的菜单（包括父级和子级）初始化必备权限
  allCheckedIds.forEach(menuId => {
    const requiredPerms = getRequiredPermissions(menuId);
    const savedPerms = form.role_permissions
      ?.filter(item => item.menu_id === menuId)
      .map(item => item.permission_id) || [];

    const mergedPerms = [...new Set([...requiredPerms, ...savedPerms])];
    checkedPermissions.value[menuId] = mergedPerms;
  });

  // 强制更新树组件
  treeUpdateKey.value++;
};


// 处理单个节点的勾选状态变化
const handleCheckChange = (data: any, checked: boolean, indeterminate: boolean) => {
  if (isView.value) return;

  if (checked) {
    // 勾选时添加当前菜单及子菜单的必备权限
    const requiredPerms = getRequiredPermissions(data.menu_id);
    if (!checkedPermissions.value[data.menu_id]) {
      checkedPermissions.value[data.menu_id] = [...requiredPerms];
    } else {
      requiredPerms.forEach(permId => {
        if (!checkedPermissions.value[data.menu_id].includes(permId)) {
          checkedPermissions.value[data.menu_id].push(permId);
        }
      });
    }

    // 处理子节点 - 父节点勾选时自动添加子节点必备权限
    if (data.children && data.children.length > 0) {
      data.children.forEach((child: any) => {
        const childRequiredPerms = getRequiredPermissions(child.menu_id);
        if (!checkedPermissions.value[child.menu_id]) {
          checkedPermissions.value[child.menu_id] = [...childRequiredPerms];
        } else {
          childRequiredPerms.forEach((permId: number) => {
            if (!checkedPermissions.value[child.menu_id].includes(permId)) {
              checkedPermissions.value[child.menu_id].push(permId);
            }
          });
        }
      });
    }
  } else {
    // 取消勾选时清理当前菜单及所有子菜单的权限
    cleanMenuPermissions(data.menu_id);
  }
};

// 创建菜单ID到菜单对象的映射，便于查找父节点
const createMenuMap = (menuList: any[]): Record<number, any> => {
  const map: Record<number, any> = {};

  const traverse = (menus: any[], parent: any = null) => {
    menus.forEach(menu => {
      map[menu.menu_id] = { ...menu, parent };
      if (menu.children && menu.children.length > 0) {
        traverse(menu.children, menu);
      }
    });
  };

  traverse(menuList);
  return map;
};

// 获取某个菜单的所有父级菜单ID
const getParentMenuIds = (menuMap: Record<number, any>, menuId: number): number[] => {
  const parentIds: number[] = [];
  let current = menuMap[menuId];

  // 向上遍历所有父节点
  while (current && current.parent) {
    parentIds.push(current.parent.menu_id);
    current = menuMap[current.parent.menu_id];
  }

  return parentIds;
};

// 处理菜单勾选事件（批量处理）
const handleMenuCheck = () => {
  if (isView.value) return;

  // 获取当前所有选中的菜单节点（包括父子级联动勾选的）
  const checkedNodes = menuTreeRef.value?.getCheckedNodes(false, false) || [];
  let checkedMenuIds = checkedNodes.map((node: any) => node.menu_id);

  // 创建菜单ID到菜单对象的映射，便于查找父节点
  const allNodesMap = createMenuMap(menuTreeData.value);

  // 临时存储所有需要勾选的节点ID（包括原勾选和父节点）
  const allNeedCheckIds = new Set<number>(checkedMenuIds);

  // 为每个已勾选的节点，添加其所有父节点
  checkedMenuIds.forEach(menuId => {
    const parentIds = getParentMenuIds(allNodesMap, menuId);
    parentIds.forEach(id => allNeedCheckIds.add(id));
  });

  // 更新勾选状态，包括所有必要的父节点
  checkedMenuIds = Array.from(allNeedCheckIds);
  menuTreeRef.value?.setCheckedKeys(checkedMenuIds);

  // 更新默认勾选的菜单ID
  defaultCheckedMenuIds.value = checkedMenuIds;

  // 获取所有应该被清理的菜单ID（当前未勾选的）
  const allMenuIds = getAllMenuIds(menuTreeData.value);
  const menusToClean = allMenuIds.filter(id => !checkedMenuIds.includes(id));

  // 清理未勾选菜单的权限数据
  menusToClean.forEach(menuId => {
    if (checkedPermissions.value[menuId]) {
      delete checkedPermissions.value[menuId];
    }
  });

  // 确保所有勾选的菜单都包含必备权限
  checkedMenuIds.forEach(menuId => {
    const requiredPerms = getRequiredPermissions(menuId);
    if (!checkedPermissions.value[menuId]) {
      checkedPermissions.value[menuId] = [...requiredPerms];
    } else {
      requiredPerms.forEach(permId => {
        if (!checkedPermissions.value[menuId].includes(permId)) {
          checkedPermissions.value[menuId].push(permId);
        }
      });
    }
  });

  // 强制更新树组件
  treeUpdateKey.value++;
};

// 获取所有菜单ID（用于批量清理）
const getAllMenuIds = (menuList: any[]): number[] => {
  let allIds: number[] = [];
  menuList.forEach(menu => {
    allIds.push(menu.menu_id);
    if (menu.children && menu.children.length > 0) {
      allIds = [...allIds, ...getAllMenuIds(menu.children)];
    }
  });
  return allIds;
};

// 打开权限弹窗
const openPermissionModal = (menuData: any) => {
  currentMenu.value = menuData;

  // 确保权限数据已初始化，并且必备权限已勾选
  const requiredPerms = getRequiredPermissions(menuData.menu_id);
  if (!checkedPermissions.value[menuData.menu_id]) {
    checkedPermissions.value[menuData.menu_id] = [...requiredPerms];
  } else {
    requiredPerms.forEach(permId => {
      if (!checkedPermissions.value[menuData.menu_id].includes(permId)) {
        checkedPermissions.value[menuData.menu_id].push(permId);
      }
    });
  }

  nextTick(() => {
    showPermissionModal.value = true;
  });
};

// 全选权限
const selectAllPermissions = () => {
  if (!currentMenu.value || !currentMenu.value.permissions) return;

  const allPermIds = currentMenu.value.permissions.map((perm: any) => perm.permission_id);
  checkedPermissions.value[currentMenu.value.menu_id] = [...new Set(allPermIds)];
};

// 取消所有可选权限（保留必备权限）
const deselectAllOptionalPermissions = () => {
  if (!currentMenu.value || !currentMenu.value.permissions) return;

  const requiredPerms = getRequiredPermissions(currentMenu.value.menu_id);
  checkedPermissions.value[currentMenu.value.menu_id] = [...requiredPerms];
};

// 关闭权限弹窗
const handlePermissionModalClose = () => {
  showPermissionModal.value = false;
  currentMenu.value = null;
};

// 加载管理员数据
const loadAllAdmins = async () => {
  try {
    const params = {
      page: adminPage.value,
      list_rows: adminPageSize.value,
      keyword: adminSearchKeyword.value,
      not_super: 1
    };

    const response = await getAdminListApi(params);

    if (response?.code === 200 && response.data) {
      // 过滤已关联的管理员
      allAdmins.value = response.data.list.filter((admin: any) => {
        return !form.admins.some(item => item.admin_id === admin.admin_id);
      });
      adminTotal.value = response.data.total;
    }
  } catch (error) {
    console.error("获取管理员列表失败:", error);
    ElMessage.error("获取管理员列表失败");
  }
};

// 管理员选择器相关方法
const handleAdminLocalSearch = () => {
  adminPage.value = 1;
  loadAllAdmins();
};

const handlePageSizeChange = (size: number) => {
  adminPageSize.value = size;
  adminPage.value = 1;
  loadAllAdmins();
};

const handleAdminPageChange = (page: number) => {
  adminPage.value = page;
  loadAllAdmins();
};

const handleAdminSelectionChange = (selection: any[]) => {
  selectedAdmins.value = selection;
};

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

  loadAllAdmins();
  showAdminSelector.value = false;
  selectedAdmins.value = [];
};

const removeAdmin = (adminId: number) => {
  form.admins = form.admins.filter(item => item.admin_id !== adminId);
  loadAllAdmins();
};

const handleAdminSelectorClose = () => {
  selectedAdmins.value = [];
};

watch(showAdminSelector, (newVal) => {
  if (newVal) {
    adminPage.value = 1;
    adminSearchKeyword.value = '';
    loadAllAdmins();
  }
});

// 关闭抽屉
const handleClose = () => {
  visible.value = false;
  showAdminSelector.value = false;
  showPermissionModal.value = false;
  formRef.value?.resetFields();
};

// 提交表单
const handleSubmit = async () => {
  // 验证表单
  const valid = await formRef.value.validate();
  if (!valid) return;

  try {
    // 获取所有选中的菜单（包括父子级联动的）
    const checkedNodes = menuTreeRef.value?.getCheckedNodes(false, false) || [];
    const checkedMenuIds = checkedNodes.map((node: any) => node.menu_id);

    // 组装 role_menus
    const roleMenus = checkedMenuIds.map(menuId => ({
      role_id: form.role_id,
      menu_id: menuId
    }));

    // 组装 role_permissions - 只包含当前勾选菜单的权限
    const rolePermissions: any[] = [];
    checkedMenuIds.forEach(menuId => {
      const requiredPerms = getRequiredPermissions(menuId);
      const selectedPerms = checkedPermissions.value[menuId] || [];
      const finalPerms = [...new Set([...requiredPerms, ...selectedPerms])];

      finalPerms.forEach(permissionId => {
        rolePermissions.push({
          role_id: form.role_id,
          permission_id: permissionId,
          menu_id: menuId
        });
      });
    });

    const submitData = {
      name: form.name,
      description: form.description,
      admin_roles: form.admins.map(admin => ({
        admin_id: admin.admin_id,
        role_id: form.role_id
      })),
      role_menus: roleMenus,
      role_permissions: rolePermissions
    };

    if (title.value === "新增") {
      await useHandleData(
        (params) => api(params),
        submitData,
        "新增角色"
      );
    } else if (title.value === "编辑") {
      if (!form.role_id) return;

      await useHandleData(
        (params) => api(params.id, params.data),
        { id: form.role_id, data: submitData },
        "编辑角色"
      );
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
    max-height: 500px;
    overflow-y: auto;
    padding-right: 10px;
  }
  
  .el-tree-node__content {
    min-height: 30px;
    padding-bottom: 10px !important;
  }
}

/* 菜单项容器样式 - 分离标题和按钮 */
.menu-item-container {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
}

.menu-title {
  flex: 1;
}

.permission-btn {
  margin-left: 10px;
  white-space: nowrap;
}

:deep(.is-disabled) {
  opacity: 0.8;
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

.permission-group {
  max-height: 400px;
  overflow-y: auto;
  padding-right: 10px;
}

.permission-item {
  margin-bottom: 12px;
}

.permission-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.permission-name {
  display: flex;
  align-items: center;
  gap: 8px;
}

.required-tag {
  font-size: 12px;
  color: #fff;
  background-color: #ff4d4f;
  padding: 0 4px;
  border-radius: 2px;
}

.permission-desc {
  margin-left: 24px;
}

.no-permission {
  text-align: center;
  padding: 20px;
  color: #666;
}
</style>