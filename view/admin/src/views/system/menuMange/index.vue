<template>
  <div class="table-box">
    <ProTable
      ref="proTable"
      title="菜单列表"
      row-key="menu_id"
      :indent="20"
      :columns="columns"
      :data="filteredMenuData"
    >
      <!-- 表格 header 按钮 -->
      <template #tableHeader>
        <div class="flex items-center">
          <div class="search-box flex items-center gap-3">
            <el-button 
              type="primary" 
              v-auth="'create'" 
              :icon="CirclePlus" 
              @click="handleAdd"
              style="margin-right: 20px" 
            >
              新增菜单
            </el-button>

            <!-- 搜索框 -->
            <el-input
              v-model="searchParams.title"
              placeholder="菜单标题"
              clearable
              style="width: 200px"
            />
            <el-input
              v-model="searchParams.name"
              placeholder="菜单标识"
              clearable
              style="width: 200px"
            />
            <el-input
              v-model="searchParams.path"
              placeholder="路由路径"
              clearable
              style="width: 200px"
            />
            <el-button-group  style="margin-left: 10px;">
              <el-button type="primary" @click="handleSearch">搜索</el-button>
              <el-button @click="resetSearch">重置</el-button>
            </el-button-group>
          </div>
        </div>
      </template>

      <!-- 菜单图标 -->
      <template #icon="scope">
        <el-icon :size="18">
          <component :is="scope.row.icon"></component>
        </el-icon>
      </template>

      <!-- 菜单操作 -->
      <template #operation="scope">
        <el-button @click="handleEdit(scope.row)" v-auth="'update'" type="primary" link :icon="EditPen">编辑</el-button>
        <el-button @click="handleDelete(scope.row)" v-auth="'delete'" type="primary" link :icon="Delete">删除</el-button>
      </template>
    </ProTable>

    <!-- 新增/编辑对话框 -->
    <el-dialog
      v-model="dialogVisible"
      :title="isEdit ? '编辑菜单' : '新增菜单'"
      width="60%"
      destroy-on-close
    >
      <el-form
        ref="menuFormRef"
        :model="menuForm"
        :rules="formRules"
        label-width="120px"
      >
        <!-- 父级菜单选择 -->
        <el-form-item label="父级菜单" prop="parent_id">
          <el-cascader
            v-model="menuForm.parent_id"
            :options="menuCascadeOptions"
            :props="cascadeProps"
            placeholder="选择父级菜单"
            clearable
            filterable
            @change="updatePathPreview"
          />
          <div class="text-gray-500 text-xs mt-1">
            顶级菜单请留空或选择空值
          </div>
        </el-form-item>

        <!-- 菜单标题 -->
        <el-form-item label="菜单标题" prop="title">
          <el-input v-model="menuForm.title" placeholder="显示在菜单中的名称" @change="updatePathPreview" />
        </el-form-item>

        <!-- 菜单图标选择器 -->
        <el-form-item label="菜单图标" prop="icon">
          <el-popover
            v-model:visible="iconPopoverVisible"
            placement="bottom"
            width="600"
            trigger="click"
          >
            <template #reference>
              <el-input
                v-model="menuForm.icon"
                placeholder="点击选择图标"
                readonly
                class="icon-input"
              />
            </template>
            <div class="icon-selector">
              <el-input
                v-model="iconSearch"
                placeholder="搜索图标"
                prefix-icon="Search"
                class="icon-search"
              />
              <div class="icon-list">
                <div
                  v-for="icon in filteredIcons"
                  :key="icon"
                  class="icon-item"
                  @click="selectIcon(icon)"
                >
                  <el-icon :size="24"><component :is="icon" /></el-icon>
                  <span>{{ icon }}</span>
                </div>
              </div>
            </div>
          </el-popover>
        </el-form-item>

        <!-- 路由名称 -->
        <el-form-item label="菜单标识" prop="name">
          <el-input
            v-model="menuForm.name"
            placeholder="唯一的英文标识（如：userManage）"
            @change="updatePathPreview"
          />
          <div class="text-gray-500 text-xs mt-1">
            路由路径将根据此标识和父菜单自动生成
          </div>
        </el-form-item>

        <!-- 路径预览 -->
        <el-form-item label="路径预览">
          <el-input 
            v-model="pathPreview" 
            readonly 
            placeholder="路由路径将根据菜单结构自动生成"
            class="readonly-input"
          />
          <div class="text-gray-500 text-xs mt-1">
            <span>无子菜单的页面将自动添加 /index</span>
            <span v-if="!isEdit" class="text-warning">*保存后将根据实际菜单结构生成最终路径</span>
          </div>
        </el-form-item>

        <!-- 菜单类型选择 -->
        <el-form-item label="菜单类型" prop="menu_type">
          <el-radio-group v-model="menuForm.menu_type" @change="handleMenuTypeChange">
            <el-radio :label="'internal'">内部路由</el-radio>
            <el-radio :label="'external'">外部链接</el-radio>
            <el-radio :label="'redirect'">路由重定向</el-radio>
          </el-radio-group>
        </el-form-item>

        <!-- 组件路径 (仅在内部路由时显示) -->
        <el-form-item 
          label="文件路径" 
          prop="component"
          :required="menuForm.menu_type === 'internal'"
        >
          <el-input 
            v-model="menuForm.component" 
            placeholder="vue文件的相对路径,不包括pages和.vue" 
            :disabled="menuForm.menu_type !== 'internal'"
          />
          <div class="text-gray-500 text-xs mt-1">
            例如：/system/userManage/index（对应views/system/userManage/index.vue）
          </div>
        </el-form-item>

        <!-- 外部链接 (仅在外部链接时显示) -->
        <el-form-item 
          label="外部链接" 
          prop="link_url"
          :required="menuForm.menu_type === 'external'"
        >
          <el-input 
            v-model="menuForm.link_url" 
            placeholder="完整的URL地址" 
            :disabled="menuForm.menu_type !== 'external'"
          />
          <div class="text-gray-500 text-xs mt-1">
            例如：https://www.example.com
          </div>
        </el-form-item>

        <!-- 重定向路径 (仅在路由重定向时显示) -->
        <el-form-item 
          label="重定向路径" 
          prop="redirect"
          :required="menuForm.menu_type === 'redirect'"
        >
          <el-input 
            v-model="menuForm.redirect" 
            placeholder="路由重定向路径" 
            :disabled="menuForm.menu_type !== 'redirect'"
          />
          <div class="text-gray-500 text-xs mt-1">
            例如：/dashboard
          </div>
        </el-form-item>

        <!-- 排序号 -->
        <el-form-item label="排序号" prop="order_num">
          <el-input-number 
            v-model="menuForm.order_num" 
            :min="0" 
            :max="9999" 
            placeholder="数字越小越靠前" 
          />
        </el-form-item>

        <!-- 菜单显示状态 -->
        <el-form-item label="显示状态" prop="visible">
          <el-radio-group v-model="menuForm.visible">
            <el-radio :label="1">显示</el-radio>
            <el-radio :label="2">隐藏</el-radio>
          </el-radio-group>
        </el-form-item>

        <!-- 是否固定 -->
        <el-form-item label="是否固定" prop="is_affix">
          <el-radio-group v-model="menuForm.is_affix">
            <el-radio :label="1">是</el-radio>
            <el-radio :label="2">否</el-radio>
          </el-radio-group>
        </el-form-item>

        <!-- 是否缓存 -->
        <el-form-item label="是否缓存" prop="is_keep_alive">
          <el-radio-group v-model="menuForm.is_keep_alive">
            <el-radio :label="1">是</el-radio>
            <el-radio :label="2">否</el-radio>
          </el-radio-group>
        </el-form-item>

        <!-- 权限依赖管理 -->
        <el-form-item label="权限依赖" class="permission-section">

          <div class="permission-header mb-3">
            <el-button type="primary" size="small" :icon="CirclePlus" @click="showAddPermissionDialog = true">新增权限</el-button>
          </div>


          
          <el-table
            :data="menuPermissions"
            border
            row-key="permission_id"
            style="width: 100%; margin-bottom: 10px"
          >
            <el-table-column prop="permission.name" label="权限名称" width="200" />
            <el-table-column prop="permission.node" label="权限节点" width="200" />
            <el-table-column prop="permission.method" label="请求方法" width="100" />
            <el-table-column 
              label="权限类型" 
              width="120"
            >
              <template #default="scope">
                <el-select 
                  v-model="scope.row.permission_type" 
                  size="small"
                  @change="handlePermissionTypeChange(scope.row)"
                >
                  <el-option label="按钮" value="button" />
                  <el-option label="列表" value="data" />
                  <el-option label="筛选" value="filter" />
                </el-select>
              </template>
            </el-table-column>
            <el-table-column 
              label="依赖类型" 
              width="120"
            >
              <template #default="scope">
                <el-select 
                  v-model="scope.row.type" 
                  size="small"
                  @change="handleDependencyTypeChange(scope.row)"
                >
                  <el-option label="必选" value="REQUIRED" />
                  <el-option label="可选" value="OPTIONAL" />
                </el-select>
              </template>
            </el-table-column>
            <el-table-column 
              label="操作" 
              width="100" 
              fixed="right"
            >
              <template #default="scope">
                <el-button 
                  type="primary" 
                  link 
                  size="small"
                  :icon="Delete" 
                  @click="removePermission(scope.row)"
                  :disabled="scope.row.type === 'REQUIRED'"
                >
                  移除
                </el-button>
              </template>
            </el-table-column>
          </el-table>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitMenuForm">
          {{ isEdit ? '更新' : '创建' }}
        </el-button>
      </template>
    </el-dialog>

    <!-- 新增权限对话框 -->
    <el-dialog
      v-model="showAddPermissionDialog"
      title="添加权限"
      width="50%"
      destroy-on-close
    >
      <el-form
        ref="addPermissionFormRef"
        :model="addPermissionForm"
        :rules="permissionFormRules"
        label-width="120px"
      >
        <el-form-item label="选择权限" prop="permission_id">
          <el-select
            v-model="addPermissionForm.permission_id"
            placeholder="选择权限"
            filterable
            clearable
          >
            <el-option
              v-for="perm in filteredPermissions"
              :key="perm.permission_id"
              :label="perm.name + '(' + perm.node + ')'"
              :value="perm.permission_id"
              :disabled="isPermissionAdded(perm.permission_id)"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="权限类型" prop="permission_type">
          <el-select v-model="addPermissionForm.permission_type" placeholder="选择权限类型">
            <el-option label="按钮" value="button" />
            <el-option label="列表" value="data" />
            <el-option label="筛选" value="filter" />
          </el-select>
        </el-form-item>
        <el-form-item label="依赖类型" prop="type">
          <el-select v-model="addPermissionForm.type" placeholder="选择依赖类型">
            <el-option label="必选" value="REQUIRED" />
            <el-option label="可选" value="OPTIONAL" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="showAddPermissionDialog = false">取消</el-button>
        <el-button type="primary" @click="confirmAddPermission">确定添加</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="menuMange">
