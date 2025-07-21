<template>
  <div class="table-box">
    <ProTable
      ref="proTable"
      title="账号管理"
      row-key="admin_id"
      :columns="columns"
      :request-api="getAdminList"
      :init-param="initParam"
      :data-callback="dataCallback"
      :pagination="true"
    >
      <!-- 表格 header 按钮 -->
      <template #tableHeader="scope">
        <el-button type="primary" v-auth="'create'" :icon="CirclePlus" @click="addAdmin">新增账号</el-button>
        <el-button
          v-auth="'admin.delete'"
          type="danger"
          :icon="Delete"
          plain
          :disabled="!scope.isSelected"
          @click="batchDelete(scope.selectedListIds)"
        >
          批量删除
        </el-button>
      </template>

      <!-- 操作列 -->
      <template #operation="scope">
        <el-button type="primary" v-auth="'update'" link :icon="EditPen" @click="editAdmin(scope.row)">编辑</el-button>
        <el-button type="primary" v-auth="'delete'" link :icon="Delete" @click="deleteAdmin(scope.row)">删除</el-button>
        <el-button type="primary" v-auth="'delete'" link :icon="View" @click="openDetailDialog(scope.row.admin_id)">查看详情</el-button>
      </template>

      <!-- 状态列 -->
      <template #status="scope">
        <el-tag v-if="scope.row.status === 1" type="success">启用</el-tag>
        <el-tag v-else type="danger">禁用</el-tag>
      </template>
    </ProTable>

    <!-- 账号编辑弹窗 -->
     <el-dialog
      v-model="dialogVisible"
      :title="isEdit ? '编辑账号' : '新增账号'"
      width="500px"
      :close-on-click-modal="false"
    >
      <el-form
        :model="adminForm"
        :rules="formRules"
        ref="formRef"
        label-width="120px"
      >
        <el-form-item label="用户名" prop="username">
          <el-input
            v-model="adminForm.username"
            placeholder="请输入用户名"
            maxlength="50"
          />
        </el-form-item>

        <el-form-item label="密码" prop="password">
          <el-input
            v-model="adminForm.password"
            type="password"
            placeholder="请输入密码"
            :show-password="true"
          />
          <div class="form-tip">密码长度至少8位，需包含大小写字母、数字和特殊字符</div>
        </el-form-item>

        <el-form-item label="确认密码" prop="password_confirm">
          <el-input
            v-model="adminForm.password_confirm"
            type="password"
            placeholder="请再次输入密码"
            :show-password="true"
          />
        </el-form-item>

        <el-form-item label="真实姓名" prop="real_name">
          <el-input
            v-model="adminForm.real_name"
            placeholder="请输入真实姓名"
            maxlength="50"
          />
        </el-form-item>

        <el-form-item label="昵称" prop="nick_name">
          <el-input
            v-model="adminForm.nick_name"
            placeholder="请输入昵称"
            maxlength="50"
          />
        </el-form-item>

        <el-form-item label="头像" prop="avatar">
          <el-upload
            class="avatar-uploader"
            action="https://jsonplaceholder.typicode.com/posts/"
            :show-file-list="false"
            :on-success="handleAvatarSuccess"
            :before-upload="beforeAvatarUpload"
          >
            <img v-if="adminForm.avatar" :src="adminForm.avatar" class="avatar" />
            <el-icon v-else><Plus /></el-icon>
          </el-upload>
        </el-form-item>

        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="adminForm.status">
            <el-radio :label="1">启用</el-radio>
            <el-radio :label="2">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="submitForm">确认</el-button>
      </template>
    </el-dialog>

    <!-- 账号详情弹窗 -->
    <el-dialog
      v-model="detailDialogVisible"
      title="账号详情"
      width="500px"
      :close-on-click-modal="false"
    >
      <el-form
        :model="adminDetail"
        label-width="120px"
      >
        <el-form-item label="用户名">
          <span>{{ adminDetail.username }}</span>
        </el-form-item>
        <el-form-item label="真实姓名">
          <span>{{ adminDetail.real_name || '无' }}</span>
        </el-form-item>
        <el-form-item label="昵称">
          <span>{{ adminDetail.nick_name || '无' }}</span>
        </el-form-item>
        <el-form-item label="头像">
          <span>{{ adminDetail.avatar ? '有头像' : '无头像' }}</span>
        </el-form-item>
        <el-form-item label="状态">
          <el-tag :type="adminDetail.status === 1 ? 'success' : 'danger'">
            {{ adminDetail.status === 1 ? '启用' : '禁用' }}
          </el-tag>
        </el-form-item>
        <el-form-item label="创建时间">
          <span>{{ new Date(adminDetail.created_at).toLocaleString() }}</span>
        </el-form-item>
        <el-form-item label="更新时间">
          <span>{{ new Date(adminDetail.updated_at).toLocaleString() }}</span>
        </el-form-item>
      </el-form>

      <template #footer>
        <el-button @click="detailDialogVisible = false">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from "vue";
