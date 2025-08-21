<?php

namespace app\controller\admin;

use app\common\BaseController;
use app\model\File as FileModel;
use app\model\FileCategory as FileCategoryModel;
use app\model\FileTag as FileTagModel;
use app\model\FileTagRelation as FileTagRelationModel;
use think\App;
use think\Response;

class File extends BaseController
{
    protected FileModel $fileModel;
    protected FileCategoryModel $categoryModel;
    protected FileTagModel $tagModel;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->fileModel = new FileModel();
        $this->categoryModel = new FileCategoryModel();
        $this->tagModel = new FileTagModel();
    }

    /**
     * 获取文件列表（支持类型筛选）
     * @return Response
     */
    public function index(): Response
    {
        try {
            $params = request()->param();
            $keyword = $params['keyword'] ?? '';
            $categoryId = $params['category_id'] ?? 0;
            $fileType = $params['file_type'] ?? ''; // 支持：image, video, pdf, document, all
            $scope = $params['scope'] ?? 'all'; // own: 自己的, public: 公共的, all: 所有可见的
            
            $conditions = [];
            
            // 文件类型筛选
            if ($fileType && $fileType !== 'all') {
                if ($fileType === 'image') {
                    $conditions[] = ['mime_type', 'like', 'image/%'];
                } elseif ($fileType === 'video') {
                    $conditions[] = ['mime_type', 'like', 'video/%'];
                } elseif ($fileType === 'pdf') {
                    $conditions[] = ['mime_type', '=', 'application/pdf'];
                } elseif ($fileType === 'document') {
                    $conditions[] = ['mime_type', 'like', 'application/%'];
                    $conditions[] = ['mime_type', 'not like', 'application/pdf'];
                }
            }
            
            // 文件范围筛选
            $currentAdminId = $this->getCurrentAdminId();
            if ($scope === 'own') {
                // 只显示自己上传的文件
                $conditions[] = ['uploader_type', '=', 'admin'];
                $conditions[] = ['uploader_id', '=', $currentAdminId];
            } elseif ($scope === 'public') {
                // 只显示公共文件
                $conditions[] = ['storage_permission', '=', 'public'];
            } else {
                // 显示自己的所有文件 + 其他人的公共文件
                // 使用whereRaw构建OR条件，避免复杂的闭包嵌套
                $conditions[] = function($query) use ($currentAdminId) {
                    $query->where(function($subQuery) use ($currentAdminId) {
                        $subQuery->where('uploader_type', '=', 'admin')
                                ->where('uploader_id', '=', $currentAdminId);
                    })->whereOr(function($orQuery) {
                        $orQuery->where('storage_permission', '=', 'public');
                    });
                };
                
                // 备用方案1：如果闭包不工作，使用whereRaw构建OR条件
                // $conditions[] = function($query) use ($currentAdminId) {
                //     $query->whereRaw("(uploader_type = 'admin' AND uploader_id = {$currentAdminId}) OR storage_permission = 'public'");
                // };
                
                // 备用方案2：如果闭包不工作，使用数组条件（AND逻辑，不是OR）
                // $conditions[] = ['uploader_type', '=', 'admin'];
                // $conditions[] = ['uploader_id', '=', $currentAdminId];
                // $conditions[] = ['storage_permission', '=', 'public'];
            }
            
            // 关键词搜索 - 暂时注释掉，测试基本查询
            // if ($keyword) {
            //     $conditions[] = ['origin_name', 'like', "%{$keyword}%"];
            // }
            
            // 分类筛选 - 暂时注释掉，测试基本查询
            // if ($categoryId > 0) {
            //     $conditions[] = ['category_id', '=', $categoryId];
            // }
            

            
            // 使用BaseModel的fetchData方法
            $result = $this->fileModel->fetchData($conditions, [
                'orderBy' => 'created_at',
                'orderDir' => 'desc'
            ]);
            
            return $this->success($result);
            
        } catch (\Exception $e) {
            return $this->error('获取文件列表失败：' . $e->getMessage());
        }
    }

    /**
     * 获取文件详情
     * @param int $id
     * @return Response
     */
    public function read(int $id): Response
    {
        try {
            $file = $this->fileModel->fetchOne($id);
            if (!$file) {
                return $this->error('文件不存在');
            }
            
            // 权限检查：只能查看自己的文件或公共文件
            $currentAdminId = $this->getCurrentAdminId();
            $isOwnFile = ($file->uploader_type === 'admin' && $file->uploader_id === $currentAdminId);
            $isPublicFile = ($file->storage_permission === 'public');
            
            if (!$isOwnFile && !$isPublicFile) {
                return $this->error('无权限查看此文件');
            }
            
            return $this->success($file);
            
        } catch (\Exception $e) {
            return $this->error('获取文件详情失败：' . $e->getMessage());
        }
    }

    /**
     * 获取文件分类列表
     * @return Response
     */
    public function categories(): Response
    {
        try {
            $categories = $this->categoryModel->fetchAll([], ['order' => 'sort asc']);
            return $this->success($categories);
            
        } catch (\Exception $e) {
            return $this->error('获取分类列表失败：' . $e->getMessage());
        }
    }

    /**
     * 获取文件标签列表
     * @return Response
     */
    public function tags(): Response
    {
        try {
            $tags = $this->tagModel->fetchAll([], ['order' => 'sort asc']);
            return $this->success($tags);
            
        } catch (\Exception $e) {
            return $this->error('获取标签列表失败：' . $e->getMessage());
        }
    }

    /**
     * 创建文件分类
     * @return Response
     */
    public function createCategory(): Response
    {
        try {
            $params = request()->only(['name', 'parent_id', 'sort', 'description']);
            $params['sort'] = $params['sort'] ?? 0;
            
            $category = FileCategoryModel::create($params);
            return $this->success($category, '创建成功');
            
        } catch (\Exception $e) {
            return $this->error('创建分类失败：' . $e->getMessage());
        }
    }

    /**
     * 更新文件分类
     * @param int $id
     * @return Response
     */
    public function updateCategory(int $id): Response
    {
        try {
            $params = request()->only(['name', 'parent_id', 'sort', 'description']);
            
            $category = FileCategoryModel::find($id);
            if (!$category) {
                return $this->error('分类不存在');
            }
            
            $category->save($params);
            return $this->success($category, '更新成功');
            
        } catch (\Exception $e) {
            return $this->error('更新分类失败：' . $e->getMessage());
        }
    }

    /**
     * 删除文件分类
     * @param int $id
     * @return Response
     */
    public function deleteCategory(int $id): Response
    {
        try {
            $category = FileCategoryModel::find($id);
            if (!$category) {
                return $this->error('分类不存在');
            }
            
            // 检查是否有子分类
            $hasChildren = FileCategoryModel::where('parent_id', $id)->count();
            if ($hasChildren > 0) {
                return $this->error('该分类下有子分类，无法删除');
            }
            
            // 检查是否有文件使用此分类
            $hasFiles = FileModel::where('category_id', $id)->count();
            if ($hasFiles > 0) {
                return $this->error('该分类下有文件，无法删除');
            }
            
            $category->delete();
            return $this->success([], '删除成功');
            
        } catch (\Exception $e) {
            return $this->error('删除分类失败：' . $e->getMessage());
        }
    }

    /**
     * 创建文件标签
     * @return Response
     */
    public function createTag(): Response
    {
        try {
            $params = request()->only(['name', 'sort', 'description']);
            $params['sort'] = $params['sort'] ?? 0;
            
            $tag = FileTagModel::create($params);
            return $this->success($tag, '创建成功');
            
        } catch (\Exception $e) {
            return $this->error('创建标签失败：' . $e->getMessage());
        }
    }

    /**
     * 删除文件标签
     * @param int $id
     * @return Response
     */
    public function deleteTag(int $id): Response
    {
        try {
            $tag = FileTagModel::find($id);
            if (!$tag) {
                return $this->error('标签不存在');
            }
            
            // 删除标签关联关系
            FileTagRelationModel::where('tag_id', $id)->delete();
            
            $tag->delete();
            return $this->success([], '删除成功');
            
        } catch (\Exception $e) {
            return $this->error('删除标签失败：' . $e->getMessage());
        }
    }

    /**
     * 移动文件分类
     * @return Response
     */
    public function moveCategory(): Response
    {
        try {
            $params = request()->only(['ids', 'category_id']);
            
            if (empty($params['ids']) || !isset($params['category_id'])) {
                return $this->error('参数错误');
            }
            
            FileModel::whereIn('file_id', $params['ids'])
                    ->update(['category_id' => $params['category_id']]);
            
            return $this->success([], '移动成功');
            
        } catch (\Exception $e) {
            return $this->error('移动文件失败：' . $e->getMessage());
        }
    }

    /**
     * 文件上传
     * @return Response
     */
    public function upload(): Response
    {
        try {
            $file = request()->file('file');
            if (!$file) {
                return $this->error('请选择要上传的文件');
            }

            // 获取上传参数
            $categoryId = request()->param('category_id');
            $storagePermission = request()->param('storage_permission', 'public');
            $fileType = request()->param('file_type', 'all');

            // 根据路由自动识别文件类型
            $routeAction = request()->action();
            if ($routeAction === 'upload') {
                $routePath = request()->pathinfo();
                if (strpos($routePath, 'upload/image') !== false) {
                    $fileType = 'image';
                } elseif (strpos($routePath, 'upload/video') !== false) {
                    $fileType = 'video';
                } else {
                    $fileType = 'all';
                }
            }

            // 使用现有的FileUploadService
            $uploadService = new \app\common\service\FileUploadService();
            
            $options = [
                'file_type' => $fileType,
                'permission' => $storagePermission,
                'category_id' => $categoryId
            ];

            $result = $uploadService->upload($file, $options, 'admin');

            // 如果上传成功，更新分类ID
            if (isset($result['file_id']) && $categoryId) {
                $this->fileModel->where('file_id', $result['file_id'])
                    ->update(['category_id' => $categoryId]);
            }

            return $this->success($result, '文件上传成功');

        } catch (\Exception $e) {
            return $this->error('上传失败：' . $e->getMessage());
        }
    }

    /**
     * 获取当前管理员ID
     * @return int
     */
    private function getCurrentAdminId(): int
    {
        return request()->adminId ?? 0;
    }
}
