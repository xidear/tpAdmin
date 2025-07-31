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
        <el-button type="primary" v-auth="'read'" link :icon="View" @click="openDetailDialog(scope.row.admin_id)">查看详情</el-button>
      </template>

      <!-- 状态列 -->
      <template #status="scope">
        <el-tag v-if="scope.row.status === 1" type="success">启用</el-tag>
        <el-tag v-else type="danger">禁用</el-tag>
      </template>

      <!-- 头像列 -->
      <template #avatar="scope">
        <div class="avatar-container">
          <img 
            v-if="scope.row.avatar" 
            :src="formatImageUrl(scope.row.avatar)" 
            class="table-avatar" 
            alt="用户头像"
          >
          <span v-else>无头像</span>
        </div>
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
          <div class="form-tip" v-if="!isEdit">密码长度至少8位，需包含大小写字母、数字和特殊字符</div>
          <div class="form-tip" v-if="isEdit">不填写表示不修改密码，若填写需至少8位</div>
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
            :action="''" 
            :http-request="handleAvatarUpload" 
            :show-file-list="false"
            :before-upload="beforeAvatarUpload"
          >
            <div class="upload-avatar-container">
              <img v-if="adminForm.avatar" :src="formatImageUrl(adminForm.avatar)" class="avatar" />
              <el-icon v-else><Plus /></el-icon>
            </div>
          </el-upload>
        </el-form-item>

        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="adminForm.status">
            <el-radio :value="1">启用</el-radio>
            <el-radio :value="2">禁用</el-radio>
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
      width="600px"
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
          <div class="detail-avatar-container">
            <div class="avatar-preview" @click="showLargeAvatar = true">
              <img 
                v-if="adminDetail.avatar" 
                :src="formatImageUrl(adminDetail.avatar)" 
                class="detail-avatar" 
                alt="用户头像"
              >
              <span v-else>无头像</span>
              <div v-if="adminDetail.avatar" class="view-large-tip">点击查看大图</div>
            </div>
          </div>
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

    <!-- 头像大图查看弹窗 -->
    <el-dialog
      v-model="showLargeAvatar"
      title="头像大图"
      width="80%"
      :close-on-click-modal="true"
      :show-close="true"
      :header="false"
      :footer="false"
    >
      <div class="large-avatar-container">
        <img 
          :src="formatImageUrl(adminDetail.avatar)" 
          class="large-avatar" 
          alt="用户头像大图"
        >
      </div>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from "vue";
import { ElMessageBox, ElMessage, UploadRequestOptions } from "element-plus";
import type { FormInstance, FormRules } from "element-plus";
import ProTable from "@/components/ProTable/index.vue";
import { ColumnProps } from "@/components/ProTable/interface";
import { CirclePlus, Delete, EditPen, Plus, View } from "@element-plus/icons-vue";
import { uploadImg } from "@/api/modules/upload";
import {
  getListApi as getAdminListApi,
  postCreateApi,
  getReadApi,
  putUpdateApi,
  deleteDeleteApi,
  batchDeleteApi
} from "@/api/modules/account";

// 状态变量
const proTable = ref<InstanceType<typeof ProTable>>();
const formRef = ref<FormInstance>();
const dialogVisible = ref(false);
const isEdit = ref(false);
const detailDialogVisible = ref(false);
const showLargeAvatar = ref(false);

// 详情数据
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

// 表单数据
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

// 初始化参数
const initParam = reactive({});

// 处理接口返回数据
const dataCallback = (res: any) => {
  const safeData = res || {};
  const list = (safeData.list || []).map((item: any) => ({
    ...item,
    avatar: formatImageUrl(item.avatar || "")
  }));
  return {
    list,
    total: safeData.total || 0
  };
};

// 获取列表数据
const getAdminList = (params: any) => {
  return getAdminListApi(params);
};

// 表单验证规则
const formRules = reactive<FormRules>({
  username: [
    { required: true, message: "用户名不能为空", trigger: "blur" },
    { min: 2, max: 50, message: "长度需在2-50个字符", trigger: "blur" }
  ],
  password: [
    { 
      required: false,
      trigger: "blur", 
      validator: (rule, value, callback) => {
        if (!isEdit.value) {
          if (!value) {
            callback(new Error("密码不能为空"));
          } else if (value.length < 8) {
            callback(new Error("密码长度至少8位"));
          } else {
            callback();
          }
        } else {
          if (value && value.length < 8) {
            callback(new Error("密码长度至少8位"));
          } else {
            callback();
          }
        }
      }
    }
  ],
  password_confirm: [
    { 
      required: false,
      trigger: "blur",
      validator: (rule, value, callback) => {
        if (!adminForm.value.password) {
          callback();
        } else {
          if (value !== adminForm.value.password) {
            callback(new Error("两次输入的密码不一致"));
          } else {
            callback();
          }
        }
      }
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

// 新增账号
const addAdmin = () => {
  isEdit.value = false;
  initForm();
  dialogVisible.value = true;
};

// 编辑账号
const editAdmin = async (row: any) => {
  isEdit.value = true;
  try {
    const res = await getReadApi(row.admin_id);
    const formData = res.data || {};
    adminForm.value = {
      admin_id: formData.admin_id || 0,
      username: formData.username || "",
      password: "",
      password_confirm: "",
      real_name: formData.real_name || "",
      nick_name: formData.nick_name || "",
      avatar: formatImageUrl(formData.avatar || ""),
      status: formData.status || 1
    };
    dialogVisible.value = true;
  } catch (error) {
    ElMessage.error("获取账号信息失败");
  }
};

// 删除单个账号
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

    await deleteDeleteApi(row.admin_id);
    ElMessage.success("删除成功");
    proTable.value?.getTableList();
  } catch (error) {
    // 用户取消删除，不做处理
  }
};

// 批量删除
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

    await batchDeleteApi({ ids });
    ElMessage.success(`成功删除 ${ids.length} 个账号`);
    proTable.value?.clearSelection();
    proTable.value?.getTableList();
  } catch (error) {
    // 用户取消删除，不做处理
  }
};

