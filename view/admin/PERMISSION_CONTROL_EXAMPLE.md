# 权限控制实现示例

## 概述
本文档展示了如何在首页仪表板中实现真正的权限控制，确保用户只能看到他们有权限访问的数据和功能。

## 权限控制原理

### 1. 权限检查逻辑
```typescript
const hasPermission = (permission: string) => {
  // 获取当前路由名称
  const currentRouteName = authStore.routeName;
  
  // 获取按钮权限列表
  const buttonList = authStore.authButtonListGet;
  
  // 检查是否有对应的按钮权限
  if (buttonList[permission] && buttonList[permission].length > 0) {
    return true;
  }
  
  // 如果没有按钮权限，检查菜单权限
  const menuList = authStore.authMenuListGet;
  const hasMenuPermission = checkMenuPermission(menuList, permission);
  
  return hasMenuPermission;
};
```

### 2. 菜单权限检查
```typescript
const checkMenuPermission = (menuList: any[], permission: string): boolean => {
  for (const menu of menuList) {
    // 检查当前菜单是否匹配权限
    if (menu.meta?.permission === permission || menu.path.includes(permission.split(':')[0])) {
      return true;
    }
    // 递归检查子菜单
    if (menu.children && menu.children.length > 0) {
      if (checkMenuPermission(menu.children, permission)) {
        return true;
      }
    }
  }
  return false;
};
```

## 权限标识定义

### 1. 基础权限
- `task:read` - 任务读取权限
- `log:read` - 日志读取权限
- `department:read` - 部门读取权限
- `role:read` - 角色读取权限
- `admin:read` - 管理员读取权限

### 2. 权限组合检查
```typescript
// 检查是否有任何权限
const hasAnyPermission = (): boolean => {
  return hasPermission('task:read') || 
         hasPermission('log:read') || 
         hasPermission('department:read') || 
         hasPermission('role:read') || 
         hasPermission('admin:read');
};
```

## 实际应用场景

### 1. 统计卡片权限控制
```vue
<!-- 部门统计卡片 - 只有有部门权限的用户才能看到 -->
<el-col :span="6" v-if="hasPermission('department:read')">
  <div class="stat-card card warning">
    <div class="stat-icon">
      <el-icon :size="24">
        <Setting />
      </el-icon>
    </div>
    <div class="stat-content">
      <div class="stat-number">{{ statsData.departmentCount }}</div>
      <div class="stat-label">部门数量</div>
    </div>
  </div>
</el-col>
```

### 2. 图表权限控制
```vue
<!-- 部门结构图 - 只有有部门权限的用户才能看到 -->
<el-col :span="12" v-if="hasPermission('department:read')">
  <div class="chart-card card">
    <div class="chart-header">
      <h3>部门结构图</h3>
      <el-button type="primary" size="small" @click="refreshDepartmentData">
        刷新数据
      </el-button>
    </div>
    <div ref="departmentTreeChart" class="chart-container"></div>
  </div>
</el-col>
```

### 3. 表格权限控制
```vue
<!-- 任务列表 - 只有有任务权限的用户才能看到 -->
<div class="table-section" v-if="hasPermission('task:read')">
  <div class="chart-card card">
    <div class="chart-header">
      <h3>最近任务列表</h3>
      <el-button type="primary" size="small" @click="refreshTaskTable">
        刷新数据
      </el-button>
    </div>
    <el-table :data="recentTasks" style="width: 100%" v-loading="tableLoading">
      <!-- 表格列定义 -->
    </el-table>
  </div>
</div>
```

## 无权限时的处理

### 1. 无权限提示
```vue
<!-- 无权限提示 -->
<el-col :span="24" v-if="!hasAnyPermission()">
  <div class="no-permission-card card">
    <el-empty description="暂无数据查看权限" />
  </div>
</el-col>
```

### 2. 样式定义
```scss
.no-permission-card {
  padding: 40px;
  text-align: center;
  
  .el-empty {
    .el-empty__description {
      color: #909399;
      font-size: 14px;
    }
  }
}
```

## 数据获取权限控制

### 1. 统计数据处理
```typescript
// 获取部门统计 - 只有有权限的用户才会获取数据
if (hasPermission('department:read')) {
  try {
    const deptResponse = await getDepartmentTreeApi();
    if (deptResponse.data) {
      statsData.value.departmentCount = deptResponse.data.length;
    }
  } catch (error) {
    console.error('获取部门统计失败:', error);
  }
}
```

### 2. 图表初始化权限控制
```typescript
// 初始化部门结构图 - 只有有权限的用户才会初始化
const initDepartmentTreeChart = async () => {
  if (!departmentTreeChart.value || !hasPermission('department:read')) return;
  
  try {
    // 获取数据并初始化图表
    const response = await getDepartmentTreeApi();
    // ... 图表初始化逻辑
  } catch (error) {
    console.error('初始化部门结构图失败:', error);
    // 显示默认图表
  }
};
```

## 权限配置示例

### 1. 菜单权限配置
```typescript
// 在菜单配置中添加权限标识
{
  path: '/department',
  name: 'Department',
  component: 'Department',
  meta: {
    title: '部门管理',
    icon: 'Setting',
    permission: 'department:read' // 权限标识
  }
}
```

### 2. 按钮权限配置
```typescript
// 在权限配置中添加按钮权限
{
  'department:read': ['view', 'list'], // 部门查看权限
  'department:write': ['create', 'update', 'delete'], // 部门写权限
  'task:read': ['view', 'list'], // 任务查看权限
  'log:read': ['view', 'list'] // 日志查看权限
}
```

## 测试场景

### 1. 有部门权限的用户
- ✅ 可以看到部门统计卡片
- ✅ 可以看到部门结构图
- ✅ 可以刷新部门数据
- ❌ 看不到任务相关功能（如果没有任务权限）

### 2. 无部门权限的用户
- ❌ 看不到部门统计卡片
- ❌ 看不到部门结构图
- ❌ 无法获取部门数据
- ✅ 可以看到其他有权限的功能

### 3. 完全无权限的用户
- ❌ 看不到任何统计卡片
- ❌ 看不到任何图表
- ❌ 看不到任何表格
- ✅ 显示"暂无数据查看权限"提示

## 权限升级建议

### 1. 动态权限加载
```typescript
// 根据用户角色动态加载权限
const loadUserPermissions = async () => {
  const userRole = userStore.userInfo.role;
  const permissions = await getRolePermissions(userRole);
  authStore.setPermissions(permissions);
};
```

### 2. 权限缓存
```typescript
// 缓存权限检查结果，提高性能
const permissionCache = new Map();

const hasPermissionCached = (permission: string) => {
  if (permissionCache.has(permission)) {
    return permissionCache.get(permission);
  }
  
  const result = hasPermission(permission);
  permissionCache.set(permission, result);
  return result;
};
```

### 3. 权限变更监听
```typescript
// 监听权限变更，实时更新界面
watch(() => authStore.authButtonListGet, () => {
  // 清除权限缓存
  permissionCache.clear();
  // 重新检查权限
  updateUIByPermissions();
}, { deep: true });
```

## 总结

通过实现真正的权限控制，我们确保了：

1. **安全性**: 用户只能看到他们有权限访问的数据
2. **用户体验**: 无权限时显示友好的提示信息
3. **性能优化**: 只获取有权限的数据，避免不必要的API调用
4. **维护性**: 权限逻辑集中管理，易于维护和扩展

这种权限控制机制可以确保系统的安全性和用户体验，同时为后续的权限管理提供了良好的基础。