import { useAuthButtons } from "@/hooks/useAuthButtons";
const { BUTTONS } = useAuthButtons();

import { onMounted, ref, reactive, computed, watch } from "vue";
import { ColumnProps } from "@/components/ProTable/interface";
import { Delete, EditPen, CirclePlus, Search } from "@element-plus/icons-vue";
import * as Icons from "@element-plus/icons-vue";
import { 
  deleteDeleteApi, 
  getTreeApi, 
  putUpdateApi, 
  postCreateApi, 
  getReadApi 
} from "@/api/modules/menu";
// 导入权限相关API
import { getListApi } from "@/api/modules/permission";
import { ElCascader } from "element-plus";
import type { FormInstance, FormRules } from "element-plus";
import ProTable from "@/components/ProTable/index.vue";
import { ElMessage, ElMessageBox } from "element-plus";

const menuFormRef = ref<FormInstance>();
const addPermissionFormRef = ref<FormInstance>();

// 对话框状态
const dialogVisible = ref(false);
const isEdit = ref(false);
const showAddPermissionDialog = ref(false);

// 图标选择器状态
const iconPopoverVisible = ref(false);
const iconSearch = ref("");

// 图标列表
const allIcons = ref<string[]>([]);
Object.keys(Icons).forEach(key => {
  if (!key.startsWith('$')) {
    allIcons.value.push(key);
  }
});

