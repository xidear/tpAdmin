import { Upload } from "@/api/interface";
import { PORT1 } from "@/api/config/servicePort";
import http from "@/api";


// 图片上传
export const uploadImg = (params: FormData) => {
  return http.post<Upload.ResFileUrl>(PORT1 + `/upload/image`, params, { cancel: false });
};

// 视频上传
export const uploadVideo = (params: FormData) => {
  return http.post<Upload.ResFileUrl>(PORT1 + `/upload/video`, params, { cancel: false });
};


// 视频上传
export const uploadFile = (params: FormData) => {
  return http.post<Upload.ResFileUrl>(PORT1 + `/upload/file`, params, { cancel: false });
};