// 提交表单
const submitForm = async () => {
  if (!formRef.value) return;

  try {
    await formRef.value.validate();
    const payload = { ...adminForm.value };

    // 编辑时密码为空则不提交密码字段
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

    dialogVisible.value = false;
    proTable.value?.getTableList();
  } catch (error: any) {
    ElMessage.error(error.message || "提交失败");
  }
};

// 处理图片路径反斜杠
const formatImageUrl = (url: string) => {
  if (!url) return '';
  return url.replace(/\\/g, '/');
};

// 头像上传处理
const handleAvatarUpload = async (options: UploadRequestOptions) => {
  const { file, onSuccess, onError } = options;
  
  try {
    const formData = new FormData();
    formData.append("file", file);    
    const res = await uploadImg(formData);    
    if (res.code === 200 && res.data?.url) {
      adminForm.value.avatar = formatImageUrl(res.data.url);
      onSuccess(res);
      ElMessage.success("头像上传成功");
    } else {
      throw new Error(res.msg || "上传失败");
    }
  } catch (error: any) {
    onError(error);
    ElMessage.error(error.message || "头像上传失败");
  }
};

// 上传前验证
const beforeAvatarUpload = (file: File) => {
  const isImage = file.type.startsWith('image/');
  if (!isImage) {
    ElMessage.error('请上传图片格式的文件');
    return false;
  }
  
  const isLt2M = file.size / 1024 / 1024 < 2;
  if (!isLt2M) {
    ElMessage.error('图片大小不能超过2MB');
    return false;
  }
  
  return true;
};

// 表格列定义
const columns = reactive<ColumnProps[]>([
  { type: "selection", fixed: "left", width: 70 },
  { type: "sort", label: "排序", width: 80 },
  { prop: "username", label: "用户名", search: { el: "input" } },
  { prop: "real_name", label: "真实姓名", search: { el: "input" }},
  { prop: "nick_name", label: "昵称"},
  {
    prop: "avatar",
    label: "头像",
    width: 100
  },
  { prop: "status", label: "状态", width: 100 },
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
  { prop: "operation", label: "操作", fixed: "right", width: 300 }
]);

// 打开详情弹窗
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
      avatar: formatImageUrl(data.avatar || ""),
      deleted_at: data.deleted_at
    };
    detailDialogVisible.value = true;
  } catch (error) {
    ElMessage.error("获取账号详情失败");
  }
};
</script>

<style scoped>
/* 表格容器样式 */
.table-box {
  padding: 20px;
  background-color: #fff;
  border-radius: 4px;
  box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.05);
}

/* 表格中的头像容器 */
.avatar-container {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
}

/* 表格中的小头像 */
.table-avatar {
  width: 40px;
  height: 40px;
  object-fit: cover;
  border-radius: 4px;
}

/* 上传组件容器 */
.avatar-uploader .el-upload {
  border: 1px dashed #d9d9d9;
  border-radius: 6px;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  width: 100px;
  height: 100px;
}

.avatar-uploader .el-upload:hover {
  border-color: #409EFF;
}

/* 上传组件中的头像容器 */
.upload-avatar-container {
  width: 100px;
  height: 100px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* 编辑弹窗中的头像 */
.avatar {
  width: 100px;
  height: 100px;
  object-fit: cover;
  border-radius: 4px;
}

/* 详情弹窗中的头像容器 */
.detail-avatar-container {
  display: flex;
  align-items: center;
  min-height: 100px;
}

/* 详情中的中等头像 */
.detail-avatar {
  width: 100px;
  height: 100px;
  object-fit: cover;
  border-radius: 4px;
  cursor: pointer;
  transition: transform 0.2s;
}

.detail-avatar:hover {
  transform: scale(1.05);
}

/* 头像预览容器 */
.avatar-preview {
  display: flex;
  align-items: center;
}

/* 查看大图提示 */
.view-large-tip {
  margin-left: 10px;
  font-size: 12px;
  color: #666;
  cursor: pointer;
  white-space: nowrap;
}

/* 大图容器 */
.large-avatar-container {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  min-height: 300px;
}

/* 大图样式 */
.large-avatar {
  max-width: 100%;
  max-height: 80vh;
  object-fit: contain;
  border-radius: 4px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* 表单提示文字 */
.form-tip {
  font-size: 12px;
  color: #999;
  margin-top: 4px;
  line-height: 1.4;
}
</style>
