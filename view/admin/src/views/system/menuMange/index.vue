<template>
  <div class="table-box">
    <ProTable
      ref="proTable"
      title="菜单列表"
      row-key="path"
      :indent="20"
      :columns="columns"
      :data="filteredMenuData"
    >
      <!-- 表格 header 按钮 -->
      <template #tableHeader>
        <div class="flex items-center">
          <div class="search-box flex items-center gap-3">
            <!-- 菜单名称搜索框 -->
            <el-input
              v-model="searchParams.title"
              placeholder="菜单标题"
              clearable
              style="width: 200px"
            />
            <!-- 菜单名称搜索框 -->
            <el-input
              v-model="searchParams.name"
              placeholder="菜单名称"
              clearable
              style="width: 200px"
            />
            <!-- 菜单路径搜索框 -->
            <el-input
              v-model="searchParams.path"
              placeholder="菜单路径"
              clearable
              style="width: 200px"
            />
            <!-- 操作按钮 -->
            <el-button type="primary" @click="handleSearch">搜索</el-button>
            <el-button @click="resetSearch">重置</el-button>
          </div>
        </div>
      </template>

      <!-- 表格 header 右侧按钮 -->
      <template #tableHeaderRight>
        <el-button type="primary" :icon="CirclePlus">新增菜单</el-button>
      </template>

      <!-- 菜单图标 -->
      <template #icon="scope">
        <el-icon :size="18">
          <component :is="scope.row.meta.icon"></component>
        </el-icon>
      </template>

      <!-- 菜单操作 -->
      <template #operation="scope">
        <el-button @click="handleEdit(scope.row)"  type="primary" link :icon="EditPen">编辑</el-button>
        <el-button type="primary" link :icon="Delete" @click="handleDelete(scope.row)">删除</el-button>
      </template>
    </ProTable>

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
            @change="updateAutoPath"
          />
          <div class="text-gray-500 text-xs mt-1">
            顶级菜单请留空或选择空值
          </div>
        </el-form-item>

        <!-- 菜单标题 -->
        <el-form-item label="菜单标题" prop="meta.title">
          <el-input v-model="menuForm.meta.title" placeholder="显示在菜单中的名称" />
        </el-form-item>

        <!-- 菜单图标 -->
        <el-form-item label="菜单图标" prop="meta.icon">
          <el-input v-model="menuForm.meta.icon" placeholder="输入图标组件名（如：Menu）" />
        </el-form-item>

        <!-- 路由名称 -->
        <el-form-item label="路由名称" prop="name">
          <el-input
            v-model="menuForm.name"
            placeholder="唯一的英文标识（如：userManage）"
            @input="updateAutoPath"
          />
        </el-form-item>

        <!-- 路由路径 -->
        <el-form-item label="路由路径">
          <el-input :value="menuForm.path" readonly />
          <div class="text-xs text-gray-500 mt-1">
            自动生成: {{ menuForm.path }}
          </div>
        </el-form-item>

        <!-- 组件路径 -->
        <el-form-item label="组件路径" prop="component">
          <el-input v-model="menuForm.component" placeholder="vue组件文件路径" />
          <div class="text-gray-500 text-xs mt-1">
            例如：/system/userManage/index
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
import { onMounted, ref, reactive, computed } from "vue";
import { ColumnProps } from "@/components/ProTable/interface";
import { Delete, EditPen, CirclePlus } from "@element-plus/icons-vue";
import {deleteDeleteApi, getTreeApi, putUpdateApi, postCreateApi, getReadApi} from "@/api/modules/menu";
// 导入级联选择器
import { ElCascader } from "element-plus";

import type { FormInstance, FormRules } from "element-plus";
import ProTable from "@/components/ProTable/index.vue";
import {ElMessage, ElMessageBox} from "element-plus";

const menuFormRef = ref<FormInstance>();

// 新增状态
const dialogVisible = ref(false);
const isEdit = ref(false);


// 表单验证规则
const formRules = reactive<FormRules>({
  parent_id: [
    { required: false, message: "请选择父级菜单", trigger: "change" }
  ],
  "meta.title": [
    { required: true, message: "菜单标题不能为空", trigger: "blur" }
  ],
  "meta.icon": [
    { required: true, message: "图标不能为空", trigger: "blur" }
  ],
  name: [
    { required: true, message: "路由名称不能为空", trigger: "blur" },
    { pattern: /^[a-zA-Z0-9]+$/, message: "只能包含字母和数字", trigger: "blur" }
  ],
  component: [
    { required: true, message: "组件路径不能为空", trigger: "blur" }
  ]
});


interface MenuForm {
  menu_id: number;
  parent_id: number; // 明确类型为 number
  meta: {
    title: string;
    icon: string;
  };
  name: string;
  path: string;
  component: string; // 明确类型为 string
}

