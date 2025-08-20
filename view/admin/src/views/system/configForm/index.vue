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
          <!-- 文本输入框（type:1/5/6/7） -->
          <el-input
            v-if="[1,5,6,7].includes(field.type)"
            v-model.trim="formData[group.group_id][field.key]"
            :placeholder="field.placeholder || `请输入${field.label}`"
            :type="field.type === 6 ? 'email' : field.type === 5 ? 'url' : 'text'"
            clearable
            @update:model-value="(val) => formData[group.group_id][field.key] = val"
          />

          <!-- 多行文本（type:2） -->
          <el-input
            v-else-if="field.type === 2"
            v-model.trim="formData[group.group_id][field.key]"
            :placeholder="field.placeholder || `请输入${field.label}`"
            type="textarea"
            :rows="4"
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

          <!-- 密码输入框（type:4） -->
          <el-input
            v-else-if="field.type === 4"
            v-model.trim="formData[group.group_id][field.key]"
            :placeholder="field.placeholder || `请输入${field.label}`"
            type="password"
            show-password
            clearable
            @update:model-value="(val) => formData[group.group_id][field.key] = val"
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

          <!-- 单选框组（type:11） -->
          <el-radio-group
            v-else-if="field.type === 11"
            v-model="formData[group.group_id][field.key]"
            @update:model-value="(val) => formData[group.group_id][field.key] = val"
          >
            <el-radio
              v-for="option in field.options"
              :key="option.key || option.value"
              :label="option.key || option.value"
            >
              {{ option.value || option.label }}
            </el-radio>
          </el-radio-group>

          <!-- 复选框组（type:12） -->
          <el-checkbox-group
            v-else-if="field.type === 12"
            v-model="formData[group.group_id][field.key]"
            @update:model-value="(val) => formData[group.group_id][field.key] = val"
          >
            <el-checkbox
              v-for="option in field.options"
              :key="option.key || option.value"
              :label="option.key || option.value"
            >
              {{ option.value || option.label }}
            </el-checkbox>
          </el-checkbox-group>

          <!-- 下拉选择（type:13） -->
          <el-select
            v-else-if="field.type === 13"
            v-model="formData[group.group_id][field.key]"
            :placeholder="field.placeholder || `请选择${field.label}`"
            clearable
            @update:model-value="(val) => formData[group.group_id][field.key] = val"
          >
            <el-option
              v-for="option in field.options"
              :key="option.key || option.value"
              :label="option.value || option.label"
              :value="option.key || option.value"
            />
          </el-select>

          <!-- 多选下拉（type:14） -->
          <el-select
            v-else-if="field.type === 14"
            v-model="formData[group.group_id][field.key]"
            :placeholder="field.placeholder || `请选择${field.label}`"
            multiple
            clearable
            @update:model-value="(val) => formData[group.group_id][field.key] = val"
          >
            <el-option
              v-for="option in field.options"
              :key="option.key || option.value"
              :label="option.value || option.label"
              :value="option.key || option.value"
            />
          </el-select>

          <!-- 单图上传（type:20，图片） -->
          <div v-else-if="field.type === 20">
            <ImageSelector
              :multiple="false"
              @change="(images) => handleImageChange(images, field, group.group_id, false)"
            />

            <!-- 预览区域 -->
            <div v-if="formData[group.group_id]?.[field.key]" class="preview-container">
              <el-image
                :src="formData[group.group_id][field.key]"
                fit="contain"
                style="width: 200px; height: 150px; margin-top: 10px"
              />
            </div>
          </div>

          <!-- 多图上传（type:21，图片） -->
          <div v-else-if="field.type === 21">
            <ImageSelector
              :multiple="true"
              @change="(images) => handleImageChange(images, field, group.group_id, true)"
            />
            <!-- 预览区域 -->
            <div v-if="Array.isArray(formData[group.group_id]?.[field.key]) && formData[group.group_id][field.key].length" class="preview-container">
              <div class="image-grid" style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;">
                <el-image
                  v-for="(imgUrl, idx) in formData[group.group_id][field.key]"
                  :key="idx"
                  :src="imgUrl"
                  fit="cover"
                  style="width: 100px; height: 100px; border-radius: 4px;"
                />
              </div>
            </div>
          </div>

          <!-- 视频上传（type:22，保持原生上传） -->
          <div v-else-if="field.type === 22">
            <el-upload
              :file-list="fileList[group.group_id]?.[field.key] || []"
              :accept="field.accept"
              :multiple="false"
              :auto-upload="true"
              :http-request="(uploadFile) => handleHttpUpload(uploadFile, field, group.group_id)"
              class="upload-control"
              @remove="(file) => handleFileRemove(file, field, group.group_id)"
              @update:model-value="(val) => formData[group.group_id][field.key] = val"
            >
              <el-button size="small" type="primary">点击上传视频</el-button>
            </el-upload>
            <!-- 预览区域 -->
            <div v-if="formData[group.group_id]?.[field.key]" class="preview-container">
              <video
                :src="formData[group.group_id][field.key]"
                controls
                style="width: 200px; height: 150px; margin-top: 10px"
              ></video>
            </div>
          </div>

          <!-- JSON编辑器（type:32） -->
          <div v-else-if="field.type === 32">
            <div class="json-editor-container">
              <div class="json-editor-header">
                <span class="json-editor-title">JSON 配置编辑器</span>
                <div class="json-editor-actions">
                  <el-dropdown @command="(template) => applyJsonTemplate(template, field, group.group_id)">
                    <el-button size="small" type="info" icon="Document">
                      模板
                    </el-button>
                    <template #dropdown>
                      <el-dropdown-menu>
                        <el-dropdown-item command="watermark">水印配置模板</el-dropdown-item>
                        <el-dropdown-item command="storage">存储配置模板</el-dropdown-item>
                        <el-dropdown-item command="upload">上传配置模板</el-dropdown-item>
                      </el-dropdown-menu>
                    </template>
                  </el-dropdown>
                  <el-button 
                    size="small" 
                    type="primary" 
                    @click="formatJson(field, group.group_id)"
                    icon="MagicStick"
                  >
                    格式化
                  </el-button>
                  <el-button 
                    size="small" 
                    @click="validateJson(field, group.group_id)"
                    icon="Check"
                  >
                    验证
                  </el-button>
                </div>
              </div>
              <el-input
                v-model="formData[group.group_id][field.key]"
                :placeholder="field.placeholder || `请输入${field.label}的JSON配置`"
                type="textarea"
                :rows="12"
                clearable
                class="json-textarea"
                @update:model-value="(val) => handleJsonChange(val, field, group.group_id)"
              />
              <div class="json-editor-footer">
                <el-text type="info" size="small">
                  💡 支持 JSON 格式，点击"格式化"按钮可自动美化代码
                </el-text>
                <el-text v-if="jsonErrors[`${group.group_id}-${field.key}`]" type="danger" size="small">
                  ❌ {{ jsonErrors[`${group.group_id}-${field.key}`] }}
                </el-text>
              </div>
            </div>
          </div>

          <!-- 键值对配置（type:40） -->
          <div v-else-if="field.type === 40">
            <div class="key-value-editor">
              <div 
                v-for="(item, index) in getKeyValueArray(group.group_id, field.key)"
                :key="`${field.key}-${index}`"
                class="key-value-row"
              >
                <el-input
                  v-model="item.key"
                  placeholder="键名"
                  style="width: 200px; margin-right: 8px;"
                  @update:model-value="() => updateKeyValueData(group.group_id, field.key)"
                />
                <el-input
                  v-model="item.value"
                  placeholder="键值"
                  style="flex: 1; margin-right: 8px;"
                  @update:model-value="() => updateKeyValueData(group.group_id, field.key)"
                />
                <el-button 
                  type="danger" 
                  size="small" 
                  @click="removeKeyValueRow(group.group_id, field.key, index)"
                  icon="Delete"
                />
              </div>
              <el-button 
                type="primary" 
                size="small" 
                @click="addKeyValueRow(group.group_id, field.key)"
                icon="Plus"
                style="margin-top: 8px;"
              >
                添加配置项
              </el-button>
            </div>
            <div class="form-tip">
              <el-text type="info" size="small">
                {{ field.label }} 的配置参数
              </el-text>
            </div>
          </div>

          <div v-else>不支持的字段类型（类型：{{ field.type }}）</div>
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
import ImageSelector from '@/components/ImageSelector/index.vue';

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
// 键值对临时存储
const keyValueData = reactive<Record<string, Array<{key: string, value: string}>>>({});
// JSON 格式化错误信息
const jsonErrors = reactive<Record<string, string>>({});

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
        } else if (field.type === 12 || field.type === 14 || field.type === 21) {
          // 复选框组、多选下拉、多图上传 - 需要数组类型
          obj[field.key] = field.value ? (Array.isArray(field.value) ? field.value : [field.value]) : [];
        } else if (field.type === 11 || field.type === 13) {
          // 单选框组、下拉选择 - 使用选项中的第一个值或字段值
          obj[field.key] = field.value || field.options[0]?.value || '';
        } else if (field.type === 32) {
          // JSON编辑器 - 如果是对象则转为字符串
          obj[field.key] = typeof field.value === 'object' ? JSON.stringify(field.value, null, 2) : (field.value || '{}');
        } else if (field.type === 40) {
          // 键值对配置 - 转为对象或保持原样
          obj[field.key] = field.value || {};
        } else {
          obj[field.key] = field.value || '';
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
 * 处理图片选择器返回
 * images: ImageSelector 返回的图片对象数组（包含url等）
 * isMultiple: 是否多选
 */
const handleImageChange = (
  images: any[],
  field: ConfigForm.ConfigField,
  groupId: number,
  isMultiple: boolean
) => {
  if (!formData[groupId]) formData[groupId] = {};
  if (isMultiple) {
    formData[groupId][field.key] = (images || []).map((img: any) => img.url || img);
  } else {
    const first = Array.isArray(images) ? images[0] : images;
    formData[groupId][field.key] = first ? (first.url || first) : '';
  }
};

/**
 * 处理JSON编辑器变更
 */
const handleJsonChange = (value: string, field: any, groupId: number) => {
  if (!formData[groupId]) formData[groupId] = {};
  formData[groupId][field.key] = value;
  
  // 可以添加JSON格式验证
  try {
    if (value.trim()) {
      JSON.parse(value);
    }
  } catch (error) {
    // JSON格式错误，可以显示提示
    console.warn('JSON格式错误:', error);
  }
};

/**
 * 格式化JSON
 */
const formatJson = (field: ConfigForm.ConfigField, groupId: number) => {
  const dataKey = `${groupId}-${field.key}`;
  if (!formData[groupId]) formData[groupId] = {};
  const jsonString = formData[groupId][field.key];

  if (jsonString.trim()) {
    try {
      const parsed = JSON.parse(jsonString);
      formData[groupId][field.key] = JSON.stringify(parsed, null, 2);
      jsonErrors[dataKey] = ''; // 清除错误
    } catch (error) {
      jsonErrors[dataKey] = `JSON 格式错误: ${error.message}`;
    }
  }
};

/**
 * 验证JSON
 */
const validateJson = (field: ConfigForm.ConfigField, groupId: number) => {
  const dataKey = `${groupId}-${field.key}`;
  if (!formData[groupId]) formData[groupId] = {};
  const jsonString = formData[groupId][field.key];

  if (jsonString.trim()) {
    try {
      JSON.parse(jsonString);
      jsonErrors[dataKey] = ''; // 清除错误
      ElMessage.success('JSON 格式正确');
    } catch (error) {
      jsonErrors[dataKey] = `JSON 格式错误: ${error.message}`;
      ElMessage.error('JSON 格式错误');
    }
  } else {
    jsonErrors[dataKey] = ''; // 清除错误
  }
};

/**
 * 应用JSON模板
 */
const applyJsonTemplate = (template: string, field: ConfigForm.ConfigField, groupId: number) => {
  const dataKey = `${groupId}-${field.key}`;
  if (!formData[groupId]) formData[groupId] = {};

  let templateJson: any;
  
  // 根据模板类型生成不同的JSON
  switch (template) {
    case 'watermark':
      templateJson = {
        "text": "水印文字",
        "image": "/path/to/watermark.png",
        "position": "bottom-right",
        "opacity": 0.8,
        "fontSize": 16,
        "fontColor": "#ffffff",
        "backgroundColor": "#000000"
      };
      break;
    case 'storage':
      templateJson = {
        "type": "local",
        "path": "/storage/uploads",
        "url": "/uploads",
        "maxSize": "10485760",
        "allowedTypes": ["jpg", "png", "gif", "pdf"],
        "compression": {
          "enabled": true,
          "quality": 80
        }
      };
      break;
    case 'upload':
      templateJson = {
        "maxFileSize": "10485760",
        "allowedExtensions": "jpg,jpeg,png,gif,pdf,doc,docx",
        "imageQuality": 80,
        "watermarkEnabled": false,
        "watermarkConfig": {
          "text": "",
          "image": "",
          "position": "bottom-right"
        }
      };
      break;
    default:
      ElMessage.error('未知的模板类型');
      return;
  }

  // 应用模板到当前字段
  formData[groupId][field.key] = JSON.stringify(templateJson, null, 2);
  jsonErrors[dataKey] = ''; // 清除错误
  ElMessage.success('模板已应用');
};

/**
 * 获取键值对数组
 */
const getKeyValueArray = (groupId: number, fieldKey: string) => {
  const dataKey = `${groupId}-${fieldKey}`;
  if (!keyValueData[dataKey]) {
    const data = formData[groupId]?.[fieldKey] || {};
    keyValueData[dataKey] = Object.entries(data).map(([key, value]) => ({ key, value: String(value) }));
    
    // 如果没有数据，添加一个空行
    if (keyValueData[dataKey].length === 0) {
      keyValueData[dataKey] = [{ key: '', value: '' }];
    }
  }
  return keyValueData[dataKey];
};

/**
 * 更新键值对数据到表单
 */
const updateKeyValueData = (groupId: number, fieldKey: string) => {
  const dataKey = `${groupId}-${fieldKey}`;
  if (!formData[groupId]) formData[groupId] = {};
  
  const kvArray = keyValueData[dataKey] || [];
  const result: Record<string, any> = {};
  
  kvArray.forEach(item => {
    if (item.key.trim()) {
      result[item.key] = item.value;
    }
  });
  
  formData[groupId][fieldKey] = result;
};

/**
 * 添加键值对行
 */
const addKeyValueRow = (groupId: number, fieldKey: string) => {
  const dataKey = `${groupId}-${fieldKey}`;
  if (!keyValueData[dataKey]) {
    keyValueData[dataKey] = [];
  }
  keyValueData[dataKey].push({ key: '', value: '' });
};

/**
 * 删除键值对行
 */
const removeKeyValueRow = (groupId: number, fieldKey: string, index: number) => {
  const dataKey = `${groupId}-${fieldKey}`;
  if (keyValueData[dataKey]) {
    keyValueData[dataKey].splice(index, 1);
    updateKeyValueData(groupId, fieldKey);
  }
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
    
    // 处理特殊字段格式
    const submitData = JSON.parse(JSON.stringify(formData[groupId]));
    
    // 调试信息：打印提交的数据
    console.log('提交的数据:', submitData);
    
    // 处理JSON字段：尝试解析JSON字符串
    Object.keys(submitData).forEach(key => {
      const value = submitData[key];
      if (typeof value === 'string' && (value.startsWith('{') || value.startsWith('['))) {
        try {
          submitData[key] = JSON.parse(value);
        } catch (error) {
          // 如果JSON解析失败，保持原字符串
        }
      }
    });
    
    const submitParams: ConfigForm.SaveConfigParams = {
      group_id: Number(groupId),
      fields: submitData
    };

    console.log('最终提交参数:', submitParams);

    const saveRes = await saveConfigFormApi(submitParams);
      ElMessage.success('配置保存成功');
  } catch (error: any) {
    console.error('提交错误:', error);
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

.key-value-editor {
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  padding: 16px;
  background: #fafafa;
}

.key-value-row {
  display: flex;
  align-items: center;
  margin-bottom: 8px;
}

.key-value-row:last-of-type {
  margin-bottom: 0;
}

.form-tip {
  margin-top: 4px;
}

.json-editor-container {
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  padding: 16px;
  background: #fafafa;
  position: relative;
}

.json-editor-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
  padding-bottom: 8px;
  border-bottom: 1px solid #eee;
}

.json-editor-title {
  font-size: 16px;
  font-weight: bold;
  color: #333;
}

.json-editor-actions {
  display: flex;
  gap: 8px;
}

.json-textarea {
  font-family: 'Consolas', 'Monaco', 'Andale Mono', 'Ubuntu Mono', 'Monospace', monospace;
  font-size: 14px;
  line-height: 1.5;
  white-space: pre-wrap;
  word-break: break-all;
  padding: 10px;
  border: 1px solid #dcdfe6;
  border-radius: 4px;
  background-color: #fff;
  color: #333;
  min-height: 150px;
  resize: vertical;
}

.json-editor-footer {
  margin-top: 10px;
  padding-top: 8px;
  border-top: 1px solid #eee;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 12px;
  color: #909399;
}

.json-editor-footer .el-text {
  margin-left: 10px;
}
</style>
