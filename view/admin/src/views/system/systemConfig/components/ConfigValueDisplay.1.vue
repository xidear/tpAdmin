<template>
  <component
    :is="getDisplayComponent"
    :value="value"
    :options="options"
    :type="type"
  />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { EnumItem } from '@/typings/enum';

// 导入所有展示组件
// import TextDisplay from "@/views/system/systemConfig/components/displays/TextDisplay.vue";
// import ImageDisplay from '@/views/system/systemConfig/components/displays/ImageDisplay.vue';
// import ImagesDisplay from '@/views/system/systemConfig/components/displays/ImageDisplay.vue';
// import SwitchDisplay from '@/views/system/systemConfig/components/displays/SwitchDisplay.vue';
// import SelectDisplay from '@/views/system/systemConfig/components/displays/SelectDisplay.vue';
// import JsonDisplay from '@/views/system/systemConfig/components/displays/JsonDisplay.vue';
//
// import CheckboxDisplay from '@/views/system/systemConfig/components/displays/CheckboxDisplay.vue';
// import RichTextDisplay from '@/views/system/systemConfig/components/displays/RichTextDisplay.vue';
// import MarkdownDisplay from '@/views/system/systemConfig/components/displays/MarkdownDisplay.vue';
// import CodeDisplay from '@/views/system/systemConfig/components/displays/CodeDisplay.vue';
// import ColorDisplay from '@/views/system/systemConfig/components/displays/ColorDisplay.vue';
// import DateDisplay from '@/views/system/systemConfig/components/displays/DateDisplay.vue';
// import DateTimeDisplay from '@/views/system/systemConfig/components/displays/DateTimeDisplay.vue';
// import TimeDisplay from '@/views/system/systemConfig/components/displays/TimeDisplay.vue';
// import KeyValueDisplay from '@/views/system/systemConfig/components/displays/KeyValueDisplay.vue';
// import TableDisplay from '@/views/system/systemConfig/components/displays/TableDisplay.vue';
// import TreeDisplay from '@/views/system/systemConfig/components/displays/TreeDisplay.vue';
// import VideoDisplay from '@/views/system/systemConfig/components/displays/VideoDisplay.vue';
// import FileDisplay from '@/views/system/systemConfig/components/displays/FileDisplay.vue';
// import FilesDisplay from '@/views/system/systemConfig/components/displays/FilesDisplay.vue';
import UnknownDisplay from '@/views/system/systemConfig/components/displays/UnknownDisplay.vue';

// 接收的参数
const props = defineProps<{
  type: number; // 配置类型
  value: any;   // 配置值
  options?: EnumItem[]; // 选项配置（用于选择类组件）
}>();

// 类型映射表 - 对应ConfigType枚举
const typeComponentMap = {
  // 基础输入类
  // 1: TextDisplay,        // TEXT
  // 2: TextDisplay,        // TEXTAREA
  // 3: TextDisplay,        // NUMBER
  // 4: TextDisplay,        // PASSWORD
  // 5: TextDisplay,        // URL
  // 6: TextDisplay,        // EMAIL
  // 7: TextDisplay,        // PHONE
  //
  // // 选择类
  // 10: SwitchDisplay,     // SWITCH
  // 11: SelectDisplay,     // RADIO
  // 12: CheckboxDisplay,   // CHECKBOX
  // 13: SelectDisplay,     // SELECT
  // 14: CheckboxDisplay,   // MULTI_SELECT
  // 15: SelectDisplay,     // CASCADER
  //
  // // 媒体类
  // 20: ImageDisplay,      // IMAGE
  // 21: ImagesDisplay,     // IMAGES
  // 22: VideoDisplay,      // VIDEO
  // 23: FileDisplay,       // FILE
  // 24: FilesDisplay,      // FILES
  //
  // // 富文本与特殊格式
  // 30: RichTextDisplay,   // RICH_TEXT
  // 31: MarkdownDisplay,   // MARKDOWN
  // 32: JsonDisplay,       // JSON
  // 33: CodeDisplay,       // CODE
  // 34: ColorDisplay,      // COLOR
  // 35: DateDisplay,       // DATE
  // 36: DateTimeDisplay,   // DATETIME
  // 37: TimeDisplay,       // TIME
  //
  // // 结构化数据
  // 40: KeyValueDisplay,   // KEY_VALUE
  // 41: TableDisplay,      // TABLE
  // 42: TreeDisplay        // TREE
};

// 根据类型获取对应的展示组件
const getDisplayComponent = computed(() => {
  return typeComponentMap[props.type] || UnknownDisplay;
});
</script>