import { ElMessageBox, ElMessage } from "element-plus";
import type { FormInstance, FormRules } from "element-plus";
import ProTable from "@/components/ProTable/index.vue";
import { ColumnProps } from "@/components/ProTable/interface";
import { CirclePlus, Delete, EditPen, Plus, View } from "@element-plus/icons-vue";
import {
  getListApi as getAdminListApi,
  postCreateApi,
  getReadApi,
  putUpdateApi,
  deleteDeleteApi
} from "@/api/modules/account";

const proTable = ref<InstanceType<typeof ProTable>>();
const formRef = ref<FormInstance>();

const dialogVisible = ref(false);
const isEdit = ref(false);
const detailDialogVisible = ref(false);
const adminDetail = ref({
  admin_id: 0,
  username: "",
  created_at: "",
  updated_at: "",
  real_name: null,
  nick_name: null,
  status: 1,
  avatar: "",
  deleted_at: null
});

const initParam = reactive({});

// 安全数据处理函数
const dataCallback = (res: any) => {
  const safeData = res || {};
  return {
    list: safeData.list || [],
    total: safeData.total || 0
  };
};

const getAdminList = (params: any) => {
  return getAdminListApi(params);
};

const adminForm = ref({
  admin_id: 0,
  username: "",
  password: "",
  password_confirm: "",
  real_name: "",
  nick_name: "",
  avatar: "",
  status: 1
});

// 表单验证规则
const formRules = reactive<FormRules>({
  username: [
    { required: true, message: "用户名不能为空", trigger: "blur" },
    { min: 2, max: 50, message: "长度需在2-50个字符", trigger: "blur" }
  ],
  password: [
    { required: true, message: "密码不能为空", trigger: "blur", validator: (rule, value, callback) => {
        if (!isEdit.value && !value) {
          callback(new Error("密码不能为空"));
        } else if (value && value.length < 8) {
          callback(new Error("密码长度至少8位"));
        } else {
          callback();
        }
      }
    }
  ],
  password_confirm: [
    { required: true, message: "确认密码不能为空", trigger: "blur" },
    { validator: (rule, value, callback) => {
        if (value !== adminForm.value.password) {
          callback(new Error("两次输入的密码不一致"));
        } else {
          callback();
        }
      }, trigger: "blur"
    }
  ],
  real_name: [
    { max: 50, message: "长度不能超过50个字符", trigger: "blur" }
  ],
  nick_name: [
    { max: 50, message: "长度不能超过50个字符", trigger: "blur" }
  ],
  status: [
    { required: true, message: "请选择状态", trigger: "change" }
  ]
});

// 初始化表单
const initForm = () => {
  adminForm.value = {
    admin_id: 0,
    username: "",
    password: "",
    password_confirm: "",
    real_name: "",
    nick_name: "",
    avatar: "",
    status: 1
  };
};

const addAdmin = () => {
  isEdit.value = false;
  initForm();
  dialogVisible.value = true;
};

const editAdmin = async (row: any) => {
  isEdit.value = true;
  try {
    const res = await getReadApi(row.admin_id);

    // 安全赋值，确保所有字段存在
    const formData = res.data || {};
    adminForm.value = {
      admin_id: formData.admin_id || 0,
      username: formData.username || "",
      password: "", // 编辑时不显示原密码
      password_confirm: "",
      real_name: formData.real_name || "",
      nick_name: formData.nick_name || "",
      avatar: formData.avatar || "",
      status: formData.status || 1
    };

    dialogVisible.value = true;
  } catch (error) {
    ElMessage.error("获取账号信息失败");
  }
};

const deleteAdmin = async (row: any) => {
  try {
    await ElMessageBox.confirm(
      `确定要删除账号 "${row.username}" 吗?`,
      "提示",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }
    );

    await deleteDeleteApi({ ids: [row.admin_id] });
    ElMessage.success("删除成功");
    proTable.value?.getTableList();
  } catch (error) {
    // 用户取消删除，无需处理
  }
};

