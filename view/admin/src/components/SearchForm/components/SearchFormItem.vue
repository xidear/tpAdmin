<template>
  <component
    :is="column.search?.render ?? `el-${column.search?.el}`"
    v-bind="{
      ...handleSearchProps,
      ...placeholder,
      searchParam: _searchParam,
      clearable,
      remote: column.search?.props?.remote,
      'remote-method': column.search?.remoteMethod ? handleRemoteSearch : undefined,
      loading: remoteLoading || isLoadingMore,
      filterable: column.search?.props?.filterable ?? (column.search?.props?.remote ? true : false),
      listRows: column.search?.props?.listRows || 10 // 传递每页条数配置
    }"
    v-model.trim="_searchParam[column.search?.key ?? handleProp(column.prop!)]"
    :data="column.search?.el === 'tree-select' ? options : []"
    :options="['cascader', 'select-v2', 'select'].includes(column.search?.el!) ? options : []"
    ref="searchComponentRef"
  >
    <!-- 选择框选项 -->
    <template v-if="column.search?.el === 'cascader'" #default="{ data }">
      <span>{{ data[fieldNames.label] }}</span>
    </template>

    <template v-if="column.search?.el === 'select'">
      <component
        :is="`el-option`"
        v-for="(col, index) in options"
        :key="index"
        :label="col[fieldNames.label]"
        :value="col[fieldNames.value]"
      ></component>

      <!-- 加载更多按钮区域 -->
      <div v-if="hasMoreData && !isLoadingMore" class="load-more-trigger">
        <el-button
          type="info"
          size="small"
          @click="loadMore"
        >
          加载更多
        </el-button>
      </div>
      <div v-if="isLoadingMore" class="loading-more">
        <el-icon size="16"><Loading /></el-icon>
        <span class="text">加载中...</span>
      </div>
      <div v-if="!hasMoreData && options.length > 0" class="no-more-data">
        <span>已加载全部数据</span>
      </div>
    </template>

    <slot v-else></slot>
  </component>
</template>

<script setup lang="ts" name="SearchFormItem">
import { computed, inject, ref, watch } from "vue";
import { handleProp } from "@/utils";
import { ColumnProps, EnumProps } from "@/components/ProTable/interface";
import { ElMessage } from "element-plus";
import { Loading } from "@element-plus/icons-vue";

interface SearchFormItem {
  column: ColumnProps;
  searchParam: { [key: string]: any };
}
const props = defineProps<SearchFormItem>();

// 分页相关状态
const options = ref<EnumProps[]>([]);
const page = ref(1);
const total = ref(0);
const remoteLoading = ref(false);
const isLoadingMore = ref(false);
const lastQuery = ref("");
const searchComponentRef = ref<any>(null);

// 从配置获取每页条数
const listRows = computed(() => {
  return props.column.search?.props?.listRows || 10;
});

// 计算是否还有更多数据
const hasMoreData = computed(() => {
  return options.value.length < total.value;
});

// 字段名映射
const fieldNames = computed(() => ({
  label: props.column.fieldNames?.label ?? "label",
  value: props.column.fieldNames?.value ?? "value",
  children: props.column.fieldNames?.children ?? "children"
}));

// 注入枚举映射
const enumMap = inject("enumMap", ref(new Map()));
const _searchParam = computed(() => props.searchParam);

// 处理远程搜索
const handleRemoteSearch = async (query: string, isLoadMore = false) => {
  if (typeof props.column.search?.remoteMethod !== "function") return;

  // 计算当前页码
  const currentPage = isLoadMore ? page.value + 1 : 1;

  // 更新加载状态
  if (isLoadMore) {
    isLoadingMore.value = true;
  } else {
    remoteLoading.value = true;
    lastQuery.value = query;
  }

  try {
    // 调用远程方法，传递正确参数
    const data = await props.column.search.remoteMethod(
      query,
      currentPage,
      listRows.value
    );

    // 更新总条数
    total.value = data.total || 0;

    // 更新选项数据
    if (isLoadMore) {
      options.value = [...options.value, ...(data.list || [])];
      page.value = currentPage;
    } else {
      options.value = data.list || [];
      page.value = 1;
    }
  } catch (error) {
    console.error("远程搜索失败:", error);
    ElMessage.error("搜索失败，请重试");
  } finally {
    remoteLoading.value = false;
    isLoadingMore.value = false;
  }
};

// 加载更多
const loadMore = () => {
  if (hasMoreData.value && !isLoadingMore.value && !remoteLoading.value) {
    handleRemoteSearch(lastQuery.value, true);
  }
};

// 监听列配置变化，重新初始化
watch(
  () => props.column,
  (newColumn) => {
    const searchProps = newColumn.search;
    if (searchProps?.props?.remote && typeof searchProps.remoteMethod === "function") {
      // 初始加载
      if (searchProps.initialLoad !== false) {
        handleRemoteSearch("");
      } else {
        options.value = [];
        total.value = 0;
        page.value = 1;
      }
    } else {
      // 非远程搜索，从枚举获取数据
      options.value = enumMap.value.get(newColumn.prop) || [];
    }
  },
  { immediate: true, deep: true }
);

// 其他计算属性
const handleSearchProps = computed(() => {
  const label = fieldNames.value.label;
  const value = fieldNames.value.value;
  const children = fieldNames.value.children;
  const searchEl = props.column.search?.el;
  let searchProps = props.column.search?.props ?? {};

  if (searchEl === "tree-select") {
    searchProps = { ...searchProps, props: { ...searchProps, label, children }, nodeKey: value };
  }
  if (searchEl === "cascader") {
    searchProps = { ...searchProps, props: { ...searchProps, label, value, children } };
  }
  if (["select", "select-v2"].includes(searchEl!) && props.column.fieldNames) {
    searchProps = { ...searchProps, props: { ...searchProps, label, value } };
  }
  return searchProps;
});

const placeholder = computed(() => {
  const search = props.column.search;
  if (["datetimerange", "daterange", "monthrange"].includes(search?.props?.type) || search?.props?.isRange) {
    return {
      rangeSeparator: search?.props?.rangeSeparator ?? "至",
      startPlaceholder: search?.props?.startPlaceholder ?? "开始时间",
      endPlaceholder: search?.props?.endPlaceholder ?? "结束时间"
    };
  }
  const placeholder = search?.props?.placeholder ?? (search?.el?.includes("input") ? "请输入" : "请选择");
  return { placeholder };
});

const clearable = computed(() => {
  const search = props.column.search;
  return search?.props?.clearable ?? (search?.defaultValue == null || search?.defaultValue == undefined);
});
</script>

<style scoped>
.load-more-trigger {
  text-align: center;
  padding: 6px 0;
  border-top: 1px solid #eee;
  margin-top: 4px;
}

.loading-more {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 8px 0;
  font-size: 12px;
  color: #666;
}

.no-more-data {
  text-align: center;
  padding: 8px 0;
  font-size: 12px;
  color: #999;
}

.text {
  margin-left: 6px;
}
</style>