// 过滤图标
const filteredIcons = computed(() => {
  if (!iconSearch.value) return allIcons.value;
  return allIcons.value.filter(icon => 
    icon.toLowerCase().includes(iconSearch.value.toLowerCase())
  );
});

// 选择图标
const selectIcon = (icon: string) => {
  menuForm.value.icon = icon;
  iconPopoverVisible.value = false;
};

// 菜单类型映射
const MENU_TYPE = {
  INTERNAL: 'internal',
  EXTERNAL: 'external',
  REDIRECT: 'redirect'
};

// 路径预览
const pathPreview = ref("");

// 表单验证规则
const formRules = reactive<FormRules>({
  parent_id: [{ required: false, message: "请选择父级菜单", trigger: "change" }],
  title: [{ required: true, message: "菜单标题不能为空", trigger: "blur" }],
  icon: [{ required: true, message: "图标不能为空", trigger: "blur" }],
  name: [
    { required: true, message: "菜单标识不能为空", trigger: "blur" },
    { pattern: /^[a-zA-Z0-9]+$/, message: "只能包含字母和数字", trigger: "blur" }
  ],
  menu_type: [{ required: true, message: "请选择菜单类型", trigger: "change" }],
  component: [
    { 
      required: computed(() => menuForm.value.menu_type === MENU_TYPE.INTERNAL), 
      message: "组件路径不能为空", 
      trigger: "blur" 
    }
  ],
  link_url: [
    { 
      required: computed(() => menuForm.value.menu_type === MENU_TYPE.EXTERNAL), 
      message: "外部链接不能为空", 
      trigger: "blur" 
    },
    { 
      pattern: /^https?:\/\/.+$/, 
      message: "请输入有效的URL地址", 
      trigger: "blur" 
    }
  ],
  redirect: [
    { 
      required: computed(() => menuForm.value.menu_type === MENU_TYPE.REDIRECT), 
      message: "重定向路径不能为空", 
      trigger: "blur" 
    }
  ],
  order_num: [{ required: true, message: "排序号不能为空", trigger: "blur" }]
});

