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
        <el-button type="primary" link :icon="EditPen">编辑</el-button>
        <el-button type="primary" link :icon="Delete" @click="handleDelete(scope.row)">删除</el-button>
      </template>
    </ProTable>
  </div>
</template>

<script setup lang="ts" name="menuMange">
import { onMounted, ref, reactive, computed } from "vue";
import { ColumnProps } from "@/components/ProTable/interface";
import { Delete, EditPen, CirclePlus } from "@element-plus/icons-vue";
import {deleteMenuApi, getAuthMenuListApi} from "@/api/modules/menu";
import ProTable from "@/components/ProTable/index.vue";
import {ElMessage, ElMessageBox} from "element-plus";

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
//   getAuthMenuListApi().then((res) => {
//     // 保存原始数据
//     originalMenuData.value = res.data;
//   }).catch((err) => {
//     console.error("获取菜单数据失败", err);
//   });
// });


const fetchMenuData = async () => {
  try {
    const res = await getAuthMenuListApi();
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
    await deleteMenuApi(row.menu_id);

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
  { prop: "name", label: "菜单名称", width: 200 },
  { prop: "path", label: "菜单路径", width: 300 },
  { prop: "component", label: "组件路径", width: 300 },
  { prop: "operation", label: "操作", width: 250, fixed: "right" }
];
</script>

<style scoped>
.search-box {
  padding: 15px 0;
}
</style>
