// src/utils/websocket.ts
class WebSocketService {
  private static instance: WebSocketService;
  private ws: WebSocket | null = null;
  private url: string;
  private retryCount = 0; // 重连次数
  private maxRetry = 5; // 最大重连次数
  private listeners: Record<string, Array<(data: any) => void>> = {}; // 消息监听器
  private isEnabled: boolean; // 是否启用WebSocket

  // 单例模式，确保全局唯一连接
  private constructor() {
    // 从环境变量获取是否启用WebSocket
    this.isEnabled = import.meta.env.VITE_WS_ENABLED === 'true';
    // 根据当前页面协议和环境配置生成WebSocket URL
    this.url = this.generateWsUrl();
    
    // 如果启用，则初始化连接
    if (this.isEnabled) {
      this.init();
    } else {
      console.log('WebSocket已通过配置禁用，不主动建立连接');
    }
  }

  // 获取实例
  public static getInstance(): WebSocketService {
    if (!WebSocketService.instance) {
      WebSocketService.instance = new WebSocketService();
    }
    return WebSocketService.instance;
  }

  // 根据当前协议和环境配置生成WebSocket URL
  private generateWsUrl(): string {
    // 从环境变量获取配置
    const wsHost = import.meta.env.VITE_WS_HOST;
    const wsPort = import.meta.env.VITE_WS_PORT;
    
    // 验证必要的环境变量
    if (!wsHost) {
      console.error('请在环境配置文件中设置VITE_WS_HOST');
      // 提供默认值作为 fallback
      return import.meta.env.DEV 
        ? 'ws://127.0.0.1:2346' 
        : 'wss://your-domain.com:2346';
    }

    // 根据当前页面协议选择ws或wss
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    
    // 构建URL（如果端口存在则添加，否则省略）
    return `${protocol}//${wsHost}${wsPort ? `:${wsPort}` : ''}`;
  }

  // 初始化连接
  private init() {
    if (!this.isEnabled) {
      console.log('WebSocket已禁用，不初始化连接');
      return;
    }

    this.ws = new WebSocket(this.url);

    // 连接成功
    this.ws.onopen = () => {
      console.log(`WebSocket连接成功: ${this.url}`);
      this.retryCount = 0; // 重置重连次数
    };

    // 接收消息
    this.ws.onmessage = (event) => {
      try {
        const data = JSON.parse(event.data);
        this.handleMessage(data); // 分发消息
      } catch (e) {
        console.error('WebSocket消息解析失败', e);
      }
    };

    // 连接错误
    this.ws.onerror = (error) => {
      console.error('WebSocket错误', error);
      this.reconnect();
    };

    // 连接关闭
    this.ws.onclose = () => {
      console.log('WebSocket连接关闭');
      this.reconnect();
    };
  }

  // 重连机制
  private reconnect() {
    if (!this.isEnabled) {
      console.log('WebSocket已禁用，不进行重连');
      return;
    }

    if (this.retryCount < this.maxRetry) {
      this.retryCount++;
      setTimeout(() => {
        console.log(`第${this.retryCount}次重连...`);
        // 重连时重新生成URL，应对可能的环境变化
        this.url = this.generateWsUrl();
        this.init();
      }, 3000 * this.retryCount); // 指数退避重连
    } else {
      console.error('超过最大重连次数，请刷新页面');
    }
  }

  // 手动连接WebSocket（当开关关闭时可手动调用）
  public connect() {
    if (this.ws && this.ws.readyState === WebSocket.OPEN) {
      console.log('WebSocket已处于连接状态');
      return;
    }
    
    // 允许手动连接时启用WebSocket
    this.isEnabled = true;
    console.log('手动启动WebSocket连接...');
    this.init();
  }

  // 发送消息
  public send(data: any) {
    if (!this.isEnabled) {
      console.error('WebSocket已禁用，无法发送消息');
      return;
    }

    if (this.ws && this.ws.readyState === WebSocket.OPEN) {
      this.ws.send(JSON.stringify(data));
    } else {
      console.error('WebSocket未连接，无法发送消息');
    }
  }

  // 注册消息监听器（按类型区分）
  public on(type: string, callback: (data: any) => void) {
    if (!this.listeners[type]) {
      this.listeners[type] = [];
    }
    this.listeners[type].push(callback);
  }

  // 移除监听器
  public off(type: string, callback?: (data: any) => void) {
    if (!this.listeners[type]) return;
    if (callback) {
      this.listeners[type] = this.listeners[type].filter(cb => cb !== callback);
    } else {
      delete this.listeners[type];
    }
  }

  // 消息分发（支持任意类型消息）
  private handleMessage(data: any) {
    const { type } = data;
    if (this.listeners[type]) {
      this.listeners[type].forEach(callback => callback(data));
    }
    // 通用监听器（监听所有消息）
    if (this.listeners['*']) {
      this.listeners['*'].forEach(callback => callback(data));
    }
  }

  // 手动关闭连接
  public close() {
    if (this.ws) {
      this.ws.close();
      this.ws = null;
    }
  }

  // 检查WebSocket是否启用
  public isWebSocketEnabled() {
    return this.isEnabled;
  }
}

// 导出单例实例
export const wsService = WebSocketService.getInstance();