// 权限表单验证规则
const permissionFormRules = reactive<FormRules>({
  permission_id: [{ required: true, message: "请选择权限", trigger: "change",type: 'number' }],
  permission_type: [{ required: true, message: "请选择权限类型", trigger: "change" }],
  type: [{ required: true, message: "请选择依赖类型", trigger: "change" }]
});

// 菜单表单数据模型
interface MenuForm {
  menu_id: number;
  parent_id: number;
  title: string;
  icon: string;
  name: string;
  component: string;
  path: string; // 后端生成，前端只读
  link_url: string;
  redirect: string;
  order_num: number;
  visible: number;
  menu_type: 'internal' | 'external' | 'redirect';
  is_link: number;
  is_full: number;
  is_affix: number;
  is_keep_alive: number;
}

const menuForm = ref<MenuForm>({
  menu_id: 0,
  parent_id: 0,
  title: "",
  icon: "",
  name: "",
  component: "",
  path: "", // 后端生成，前端只读
  link_url: "",
  redirect: "",
  order_num: 0,
  visible: 1,
  menu_type: 'internal',
  is_link: 2,
  is_full: 2,
  is_affix: 2,
  is_keep_alive: 1
});

// 过滤掉ID为0的权限
const filteredPermissions = computed(() => {
  return allPermissions.value.filter(perm => perm.permission_id !== 0);
});

