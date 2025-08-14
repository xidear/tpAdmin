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
            @update:model-value="(val) => formData[group.group_id][field.key] = val"
          />

          <!-- 数字输入框（type:3） -->
          <el-input-number
            v-else-if="field.type === 3"
            :model-value="Number(formData[group.group_id][field.key])"
            @update:model-value="(val) => formData[group.group_id][field.key] = val"
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
            @update:model-value="(val) => formData[group.group_id][field.key] = val"
          />

          <!-- 文件上传（type:20） -->
          <div v-else-if="field.type === 20">
            <el-upload
              :file-list="fileList[group.group_id]?.[field.key] || []"
              :accept="field.accept"
              :multiple="field.multiple"
              :auto-upload="true"
              :http-request="(uploadFile) => handleHttpUpload(uploadFile, field, group.group_id)"
              class="upload-control"
              @remove="(file) => handleFileRemove(file, field, group.group_id)"
              @update:model-value="(val) => formData[group.group_id][field.key] = val"
            >
              <el-button size="small" type="primary">点击上传</el-button>
            </el-upload>

            <!-- 预览区域 -->
            <div class="preview-container">
              <el-image
                v-if="field.accept?.includes('image') && formData[group.group_id]?.[field.key]"  
                :src="formData[group.group_id][field.key]"
                fit="contain"
                style="width: 200px; height: 150px; margin-top: 10px"
              />
              <video
                v-else-if="field.accept?.includes('video') && formData[group.group_id]?.[field.key]" 
                :src="formData[group.group_id][field.key]"
                controls
                style="width: 200px; height: 150px; margin-top: 10px"
              ></video>
            </div>
          </div>

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
import { ElMessage, ElForm, ElImage } from 'element-plus';
import type { ConfigForm } from '@/typings/configForm';
import { getConfigFormApi, saveConfigFormApi } from '@/api/modules/configForm';
import { uploadImg, uploadVideo, uploadFile } from '@/api/modules/upload';
import type { Upload } from '@/api/interface';

// 当前激活的分组ID
const activeGroupId = ref<string>('1');
// 配置分组列表
const configGroups = ref<ConfigForm.ConfigGroup[]>([]);
// 表单引用
const formRefs = ref<Record<number, InstanceType<typeof ElForm> | undefined>>({});
// 表单数据（核心：按分组ID存储，确保键为数字类型）
const formData = reactive<Record<number, Record<string, any>>>({});
// 表单校验规则
const formRules = reactive<Record<number, Record<string, any[]>>>({});
// 文件列表
const fileList = reactive<Record<number, Record<string, any[]>>>({});

/**
 * 初始化表单数据：强化分组对象初始化
 */
const initForm = async () => {
  try {
    const res = await getConfigFormApi();
    configGroups.value = res.data || [];

    configGroups.value.forEach((group) => {
      const groupId = group.group_id; // 提取分组ID，确保类型一致
      // 核心保护1：强制初始化分组对象（即使字段为空，也确保formData[groupId]是对象）
      formData[groupId] = formData[groupId] || {}; 

      // 保留你的字段初始化逻辑（reduce）
      formData[groupId] = group.fields.reduce((obj, field) => {
        if (field.type === 10) {
          obj[field.key] = String(field.value || field.options[0]?.value || '1');
        } else if (field.type === 3) {
          obj[field.key] = Number(field.value) || 0; // 保留你的数字初始化
        } else {
          obj[field.key] = field.value;
        }
        return obj;
      }, formData[groupId]); // 基于已初始化的对象扩展，避免覆盖

      // 初始化文件列表
      fileList[groupId] = group.fields.reduce((obj, field) => {
        obj[field.key] = field.value ? [{ url: field.value }] : [];
        return obj;
      }, {} as Record<string, any[]>);

      // 初始化校验规则（保留你的逻辑）
      formRules[groupId] = group.fields.reduce((obj, field) => {
        const rules: any[] = [];
        if (field.required && field.type !== 20) {
          rules.push({ required: true, message: `请输入${field.label}`, trigger: 'blur' });
        }
        field.rules.forEach((rule) => {
          if (rule.label === 'type') {
            const type = field.type === 10 ? 'string' : rule.value;
            rules.push({ type, message: rule.message, trigger: 'blur' });
          } else if (rule.label === 'pattern') {
            rules.push({ pattern: new RegExp(rule.value.replace(/^\/|\/$/g, '')), message: rule.message, trigger: 'blur' });
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

/**
 * 处理文件上传：保留你的逻辑
 */
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

    if (uploadRes.data?.url) {
      // 核心保护2：上传时确保分组对象存在
      if (!formData[groupId]) formData[groupId] = {}; 
      formData[groupId][field.key] = uploadRes.data.url;
      fileList[groupId][field.key] = [{ url: uploadRes.data.url }];
      uploadFile.onSuccess();

      await nextTick();
      const currentForm = formRefs.value[groupId];
      if (currentForm) {
        await currentForm.validateField(field.key);
      }
    }
  } catch (error) {
    uploadFile.onError(error);
  }
};

/**
 * 处理文件移除：彻底解决undefined报错的核心修复
 */
const handleFileRemove = (file: any, field: ConfigForm.ConfigField, groupId: number) => {
  // 核心保护3：移除时强制确保分组对象存在（无论初始化是否完成）
  if (typeof formData[groupId] !== 'object' || formData[groupId] === null) {
    formData[groupId] = {}; // 即使未初始化，也强制创建空对象
  }
  // 安全赋值（此时formData[groupId]必然是对象，不会报错）
  formData[groupId][field.key] = ''; 
  fileList[groupId][field.key] = [];
};

/**
 * 处理表单提交：保留你的逻辑
 */
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
      ElMessage.success('配置保存成功');
  } catch (error: any) {
    ElMessage.error(`提交失败: ${error.message || '网络异常'}`);
  }
};

// 组件挂载后初始化
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
.preview-container {
  margin-top: 10px;
}
</style>