const batchDelete = async (ids: number[]) => {
  if (ids.length === 0) {
    ElMessage.warning("请选择需要删除的账号");
    return;
  }

  try {
    await ElMessageBox.confirm(
      `确定要删除选中的 ${ids.length} 个账号吗?`,
      "提示",
      {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
        type: "warning"
      }
    );

    await deleteDeleteApi({ ids });
    ElMessage.success(`成功删除 ${ids.length} 个账号`);

    proTable.value?.clearSelection();
    proTable.value?.getTableList();
  } catch (error) {
    // 用户取消删除，无需处理
  }
};

const submitForm = async () => {
  if (!formRef.value) return;

  try {
    // 验证表单
    await formRef.value.validate();

    // 准备提交数据
    const payload = { ...adminForm.value };

    // 编辑时如果密码为空，则不提交密码字段
    if (isEdit.value && !payload.password) {
      delete payload.password;
      delete payload.password_confirm;
    }

    if (isEdit.value) {
      await putUpdateApi(payload.admin_id, payload);
      ElMessage.success("更新成功");
    } else {
      await postCreateApi(payload);
      ElMessage.success("创建成功");
    }

    // 关闭弹窗并刷新表格
    dialogVisible.value = false;
    proTable.value?.getTableList();
  } catch (error: any) {
    ElMessage.error(error.message || "提交失败");
  }
};

// 头像上传处理
const handleAvatarSuccess = (res: any, file: any) => {
  adminForm.value.avatar = URL.createObjectURL(file.raw);
};

const beforeAvatarUpload = (file: any) => {
  const isJPG = file.type === 'image/jpeg' || file.type === 'image/png';
  if (!isJPG) {
    ElMessage.error('请上传JPG或PNG格式的图片');
    return false;
  }
  const isLt2M = file.size / 1024 / 1024 < 2;
  if (!isLt2M) {
    ElMessage.error('图片大小不能超过2MB');
    return false;
  }
  return true;
};

const columns = reactive<ColumnProps[]>([
  { type: "selection", fixed: "left", width: 70 },
  { type: "sort", label: "排序", width: 80 },
  { prop: "username", label: "用户名", search: { el: "input" }, width: 150 },
  { prop: "real_name", label: "真实姓名", search: { el: "input" }, width: 120 },
  { prop: "nick_name", label: "昵称", width: 120 },
  {
    prop: "avatar",
    label: "头像",
    width: 100,
    formatter: (row) => row.avatar ? '有头像' : '无头像'
  },
  { prop: "status", label: "状态", width: 100, custom: true },
  {
    prop: "created_at",
    label: "创建时间",
    width: 130,
    formatter: (row) => new Date(row.created_at).toLocaleString()
  },
  {
    prop: "updated_at",
    label: "更新时间",
    width: 130,
    formatter: (row) => new Date(row.updated_at).toLocaleString()
  },
  { prop: "operation", label: "操作", fixed: "right", width: 240 }
]);

const openDetailDialog = async (adminId: number) => {
  try {
    const res = await getReadApi(adminId);
    const data = res.data || {};
    adminDetail.value = {
      admin_id: data.admin_id || 0,
      username: data.username || "",
      created_at: data.created_at || "",
      updated_at: data.updated_at || "",
      real_name: data.real_name,
      nick_name: data.nick_name,
      status: data.status || 1,
      avatar: data.avatar || "",
      deleted_at: data.deleted_at
    };
    detailDialogVisible.value = true;
  } catch (error) {
    ElMessage.error("获取账号详情失败");
  }
};
</script>

<style scoped>
.avatar-uploader .el-upload {
  border: 1px dashed #d9d9d9;
  border-radius: 6px;
  cursor: pointer;
  position: relative;
  overflow: hidden;
}
.avatar-uploader .el-upload:hover {
  border-color: #409EFF;
}
.avatar-uploader-icon {
  font-size: 28px;
  color: #8c939d;
  width: 100px;
  height: 100px;
  line-height: 100px;
  text-align: center;
}
.avatar {
  width: 100px;
  height: 100px;
  display: block;
}
.form-tip {
  font-size: 12px;
  color: #999;
  margin-top: 4px;
  line-height: 1.4;
}
</style>