// 更新路径预览
const updatePathPreview = () => {
  if (!menuForm.value.name) {
    pathPreview.value = "";
    return;
  }
  
  // 查找父菜单路径
  let parentPath = "";
  if (menuForm.value.parent_id) {
    const parentMenu = findMenuById(menuForm.value.parent_id);
    if (parentMenu) {
      parentPath = parentMenu.path || "";
      // 移除可能存在的/index后缀
      parentPath = parentPath.replace(/\/index$/, '');
    }
  }
  
  // 构建基础路径
  const basePath = parentPath 
    ? `${parentPath}/${menuForm.value.name}` 
    : `/${menuForm.value.name}`;
  
  // 预览路径默认添加/index（假设无子菜单）
  // 实际路径将由后端根据菜单结构决定
  pathPreview.value = `${basePath}/index`;
};

// 查找菜单通过ID
const findMenuById = (menuId: number) => {
  const flattenMenus = flattenTree(originalMenuData.value);
  return flattenMenus.find(menu => menu.menu_id === menuId);
};

// 辅助函数：平铺树结构
const flattenTree = (nodes: any[], result: any[] = []) => {
  for (const node of nodes) {
    result.push(node);
    if (node.children && node.children.length) {
      flattenTree(node.children, result);
    }
  }
  return result;
};

// 处理菜单类型变更
const handleMenuTypeChange = (type: string) => {
  // 清空其他类型的字段
  switch(type) {
    case MENU_TYPE.INTERNAL:
      menuForm.value.link_url = "";
      menuForm.value.redirect = "";
      break;
    case MENU_TYPE.EXTERNAL:
      menuForm.value.component = "";
      menuForm.value.redirect = "";
      menuForm.value.is_link = 1; // 外部链接默认设置is_link为1
      break;
    case MENU_TYPE.REDIRECT:
      menuForm.value.component = "";
      menuForm.value.link_url = "";
      break;
  }
};

// 权限依赖数据结构
interface PermissionDependency {
  dependency_id?: number;
  menu_id?: number;
  permission_id: number;
  type: 'REQUIRED' | 'OPTIONAL';
  description?: string;
  created_at?: string;
  permission_type: 'button' | 'data' | 'filter';
  permission: {
    permission_id: number;
    node: string;
    name: string;
    description?: string;
    method: string;
  }
}

// 菜单权限列表
const menuPermissions = ref<PermissionDependency[]>([]);

// 所有权限列表
const allPermissions = ref<any[]>([]);

// 新增权限表单
const addPermissionForm = ref<{
  permission_id: number|string;
  permission_type: 'button' | 'data' | 'filter';
  type: 'REQUIRED' | 'OPTIONAL';
}>({
  permission_id: "",
  permission_type: 'button',
  type: 'REQUIRED'
});

// 检查权限是否已添加
const isPermissionAdded = (permissionId: number) => {
  return menuPermissions.value.some(item => item.permission_id === permissionId);
};

// 获取权限列表
const fetchAllPermissions = async () => {
  try {
    const res = await getListApi({ pageSize: 100 });
    allPermissions.value = res.data.list || [];
  } catch (error) {
    console.error("获取权限列表失败", error);
    ElMessage.error("获取权限列表失败");
  }
};