const menuForm = ref<MenuForm>({
  menu_id: 0,
  parent_id: 0,
  meta: {
    title: "",
    icon: ""
  },
  name: "",
  path: "",
  component: "" // 确保这里是字符串类型
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

// 收集子菜单ID的函数
const getChildrenIds = (menu: any, idList: number[] = []) => {
  if (menu.children && menu.children.length) {
    menu.children.forEach((child: any) => {
      idList.push(child.menu_id);
      getChildrenIds(child, idList);
    });
  }
  return idList;
};

// 自动更新路由路径
const updateAutoPath = () => {
  let basePath = "";

  // 查找父级路径
  if (menuForm.value.parent_id) {
    const findParentPath = (menus: any[], parentId: number): string | null => {
      for (const menu of menus) {
        if (menu.menu_id === parentId) {
          return menu.path;
        }
        if (menu.children) {
          const found = findParentPath(menu.children, parentId);
          if (found) return found;
        }
      }
      return null;
    };

    const parentPath = findParentPath(originalMenuData.value, menuForm.value.parent_id);
    if (parentPath) {
      basePath = parentPath.endsWith('/')
        ? parentPath.slice(0, -1)
        : parentPath;
    }
  }

  // 组合路径
  if (menuForm.value.name) {
    menuForm.value.path = `${basePath ? basePath + '/' : '/'}${menuForm.value.name}`;
  } else {
    menuForm.value.path = basePath;
  }
};

// 新增菜单处理
const handleAdd = () => {
  isEdit.value = false;
  currentMenuChildrenIds.value = [];
  menuForm.value = {
    menu_id: 0,
    parent_id: 0,
    meta: {
      title: "",
      icon: ""
    },
    name: "",
    path: "",
    component: ""
  };
  dialogVisible.value = true;
};

// 编辑菜单处理
const handleEdit = async (row: any) => {
  try {
    isEdit.value = true;
    const res = await getReadApi(row.menu_id);

    // 收集当前菜单的子菜单ID
    currentMenuChildrenIds.value = getChildrenIds(row);

    // 填充表单数据
    menuForm.value = {
      menu_id: row.menu_id,
      parent_id: Number(res.data.parent_id)||0,
      meta: {
        title: res.data.meta?.title || "",
        icon: res.data.meta?.icon || ""
      },
      name: res.data.name || "",
      path: res.data.path || "",
      component: res.data.component || "" // 确保是字符串
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

  // 提交前确保路径正确生成
  updateAutoPath();

  // 其余提交逻辑保持不变
  menuFormRef.value.validate(async (valid) => {
    if (valid) {
      try {
        // 准备API参数
        const payload: Menu.MenuOptions = {
          title: menuForm.value.meta.title,
          name: menuForm.value.name,
          icon: menuForm.value.meta.icon,
          parent_id: menuForm.value.parent_id || 0,
          path: menuForm.value.path,
          component: menuForm.value.component,
          meta: {
            ...menuForm.value.meta,
            isFull: false,
            isLink: false,
            isHide: false,
            isAffix: false,
            isKeepAlive: true
          }
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

// 修正后的筛选逻辑 - 只显示匹配项及其上级
const filteredMenuData = computed(() => {
  if (!isFilterActive) return originalMenuData.value;

  // 1. 平铺所有菜单项
  const allNodes = flattenTree(originalMenuData.value);

  // 2. 找出所有匹配的节点
  const matchedNodes = allNodes.filter(node => {
    const matchTitle = searchParams.title ? (node.meta?.title || "").includes(searchParams.title) : true;
    const matchName = searchParams.name ? (node.name || "").includes(searchParams.name) : true;
    const matchPath = searchParams.path ? (node.path || "").includes(searchParams.path) : true;
    return matchTitle && matchName && matchPath;
  });

  // 3. 为每个匹配节点回溯其祖先路径
  const resultNodes = new Map();

  matchedNodes.forEach(node => {
    // 添加当前匹配节点
    resultNodes.set(node.path, cloneNodeWithoutChildren(node));

    // 回溯父节点路径
    let currentPath = node.path;
    while (currentPath) {
      // 查找父节点
      const parent = allNodes.find(n => {
        if (!n.children) return false;
        return n.children.some(c => c.path === currentPath);
      });

      if (!parent || resultNodes.has(parent.path)) break;

      // 克隆父节点（不带子节点）
      const parentClone = cloneNodeWithoutChildren(parent);
      resultNodes.set(parent.path, parentClone);
      currentPath = parent.path;
    }
  });

  // 4. 构建结果树结构
  const result = Array.from(resultNodes.values());

  // 5. 重新组装树结构
  result.forEach(node => {
    const children = Array.from(resultNodes.values()).filter(n =>
      n.path !== node.path && n.path.startsWith(node.path + '/')
    );

    if (children.length > 0) {
      node.children = children;
    }
  });

  // 6. 保留顶级节点
  return result.filter(node => {
    return !Array.from(resultNodes.values()).some(n =>
      n !== node && node.path.startsWith(n.path + '/')
    );
  });
});

// 检查是否有活跃的筛选条件
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

// 辅助函数：克隆节点但不包含子元素
const cloneNodeWithoutChildren = (node: any) => {
  const clone = { ...node };
  delete clone.children;
  return clone;
};

// 搜索处理 - 实际上计算属性已经自动更新
const handleSearch = () => {
  // 计算属性会自动更新，这里不需要做任何事情
};

// 重置搜索
const resetSearch = () => {
  searchParams.title = "";
  searchParams.name = "";
  searchParams.path = "";
};

// onMounted(() => {
//   getTreeApi().then((res) => {
//     // 保存原始数据
//     originalMenuData.value = res.data;
//   }).catch((err) => {
//     console.error("获取菜单数据失败", err);
//   });
// });


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
// 删除菜单处理
const handleDelete = async (row: any) => {
  try {
    // 确认对话框
    await ElMessageBox.confirm(
      `确定要删除菜单 "${row.meta?.title || row.path}" 吗?`,
      "提示",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }
    );

    // 调用删除API
    await deleteDeleteApi(row.menu_id);

    // 成功提示
    ElMessage.success("删除成功");

    // 重新加载菜单数据
    await fetchMenuData();
  } catch (error) {
    // 捕获取消操作和API错误

      ElMessage.error("删除失败: " + (error || "未知错误"));
  }
};

const proTable = ref();

// 表格配置项 - 保持与图片一致
const columns: ColumnProps[] = [
  { prop: "meta.title", label: "菜单标题", align: "left", width: 200 },
  { prop: "meta.icon", label: "菜单图标", width: 150 },
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
</style>
