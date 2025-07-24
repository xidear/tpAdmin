<template>
  <div class="card content-box">
    <div class="table-box mt-4">
      <ProTable
        ref="proTable"
        :columns="columns"
        :request-api="getTableList"
        :init-param="initParam"
        :data-callback="dataCallback"
      >
        <!-- 表格 header 按钮 -->
        <template #tableHeader="scope">
          <el-button v-auth="'create'" type="primary" :icon="CirclePlus" @click="openDrawer('新增')">新增角色</el-button>
          <el-button v-auth="'read'" type="primary" :icon="Download" plain @click="downloadFile">导出角色数据</el-button>
          <el-button  v-auth="'delete'" type="danger" :icon="Delete" plain :disabled="!scope.isSelected" @click="batchDelete(scope.selectedListIds)">
            批量删除角色
          </el-button>
        </template>

        <!-- 表格操作 -->
        <template #operation="scope">
          <el-button v-auth="'read'" type="primary" link :icon="View" @click="openDrawer('查看', scope.row)">查看</el-button>
          <el-button v-auth="'update'" type="primary" link :icon="EditPen" @click="openDrawer('编辑', scope.row)">编辑</el-button>
          <el-button v-auth="'delete'" type="primary" link :icon="Delete" @click="deleteRole(scope.row)">删除</el-button>
        </template>
      </ProTable>

      <RoleDrawer ref="drawerRef"  id="role-drawer"  :drawer-width="1200"/>
    </div>
  </div>
</template>

<script setup lang="tsx" name="roleManage">
import { ref, reactive } from "vue";
import { useRouter } from "vue-router";
import { useHandleData } from "@/hooks/useHandleData";
import { useDownload } from "@/hooks/useDownload";
import { useAuthButtons } from "@/hooks/useAuthButtons";
import { ElMessage, ElMessageBox } from "element-plus";
import ProTable from "@/components/ProTable/index.vue";
import { ProTableInstance, ColumnProps } from "@/components/ProTable/interface";
import { CirclePlus, Delete, EditPen, Download, View } from "@element-plus/icons-vue";
import {
  getListApi,
  deleteDeleteApi,
  putUpdateApi,
  postCreateApi,
  getReadApi,  // 引入详情API
  batchDeleteDeleteApi, Role
} from "@/api/modules/role";
import RoleDrawer from "@/views/system/roleManage/components/RoleDrawer.vue";

const router = useRouter();

// ProTable 实例
const proTable = ref<ProTableInstance>();

// 初始化请求参数
const initParam = reactive({});

const dataCallback = (res: any) => {
  const safeData = res || {};
  console.log(res)
  return {
    list: safeData.list || [],
    total: safeData.total || 0
  };
};

// 请求表格数据
const getTableList = (params: any) => {
  let newParams = JSON.parse(JSON.stringify(params));
  // 处理时间范围查询，如果有的话
  newParams.createTime && (newParams.startTime = newParams.createTime[0]);
  newParams.createTime && (newParams.endTime = newParams.createTime[1]);
  delete newParams.createTime;
  return getListApi(newParams);
};

// 页面按钮权限
const { BUTTONS } = useAuthButtons();

// 表格配置项
const columns = reactive<ColumnProps<Role.RoleOptions>[]>([
  { type: "selection", fixed: "left", width: 70 },
  {
    prop: "role_id",
    label: "角色ID",
    width: 100,
    search: { el: "input" }
  },
  {
    prop: "name",
    label: "角色名称",
    search: { el: "input", tooltip: "搜索角色名称" },
    render: scope => {
      return (
        <el-button type="primary" link onClick={() => ElMessage.success(`查看角色: ${scope.row.name}`)}>
          {scope.row.name}
        </el-button>
      );
    }
  },
  {
    prop: "description",
    label: "角色描述",
    search: { el: "input", tooltip: "搜索角色描述" }
  },
  {
    prop: "created_at",
    label: "创建时间",
    width: 180,
    search: {
      el: "date-picker",
      span: 2,
      props: { type: "datetimerange", valueFormat: "YYYY-MM-DD HH:mm:ss" }
    }
  },
  {
    prop: "updated_at",
    label: "更新时间",
    width: 180
  },
  { prop: "operation", label: "操作", fixed: "right", width: 240 }
]);

// 删除角色
const deleteRole = async (params: Role.RoleOptions) => {
  await useHandleData(deleteDeleteApi, params.role_id, `删除【${params.name}】角色`);
  proTable.value?.getTableList();
};

// 批量删除角色
const batchDelete = async (ids: number[]) => {
  await useHandleData(batchDeleteDeleteApi, { ids }, "删除所选角色");
  proTable.value?.clearSelection();
  proTable.value?.getTableList();
};

// 导出角色列表
const downloadFile = async () => {
  ElMessageBox.confirm("确认导出角色数据?", "温馨提示", { type: "warning" }).then(() =>
    useDownload(getListApi, "角色列表", proTable.value?.searchParam)
  );
};

// 打开抽屉(新增、查看、编辑)
const drawerRef = ref<InstanceType<typeof RoleDrawer> | null>(null);
const openDrawer = (title: string, row: Partial<Role.RoleOptions> = {}) => {
  const params = {
    title,
    isView: title === "查看",
    row: { ...row },
    // 根据操作类型选择对应的API
    api: title === "新增" ? postCreateApi : title === "编辑" ? putUpdateApi : undefined,
    detailApi: getReadApi,  // 传递详情API给抽屉组件
    getTableList: () => proTable.value?.getTableList()
  };
  drawerRef.value?.acceptParams(params);
};
</script>
<style>
#role-drawer{
  max-width: none !important;
  min-width: 800px !important;
}
</style>