// 添加权限
const confirmAddPermission = async () => {
  if (!addPermissionFormRef.value) return;
  
  addPermissionFormRef.value.validate(async (valid) => {
    if (valid) {
      // 检查权限是否已添加
      if (isPermissionAdded(addPermissionForm.value.permission_id)) {
        ElMessage.warning("该权限已添加");
        return;
      }
      
      // 查找权限详情
      const permissionInfo = allPermissions.value.find(
        item => item.permission_id === addPermissionForm.value.permission_id
      );
      
      if (permissionInfo) {
        // 添加到权限列表
        menuPermissions.value.push({
          permission_id: addPermissionForm.value.permission_id,
          type: addPermissionForm.value.type,
          permission_type: addPermissionForm.value.permission_type,
          permission: {
            permission_id: permissionInfo.permission_id,
            node: permissionInfo.node,
            name: permissionInfo.name,
            description: permissionInfo.description,
            method: permissionInfo.method
          }
        });
        
        // 关闭对话框并重置表单
        showAddPermissionDialog.value = false;
        addPermissionForm.value = {
          permission_id: "",
          permission_type: 'button',
          type: 'REQUIRED'
        };
        addPermissionFormRef.value.resetFields();
      }
    }
  });
};

// 移除权限
const removePermission = (permission: PermissionDependency) => {
  ElMessageBox.confirm(
    `确定要移除权限 "${permission.permission.name}" 吗?`,
    "提示",
    {
      confirmButtonText: "确定",
      cancelButtonText: "取消",
      type: "warning"
    }
  ).then(() => {
    menuPermissions.value = menuPermissions.value.filter(
      item => !(item.permission_id === permission.permission_id)
    );
    ElMessage.success("移除成功");
  }).catch(() => {
    // 取消操作
  });
};

// 修改权限类型
const handlePermissionTypeChange = (permission: PermissionDependency) => {
  // 可以在这里添加额外的处理逻辑
};

// 修改依赖类型
const handleDependencyTypeChange = (permission: PermissionDependency) => {
  // 可以在这里添加额外的处理逻辑
};

const currentMenuChildrenIds = ref<number[]>([]);

// 级联选择器配置
const cascadeProps = {
  value: 'menu_id',
  label: 'title',
  children: 'children',
  checkStrictly: true,
  emitPath: false
};

// 菜单级联选择器选项
const menuCascadeOptions = computed(() => {
  if (!originalMenuData.value.length) return [];

  const filterMenu = (menus: any[], excludeIds: number[] = []) => {
    return menus
      .filter(menu => !excludeIds.includes(menu.menu_id))
      .map(menu => ({
        ...menu,
        children: menu.children ? filterMenu(menu.children, excludeIds) : []
      }));
  };

  // 编辑时排除当前菜单及其子菜单
  const excludeIds = [...currentMenuChildrenIds.value];
  if (isEdit.value && menuForm.value.menu_id) {
    excludeIds.push(menuForm.value.menu_id);
  }

  return filterMenu([...originalMenuData.value], excludeIds);
});

// 收集子菜单ID
const getChildrenIds = (menu: any, idList: number[] = []) => {
  if (menu.children && menu.children.length) {
    menu.children.forEach((child: any) => {
      idList.push(child.menu_id);
      getChildrenIds(child, idList);
    });
  }
  return idList;
};

// 新增菜单
const handleAdd = async () => {
  isEdit.value = false;
  currentMenuChildrenIds.value = [];
  menuForm.value = {
    menu_id: 0,
    parent_id: 0,
    title: "",
    icon: "",
    name: "",
    component: "",
    path: "", // 后端生成，前端只读
    link_url: "",
    redirect: "",
    order_num: 0,
    visible: 1,
    menu_type: 'internal',
    is_link: 2,
    is_full: 2,
    is_affix: 2,
    is_keep_alive: 1
  };
  
  // 清空权限列表
  menuPermissions.value = [];
  
  // 重置路径预览
  pathPreview.value = "";
  
  // 获取权限列表
  await fetchAllPermissions();
  
  dialogVisible.value = true;
};

