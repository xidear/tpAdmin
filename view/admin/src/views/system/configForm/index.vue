<template>
  <el-tabs v-model="activeGroupId" type="border-card" class="config-tabs">
    <el-tab-pane
      v-for="group in configGroups"
      :key="group.group_id"
      :label="group.group_name"
      :name="group.group_id.toString()"
    >
      <el-form
        :ref="(el) => formRefs[group.group_id] = el"
        :model="formData[group.group_id]"
        :rules="formRules[group.group_id]"
        label-width="140px"
        class="config-form"
      >
        <el-form-item
          v-for="field in group.fields"
          :key="field.key"
          :label="field.label"
          :prop="field.key"
        >
          <!-- 文本输入框（type:1/7） -->
          <el-input
            v-if="[1,7].includes(field.type)"
            v-model.trim="formData[group.group_id][field.key]"
            :placeholder="field.placeholder || `请输入${field.label}`"
            clearable
          />

          <!-- 数字输入框（type:3） -->
          <el-input-number
            v-else-if="field.type === 3"
            v-model.number="formData[group.group_id][field.key]"
            :placeholder="field.placeholder || `请输入${field.label}`"
            controls-position="right"
          />

          <!-- 开关（type:10） -->
          <el-switch
            v-else-if="field.type === 10"
            v-model="formData[group.group_id][field.key]"
            :active-value="field.options[0]?.value"
            :inactive-value="field.options[1]?.value"
            :active-text="field.options[0]?.label"
            :inactive-text="field.options[1]?.label"
          />

          <!-- 文件上传（type:20） -->
          <el-upload
            v-else-if="field.type === 20"
            :file-list="fileList[group.group_id]?.[field.key] || []"
            :accept="field.accept"
            :multiple="field.multiple"
            :auto-upload="true"
            :http-request="(uploadFile) => handleHttpUpload(uploadFile, field, group.group_id)"
            class="upload-control"
          >
            <el-button size="small" type="primary">点击上传</el-button>
          </el-upload>

          <div v-else>不支持的字段类型</div>
        </el-form-item>

        <el-form-item>
          <el-button type="primary" @click="handleSubmit(group.group_id)">保存配置</el-button>
        </el-form-item>
      </el-form>
    </el-tab-pane>
  </el-tabs>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, nextTick } from 'vue';
import { ElMessage, ElForm } from 'element-plus';
import type { ConfigForm } from '@/typings/configForm';
import { getConfigFormApi, saveConfigFormApi } from '@/api/modules/configForm';
import { uploadImg, uploadVideo, uploadFile } from '@/api/modules/upload';
import type { Upload } from '@/api/interface';

const activeGroupId = ref<string>('1');
const configGroups = ref<ConfigForm.ConfigGroup[]>([]);
const formRefs = ref<Record<number, InstanceType<typeof ElForm> | undefined>>({});
const formData = reactive<Record<number, Record<string, any>>>({});
const formRules = reactive<Record<number, Record<string, any[]>>>({});
const fileList = reactive<Record<number, Record<string, any[]>>>({});

const initForm = async () => {
  try {
    const res = await getConfigFormApi();
    configGroups.value = res.data || [];

    configGroups.value.forEach((group) => {
      formData[group.group_id] = group.fields.reduce((obj, field) => {
        if (field.type === 10) {
          obj[field.key] = String(field.value || field.options[0]?.value || '1');
        } else if (field.type === 3) {
          obj[field.key] = Number(field.value) || 0;
        } else {
          obj[field.key] = field.value;
        }
        return obj;
      }, {} as Record<string, any>);

      fileList[group.group_id] = group.fields.reduce((obj, field) => {
        obj[field.key] = field.value ? [{ url: field.value }] : [];
        return obj;
      }, {} as Record<string, any[]>);

      formRules[group.group_id] = group.fields.reduce((obj, field) => {
        const rules: any[] = [];

        if (field.required && field.type !== 20) {
          rules.push({
            required: true,
            message: `请输入${field.label}`,
            trigger: 'blur',
          });
        }

        field.rules.forEach((rule) => {
          if (rule.label === 'type') {
            const type = field.type === 10 ? 'string' : rule.value;
            rules.push({ type, message: rule.message, trigger: 'blur' });
          } else if (rule.label === 'pattern') {
            rules.push({
              pattern: new RegExp(rule.value.replace(/^\/|\/$/g, '')),
              message: rule.message,
              trigger: 'blur',
            });
          }
        });

        obj[field.key] = rules;
        return obj;
      }, {} as Record<string, any[]>);
    });
  } catch (error) {
    ElMessage.error('加载配置失败');
  }
};

const handleHttpUpload = async (
  uploadFile: any,
  field: ConfigForm.ConfigField,
  groupId: number
) => {
  try {
    const uploadFormData = new FormData();
    uploadFormData.append('file', uploadFile.file);

    let uploadRes: Upload.ResFileUrl;
    if (field.accept?.includes('image')) {
      uploadRes = await uploadImg(uploadFormData);
    } else if (field.accept?.includes('video')) {
      uploadRes = await uploadVideo(uploadFormData);
    } else {
      uploadRes = await uploadFile(uploadFormData);
    }

    if (uploadRes.data?.fileUrl) {
      formData[groupId][field.key] = uploadRes.data.fileUrl;
      fileList[groupId][field.key] = [{ url: uploadRes.data.fileUrl }];
      uploadFile.onSuccess();
      ElMessage.success('文件上传成功');

      await nextTick();
      const currentForm = formRefs.value[groupId];
      if (currentForm) {
        await currentForm.validateField(field.key);
      }
    }
  } catch (error) {
    uploadFile.onError(error);
    ElMessage.error('文件上传失败');
  }
};

const handleSubmit = async (groupId: number) => {
  const currentForm = formRefs.value[groupId];
  if (!currentForm) {
    ElMessage.error('表单实例不存在');
    return;
  }

  try {
    await currentForm.validate();

    const submitParams: ConfigForm.SaveConfigParams = {
      group_id: Number(groupId),
      fields: JSON.parse(JSON.stringify(formData[groupId]))
    };

    const saveRes = await saveConfigFormApi(submitParams);

    if (saveRes.code === 200) {
      ElMessage.success('配置保存成功');
      console.log('提交成功，参数:', submitParams, '后端返回:', saveRes);
    } else {
      ElMessage.error(`保存失败: ${saveRes.msg || '未知错误'}`);
    }
  } catch (error: any) {
    ElMessage.error(`提交失败: ${error.message || '网络异常'}`);
  }
};

onMounted(() => {
  initForm();
});
</script>

<style scoped>
.config-tabs {
  margin: 20px;
}
.config-form {
  padding: 20px;
  background: #fff;
}
.upload-control {
  width: 100%;
}
</style>
