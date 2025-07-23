// src/utils/websocket.ts
class WebSocketService {
  private static instance: WebSocketService;
  private ws: WebSocket | null = null;
  private url: string;
  private retryCount = 0; // 重连次数
  private maxRetry = 5; // 最大重连次数
  private listeners: Record<string, Array<(data: any) => void>> = {}; // 消息监听器

  // 单例模式，确保全局唯一连接
  private constructor() {
    // 根据环境切换ws/wss
    this.url = import.meta.env.DEV
      ? 'ws://127.0.0.1:2346'
      : 'wss://your-domain.com:2346';
    this.init();
  }

  // 获取实例
  public static getInstance(): WebSocketService {
    if (!WebSocketService.instance) {
      WebSocketService.instance = new WebSocketService();
    }
    return WebSocketService.instance;
  }

  // 初始化连接
  private init() {
    this.ws = new WebSocket(this.url);

    // 连接成功
    this.ws.onopen = () => {
      console.log('WebSocket连接成功');
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
    if (this.retryCount < this.maxRetry) {
      this.retryCount++;
      setTimeout(() => {
        console.log(`第${this.retryCount}次重连...`);
        this.init();
      }, 3000 * this.retryCount); // 指数退避重连
    } else {
      console.error('超过最大重连次数，请刷新页面');
    }
  }

  // 发送消息（未来扩展用，支持向后端发送消息）
  public send(data: any) {
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
}

// 导出单例实例
export const wsService = WebSocketService.getInstance();