// 编辑菜单
const handleEdit = async (row: any) => {
  try {
    isEdit.value = true;
    const res = await getReadApi(row.menu_id);
    
    // 获取权限列表
    await fetchAllPermissions();
    
    // 处理已有权限数据
    if (res.data.dependencies && res.data.dependencies.length) {
      menuPermissions.value = res.data.dependencies.map(dep => ({
        dependency_id: dep.dependency_id,
        menu_id: dep.menu_id,
        permission_id: dep.permission_id,
        type: dep.type,
        description: dep.description,
        created_at: dep.created_at,
        permission_type: dep.permission_type,
        permission: {
          permission_id: dep.permission.permission_id,
          node: dep.permission.node,
          name: dep.permission.name,
          description: dep.permission.description,
          method: dep.permission.method
        }
      }));
    } else {
      menuPermissions.value = [];
    }
    
    currentMenuChildrenIds.value = getChildrenIds(row);

    // 确定菜单类型
    let menuType = 'internal';
    if (res.data.is_link === 1) {
      menuType = 'external';
    } else if (res.data.redirect) {
      menuType = 'redirect';
    }

    menuForm.value = {
      menu_id: row.menu_id,
      parent_id: Number(res.data.parent_id) || 0,
      title: res.data.title || "",
      icon: res.data.icon || "",
      name: res.data.name || "",
      component: res.data.component || "",
      path: res.data.path || "", // 后端生成，前端只读
      link_url: res.data.link_url || "",
      redirect: res.data.redirect || "",
      order_num: res.data.order_num || 0,
      visible: res.data.visible || 1,
      menu_type: menuType as 'internal' | 'external' | 'redirect',
      is_link: res.data.is_link || 2,
      is_full: res.data.is_full || 2,
      is_affix: res.data.is_affix || 2,
      is_keep_alive: res.data.is_keep_alive || 1
    };
    
    // 更新路径预览
    updatePathPreview();

    dialogVisible.value = true;
  } catch (error) {
    console.error("获取菜单详情失败", error);
    ElMessage.error("加载菜单详情失败");
  }
};

// 提交菜单表单
const submitMenuForm = () => {
  if (!menuFormRef.value) return;

  menuFormRef.value.validate(async (valid) => {
    if (valid) {
      try {

        
        // 根据菜单类型设置相关字段
        const payload: any = {
          title: menuForm.value.title,
          icon: menuForm.value.icon,
          name: menuForm.value.name,
          parent_id: menuForm.value.parent_id || 0,
          order_num: menuForm.value.order_num,
          visible: menuForm.value.visible,
          is_affix: menuForm.value.is_affix,
          is_keep_alive: menuForm.value.is_keep_alive,
          // 根据菜单类型设置不同字段
          is_link: menuForm.value.menu_type === 'external' ? 1 : 2,
          component: menuForm.value.menu_type === 'internal' ? menuForm.value.component : null,
          link_url: menuForm.value.menu_type === 'external' ? menuForm.value.link_url : '',
          redirect: menuForm.value.menu_type === 'redirect' ? menuForm.value.redirect : '',
          // 权限依赖数据处理
          dependencies: menuPermissions.value.map(perm => ({
            permission_id: perm.permission_id,
            type: perm.type,
            permission_type: perm.permission_type
          }))
        };

        if (isEdit.value && menuForm.value.menu_id) {
          await putUpdateApi(menuForm.value.menu_id, payload);
          ElMessage.success("菜单更新成功");
        } else {
          await postCreateApi(payload);
          ElMessage.success("菜单创建成功");
        }

        dialogVisible.value = false;
        fetchMenuData();
      } catch (error) {
        console.error("提交菜单失败", error);
        ElMessage.error("操作失败");
      }
    }
  });
};

// 原始菜单数据
const originalMenuData = ref<any[]>([]);

// 搜索参数
const searchParams = reactive({
  title: "",
  name: "",
  path: ""
});

