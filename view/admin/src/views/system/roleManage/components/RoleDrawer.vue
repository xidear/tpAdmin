<template>
  <el-drawer
    v-model="visible"
    :title="title"
    :width="500"
    :close-on-click-modal="false"
    @close="handleClose"
  >
    <el-form
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
  </el-drawer>
</template>

<script setup lang="ts" name="RoleDrawer">
import { ref, reactive } from "vue";
import { ElMessage } from "element-plus";
import { useHandleData } from "@/hooks/useHandleData";
import { Role } from "@/api/modules/role";

// 抽屉是否可见
const visible = ref(false);
// 标题
const title = ref("");
// 是否为查看模式
const isView = ref(false);
// 表单数据
const form = reactive<Partial<Role.RoleOptions>>({
  role_id: undefined,
  name: "",
  description: "",
  created_at: "",
  updated_at: ""
});
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
// 回调函数 - 获取列表数据
let getTableList: () => void = () => {};
// API 引用
let api: any = null;

// 接收参数
const acceptParams = (params: {
  title: string;
  isView: boolean;
  row: Partial<Role.RoleOptions>;
  api?: any;
  getTableList: () => void;
}) => {
  title.value = params.title;
  isView.value = params.isView;
  api = params.api;
  getTableList = params.getTableList;

  // 重置表单
  Object.assign(form, {
    role_id: undefined,
    name: "",
    description: "",
    created_at: "",
    updated_at: ""
  });

  // 如果是编辑或查看，填充数据
  if (params.row && params.row.role_id) {
    Object.assign(form, params.row);
  }

  visible.value = true;
};

// 关闭抽屉
const handleClose = () => {
  visible.value = false;
  formRef.value?.resetFields();
};

// 提交表单
const handleSubmit = async () => {
  // 表单验证
  const valid = await formRef.value.validate();
  if (!valid) return;

  try {
    if (title.value === "新增") {
      // 新增角色 - 传递单个参数对象
      await useHandleData(api, {
        name: form.name,
        description: form.description
      }, "新增角色成功");
    } else if (title.value === "编辑") {
      // 编辑角色 - 将id和参数合并为一个对象传递
      if (!form.role_id) return;
      await useHandleData(api, {
        id: form.role_id,
        name: form.name,
        description: form.description
      }, "编辑角色成功");
    }

    // 关闭抽屉
    handleClose();
    // 刷新列表
    getTableList();
  } catch (error) {
    console.error("提交失败:", error);
  }
};

// 暴露方法
defineExpose({
  acceptParams
});
</script>
