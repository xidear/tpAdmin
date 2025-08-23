import { PORT1 } from "@/api/config/servicePort";
import type { BaseResponse } from "@/typings/global";
import http from "@/api";

export interface HomeStats {
  stats: {
    task: number | null;
    log: number | null;
    department: number | null;
    role: number | null;
  };
  details: {
    task: any[];
    log: any[];
    department: any[];
    role: any[];
  };
}

/**
 * 获取首页统计数据
 */
export const getHomeStatsApi = () => {
  return http.get<BaseResponse<HomeStats>>(
    PORT1 + `/home/getStats`,
    {},
    { loading: false }
  );
};