// 前端筛选实现
const filteredMenuData = computed(() => {
  if (!isFilterActive.value) return originalMenuData.value;

  // 1. 平铺整个菜单树
  const allNodes = flattenTree(originalMenuData.value);

  // 2. 筛选匹配的节点
  const matchedNodes = allNodes.filter(node => {
    const matchTitle = searchParams.title ? (node.title || "").includes(searchParams.title) : true;
    const matchName = searchParams.name ? (node.name || "").includes(searchParams.name) : true;
    const matchPath = searchParams.path ? (node.path || "").includes(searchParams.path) : true;
    return matchTitle && matchName && matchPath;
  });

  // 3. 收集匹配节点的ID
  const matchedIds = new Set(matchedNodes.map(node => node.menu_id));

  // 4. 递归构建筛选后的树结构
  const buildFilteredTree = (nodes: any[]): any[] => {
    return nodes.reduce((result, node) => {
      // 如果当前节点匹配或其子节点中有匹配的
      const hasMatch = matchedIds.has(node.menu_id) || 
                      (node.children && node.children.some(child => matchedIds.has(child.menu_id)));
      
      if (hasMatch) {
        // 递归处理子节点
        const children = node.children ? buildFilteredTree(node.children) : [];
        
        // 如果子节点有匹配，保留当前节点
        if (children.length > 0) {
          result.push({ ...node, children });
        } 
        // 如果当前节点匹配且没有子节点，直接添加
        else if (matchedIds.has(node.menu_id)) {
          result.push({ ...node, children: [] });
        }
      }
      
      return result;
    }, [] as any[]);
  };

  return buildFilteredTree(originalMenuData.value);
});

// 检查是否有搜索条件
const isFilterActive = computed(() => {
  return searchParams.title || searchParams.name || searchParams.path;
});

// 搜索处理（前端筛选）
const handleSearch = () => {
  // 无需调用后端，直接触发筛选
};

// 重置搜索
const resetSearch = () => {
  searchParams.title = "";
  searchParams.name = "";
  searchParams.path = "";
};

// 获取菜单数据
const fetchMenuData = async () => {
  try {
    const res = await getTreeApi();
    originalMenuData.value = res.data;
  } catch (err) {
    console.error("获取菜单数据失败", err);
    ElMessage.error("菜单数据加载失败");
  }
};

onMounted(() => {
  fetchMenuData();
});

// 删除菜单
const handleDelete = async (row: any) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除菜单 "${row.title || row.path}" 吗?`,
      "提示",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }
    );

    await deleteDeleteApi(row.menu_id);
    ElMessage.success("删除成功");
    await fetchMenuData();
  } catch (error) {
    ElMessage.error("删除失败: " + (error || "未知错误"));
  }
};

const proTable = ref();

// 表格列配置
const columns: ColumnProps[] = [
  { prop: "title", label: "菜单标题", align: "left", width: 200 },
  { prop: "icon", label: "菜单图标", width: 150 },
  { prop: "name", label: "前端标识", width: 200 },
  { prop: "path", label: "路由路径", width: 300 }, // 显示后端生成的路径
  { prop: "component", label: "实际位置", width: 300 },
  { prop: "operation", label: "操作", width: 250, fixed: "right" }
];
</script>

<style scoped>
.search-box {
  padding: 15px 0;
}

/* 图标选择器样式 */
.icon-selector {
  padding: 16px;
  max-height: 400px;
  overflow-y: auto;
}

.icon-search {
  margin-bottom: 16px;
}

.icon-list {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}

.icon-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 80px;
  height: 80px;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 12px;
  padding: 8px;
  text-align: center;
}

.icon-item:hover {
  background-color: #f0f0f0;
}

.icon-item el-icon {
  margin-bottom: 8px;
}

/* 权限部分样式 */
.permission-section {
  margin-top: 10px;
}
.permission-section :deep(.el-form-item__content) {
  display: block;
}
.permission-header {
  font-weight: bold;
}

/* 只读输入框样式 */
.readonly-input .el-input__inner {
  background-color: #f5f7fa;
  cursor: not-allowed;
}
</style>
