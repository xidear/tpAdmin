<template>
  <el-dialog
    v-model="dialogVisible"
    title="修改密码"
    width="500px"
    draggable
    :close-on-click-modal="false"
  >
    <el-form
      ref="passwordFormRef"
      :model="passwordForm"
      :rules="passwordRules"
      label-width="100px"
      class="password-form"
    >
      <el-form-item label="旧密码" prop="old_password">
        <el-input
          v-model="passwordForm.old_password"
          type="password"
          placeholder="请输入旧密码"
          show-password
        />
      </el-form-item>
      <el-form-item label="新密码" prop="password">
        <el-input
          v-model="passwordForm.password"
          type="password"
          placeholder="请输入新密码"
          show-password
        />
      </el-form-item>
      <el-form-item label="确认密码" prop="password_confirm">
        <el-input
          v-model="passwordForm.password_confirm"
          type="password"
          placeholder="请再次输入新密码"
          show-password
        />
      </el-form-item>
    </el-form>

    <template #footer>
      <span class="dialog-footer">
        <el-button @click="handleCancel">取消</el-button>
        <el-button type="primary" @click="handleSubmit">确认</el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script setup lang="ts">
import { ref, reactive } from "vue";
import { ElMessage } from "element-plus";
import { changePassword, ChangePasswordParams } from "@/api/modules/base";

// 对话框显示状态
const dialogVisible = ref(false);
// 表单引用
const passwordFormRef = ref<any>(null);

// 密码表单数据
const passwordForm = reactive<ChangePasswordParams>({
  old_password:'',
  password: '',
  password_confirm: ''
});

// 表单验证规则
const passwordRules = {
  old_password: [
    { required: true, message: '请输入旧密码', trigger: 'blur' },
  ],
  password: [
    { required: true, message: '请输入新密码', trigger: 'blur' },
    { min: 6, message: '密码长度不能少于6位', trigger: 'blur' }
  ],
  password_confirm: [
    { required: true, message: '请确认密码', trigger: 'blur' },
    {
      validator: (rule: any, value: string, callback: any) => {
        if (value !== passwordForm.password) {
          callback(new Error('两次输入的密码不一致'));
        } else {
          callback();
        }
      },
      trigger: 'blur'
    }
  ]
};

// 打开对话框
const openDialog = () => {
  dialogVisible.value = true;
  // 重置表单
  resetForm();
};

// 关闭对话框
const handleCancel = () => {
  dialogVisible.value = false;
  resetForm();
};

// 重置表单
const resetForm = () => {
  if (passwordFormRef.value) {
    passwordFormRef.value.resetFields();
  }
  passwordForm.old_password = '';
  passwordForm.password = '';
  passwordForm.password_confirm = '';
};

// 提交表单
const handleSubmit = async () => {
  // 表单验证
  if (!passwordFormRef.value) return;

  try {
    await passwordFormRef.value.validate();

    // 调用修改密码接口
    const response = await changePassword(passwordForm);

    console.log(response);
      ElMessage.success('密码修改成功');
      dialogVisible.value = false;
      resetForm();
  } catch (error: any) {
    return false;
  }
};

// 暴露方法给父组件
defineExpose({ openDialog });
</script>

<style scoped lang="scss">
.password-form {
  margin-top: 20px;
}

.dialog-footer {
  margin-top: 10px;
}
</style>
