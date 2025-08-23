# Vue.js SPA + ThinkPHP + Swoole 部署指南

## 问题描述

如果404错误，这是因为Vue.js SPA应用的前端路由没有正确处理。

## 解决方案

### 1. 修改主Nginx配置文件

将 `nginx.config.example` 中的配置应用到你的宝塔面板Nginx配置中：

```nginx
server {
    # ... 其他配置 ...
    
    # 静态文件处理规则 - 必须在反向代理之前
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot|webmanifest)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        try_files $uri =404;
    }

    # 处理 assets 目录 - 必须在反向代理之前
    location /assets/ {
        try_files $uri =404;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # 处理其他静态资源 - 必须在反向代理之前
    location ~* \.(html|txt|xml|json)$ {
        try_files $uri =404;
        add_header Cache-Control "no-cache";
    }

    # 处理Vue.js SPA路由 - 重要：必须在反向代理之前
    location /admin/ {
        try_files $uri $uri/ /admin/index.html;
    }
    
    # 引用反向代理规则
    include /www/server/panel/vhost/nginx/proxy/你的网站/*.conf;
    
    # ... 其他配置 ...
}
```

### 2. 创建反向代理配置文件


内容如下：

```nginx
# 转发API请求到Swoole服务
location ~ ^/(adminapi|api)/ {
    proxy_pass http://127.0.0.1:8080;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header REMOTE-HOST $remote_addr;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection $connection_upgrade;
    proxy_http_version 1.1;
    
    add_header X-Cache $upstream_cache_status;
    
    # 设置缓存
    set $static_file 0;
    if ( $uri ~* "\.(gif|png|jpg|css|js|woff|woff2)$" ) {
        set $static_file 1;
        expires 1m;
    }
    if ( $static_file = 0 ) {
        add_header Cache-Control no-cache;
    }
}

# 转发其他动态请求到Swoole服务
location / {
    # 排除静态文件
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot|webmanifest|html|txt|xml|json)$ {
        try_files $uri =404;
    }
    
    # 排除admin目录（由主配置处理）
    location /admin/ {
        try_files $uri $uri/ /admin/index.html;
    }
    
    # 转发到Swoole服务
    proxy_pass http://127.0.0.1:8080;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header REMOTE-HOST $remote_addr;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection $connection_upgrade;
    proxy_http_version 1.1;
    
    add_header X-Cache $upstream_cache_status;
    
    # 设置缓存
    set $static_file 0;
    if ( $uri ~* "\.(gif|png|jpg|css|js|woff|woff2)$" ) {
        set $static_file 1;
        expires 1m;
    }
    if ( $static_file = 0 ) {
        add_header Cache-Control no-cache;
    }
}
```

### 3. 配置顺序的重要性

**关键点**：静态文件处理和Vue.js SPA路由配置必须在反向代理配置之前，否则所有请求都会被转发到Swoole服务。

正确的配置顺序：
1. 静态文件处理规则
2. Vue.js SPA路由配置
3. 反向代理配置

### 4. 验证配置

1. 重启Nginx服务
2. 访问 `https://你的网站/admin/index.html` 应该能正常显示Vue.js应用
3. API请求（如 `/adminapi/home/login`）应该能正确转发到Swoole服务

### 5. 常见问题排查

- **仍然显示404**：检查静态文件路径是否正确，确保 `index.html` 文件存在
- **API请求失败**：检查Swoole服务是否正常运行在8080端口
- **静态资源加载失败**：检查assets目录路径和文件权限

### 6. 测试命令

```bash
# 测试静态文件访问
curl -I 你的网站/admin/index.html

# 测试API接口
curl 你的网站/api/test

# 检查Swoole服务状态
netstat -tlnp | grep 8080
```

## 总结

通过正确的Nginx配置，可以解决Vue.js SPA应用在ThinkPHP + Swoole环境下的路由问题。关键是确保静态文件处理和前端路由配置在反向代理之前，这样静态资源能够正确访问，而API请求能够正确转发到后端服务。
