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
      width="40%"
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
          />
          <div class="text-gray-500 text-xs mt-1">
            顶级菜单请留空或选择空值
          </div>
        </el-form-item>

        <!-- 菜单标题 -->
        <el-form-item label="菜单标题" prop="title">
          <el-input v-model="menuForm.title" placeholder="显示在菜单中的名称" />
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
          />
          <div class="text-gray-500 text-xs mt-1">
            后端将根据此字段和菜单结构自动生成路由路径
          </div>
        </el-form-item>

        <!-- 组件路径 -->
        <el-form-item label="文件路径" prop="component">
          <el-input v-model="menuForm.component" placeholder="vue文件的相对路径,不包括pages和.vue" />
          <div class="text-gray-500 text-xs mt-1">
            例如：/system/userManage/index（对应views/system/userManage/index.vue）
          </div>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitMenuForm">
          {{ isEdit ? '更新' : '创建' }}
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts" name="menuMange">
import { useAuthButtons } from "@/hooks/useAuthButtons";
const { BUTTONS } = useAuthButtons();

import { onMounted, ref, reactive, computed } from "vue";
import { ColumnProps } from "@/components/ProTable/interface";
import { Delete, EditPen, CirclePlus } from "@element-plus/icons-vue";
import * as Icons from "@element-plus/icons-vue";
import { 
  deleteDeleteApi, 
  getTreeApi, 
  putUpdateApi, 
  postCreateApi, 
  getReadApi 
} from "@/api/modules/menu";
import { ElCascader } from "element-plus";
import type { FormInstance, FormRules } from "element-plus";
import ProTable from "@/components/ProTable/index.vue";
import { ElMessage, ElMessageBox } from "element-plus";

const menuFormRef = ref<FormInstance>();

// 对话框状态
const dialogVisible = ref(false);
const isEdit = ref(false);

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

// 表单验证规则
const formRules = reactive<FormRules>({
  parent_id: [{ required: false, message: "请选择父级菜单", trigger: "change" }],
  title: [{ required: true, message: "菜单标题不能为空", trigger: "blur" }],
  icon: [{ required: true, message: "图标不能为空", trigger: "blur" }],
  name: [
    { required: true, message: "菜单标识不能为空", trigger: "blur" },
    { pattern: /^[a-zA-Z0-9]+$/, message: "只能包含字母和数字", trigger: "blur" }
  ],
  component: [{ required: true, message: "组件路径不能为空", trigger: "blur" }]
});

// 菜单表单数据模型（移除path字段）
interface MenuForm {
  menu_id: number;
  parent_id: number;
  title: string;
  icon: string;
  name: string;
  component: string;
  is_link?: number;
  is_full?: number;
  is_affix?: number;
  is_keep_alive?: number;
}

const menuForm = ref<MenuForm>({
  menu_id: 0,
  parent_id: 0,
  title: "",
  icon: "",
  name: "",
  component: "",
  is_link: 2,
  is_full: 2,
  is_affix: 2,
  is_keep_alive: 1
});

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
const handleAdd = () => {
  isEdit.value = false;
  currentMenuChildrenIds.value = [];
  menuForm.value = {
    menu_id: 0,
    parent_id: 0,
    title: "",
    icon: "",
    name: "",
    component: "",
    is_link: 2,
    is_full: 2,
    is_affix: 2,
    is_keep_alive: 1
  };
  dialogVisible.value = true;
};

// 编辑菜单
const handleEdit = async (row: any) => {
  try {
    isEdit.value = true;
    const res = await getReadApi(row.menu_id);

    currentMenuChildrenIds.value = getChildrenIds(row);

    menuForm.value = {
      menu_id: row.menu_id,
      parent_id: Number(res.data.parent_id) || 0,
      title: res.data.title || "",
      icon: res.data.icon || "",
      name: res.data.name || "",
      component: res.data.component || "",
      is_link: res.data.is_link || 2,
      is_full: res.data.is_full || 2,
      is_affix: res.data.is_affix || 2,
      is_keep_alive: res.data.is_keep_alive || 1
    };

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
        const payload: any = {
          title: menuForm.value.title,
          icon: menuForm.value.icon,
          name: menuForm.value.name,
          parent_id: menuForm.value.parent_id || 0,
          component: menuForm.value.component,
          is_link: menuForm.value.is_link || 2,
          is_full: menuForm.value.is_full || 2,
          is_affix: menuForm.value.is_affix || 2,
          is_keep_alive: menuForm.value.is_keep_alive || 1
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
  { prop: "path", label: "前端路由", width: 300 },
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

.icon-input .el-input__suffix {
  display: none;
}
</style>