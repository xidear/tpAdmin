<?php

namespace app\controller\admin;

use app\common\BaseController;
use app\model\File as FileModel;
use app\model\ImageCategory as ImageCategoryModel;
use app\model\ImageTag as ImageTagModel;
use app\model\ImageTagRelation as ImageTagRelationModel;
use think\App;
use think\Response;

class Image extends BaseController
{
    protected FileModel $fileModel;
    protected ImageCategoryModel $categoryModel;
    protected ImageTagModel $tagModel;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->fileModel = new FileModel();
        $this->categoryModel = new ImageCategoryModel();
        $this->tagModel = new ImageTagModel();
    }

    /**
     * 获取图片列表
     * @return Response
     */
    public function index(): Response
    {
        try {
            // 获取分页参数
            $page = (int)request()->param('page', 1);
            $listRows = (int)request()->param('list_rows', 24);
            
            // 构建筛选条件
            $conditions = [];
            
            // 只查询图片类型的文件
            $conditions[] = ['mime_type', 'like', 'image/%'];
            
            // 关键词搜索
            if (request()->has('keyword', 'get', true)) {
                $keyword = trim(request()->get('keyword'));
                if (!empty($keyword)) {
                    $conditions[] = ['origin_name', 'like', "%{$keyword}%"];
                }
            }
            
            // 分类筛选
            if (request()->has('category_id', 'get', true)) {
                $categoryId = (int)request()->get('category_id');
                if ($categoryId > 0) {
                    $conditions[] = ['category_id', '=', $categoryId];
                }
            }
            
            // 查询图片列表
            $query = $this->fileModel->where($conditions);
            
            // 获取总数
            $total = $query->count();
            
            // 分页查询
            $list = $query->page($page, $listRows)
                         ->order('created_at', 'desc')
                         ->select()
                         ->each(function ($item) {
                             // 格式化数据
                             $item->url = $this->getImageUrl($item);
                             return $item;
                         });
            
            $result = [
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'list_rows' => $listRows,
                'has_more' => ($page * $listRows) < $total
            ];
            
            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error('获取图片列表失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取图片详情
     * @param string $id
     * @return Response
     */
    public function read(string $id): Response
    {
        try {
            $image = $this->fileModel->where('file_id', $id)
                                   ->where('mime_type', 'like', 'image/%')
                                   ->find();
            
            if (!$image) {
                return $this->error('图片不存在');
            }
            
            // 格式化数据
            $image->url = $this->getImageUrl($image);
            
            return $this->success($image);
        } catch (\Exception $e) {
            return $this->error('获取图片详情失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取图片分类列表
     * @return Response
     */
    public function categories(): Response
    {
        try {
            $categories = $this->categoryModel->where('status', 1)
                                            ->order('sort', 'asc')
                                            ->order('category_id', 'asc')
                                            ->select()
                                            ->each(function ($item) {
                                                // 统计该分类下的图片数量
                                                $item->count = $this->fileModel->where('category_id', $item->category_id)
                                                                             ->where('mime_type', 'like', 'image/%')
                                                                             ->count();
                                                return $item;
                                            });
            
            // 构建树形结构
            $tree = $this->buildCategoryTree($categories);
            
            return $this->success($tree);
        } catch (\Exception $e) {
            return $this->error('获取图片分类失败: ' . $e->getMessage());
        }
    }

    /**
     * 创建图片分类
     * @return Response
     */
    public function createCategory(): Response
    {
        try {
            $data = request()->only(['name', 'code', 'parent_id', 'sort', 'status', 'description']);
            
            // 验证数据
            if (empty($data['name'])) {
                return $this->error('分类名称不能为空');
            }
            
            // 检查名称是否重复
            $exists = $this->categoryModel->where('name', $data['name'])
                                        ->where('parent_id', $data['parent_id'] ?? 0)
                                        ->find();
            if ($exists) {
                return $this->error('该分类名称已存在');
            }
            
            // 设置默认值
            $data['parent_id'] = $data['parent_id'] ?? 0;
            $data['sort'] = $data['sort'] ?? 0;
            $data['status'] = $data['status'] ?? 1;
            $data['level'] = $this->calculateCategoryLevel($data['parent_id']);
            
            $category = $this->categoryModel->create($data);
            
            return $this->success($category, '分类创建成功');
        } catch (\Exception $e) {
            return $this->error('创建分类失败: ' . $e->getMessage());
        }
    }

    /**
     * 更新图片分类
     * @param int $id
     * @return Response
     */
    public function updateCategory(int $id): Response
    {
        try {
            $category = $this->categoryModel->find($id);
            if (!$category) {
                return $this->error('分类不存在');
            }
            
            $data = request()->only(['name', 'code', 'parent_id', 'sort', 'status', 'description']);
            
            // 验证数据
            if (empty($data['name'])) {
                return $this->error('分类名称不能为空');
            }
            
            // 检查名称是否重复（排除自己）
            $exists = $this->categoryModel->where('name', $data['name'])
                                        ->where('parent_id', $data['parent_id'] ?? $category->parent_id)
                                        ->where('category_id', '<>', $id)
                                        ->find();
            if ($exists) {
                return $this->error('该分类名称已存在');
            }
            
            // 更新数据
            $category->save($data);
            
            return $this->success($category, '分类更新成功');
        } catch (\Exception $e) {
            return $this->error('更新分类失败: ' . $e->getMessage());
        }
    }

    /**
     * 删除图片分类
     * @param int $id
     * @return Response
     */
    public function deleteCategory(int $id): Response
    {
        try {
            $category = $this->categoryModel->find($id);
            if (!$category) {
                return $this->error('分类不存在');
            }
            
            // 检查是否有子分类
            $hasChildren = $this->categoryModel->where('parent_id', $id)->count();
            if ($hasChildren) {
                return $this->error('该分类下还有子分类，无法删除');
            }
            
            // 检查是否有图片
            $hasImages = $this->fileModel->where('category_id', $id)
                                       ->where('mime_type', 'like', 'image/%')
                                       ->count();
            if ($hasImages) {
                return $this->error('该分类下还有图片，无法删除');
            }
            
            $category->delete();
            
            return $this->success([], '分类删除成功');
        } catch (\Exception $e) {
            return $this->error('删除分类失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取图片标签列表
     * @return Response
     */
    public function tags(): Response
    {
        try {
            $tags = $this->tagModel->order('tag_id', 'asc')->select();
            
            return $this->success($tags);
        } catch (\Exception $e) {
            return $this->error('获取标签失败: ' . $e->getMessage());
        }
    }

    /**
     * 创建图片标签
     * @return Response
     */
    public function createTag(): Response
    {
        try {
            $data = request()->only(['name', 'color']);
            
            if (empty($data['name'])) {
                return $this->error('标签名称不能为空');
            }
            
            // 检查名称是否重复
            $exists = $this->tagModel->where('name', $data['name'])->find();
            if ($exists) {
                return $this->error('该标签名称已存在');
            }
            
            $tag = $this->tagModel->create($data);
            
            return $this->success($tag, '标签创建成功');
        } catch (\Exception $e) {
            return $this->error('创建标签失败: ' . $e->getMessage());
        }
    }

    /**
     * 删除图片标签
     * @param int $id
     * @return Response
     */
    public function deleteTag(int $id): Response
    {
        try {
            $tag = $this->tagModel->find($id);
            if (!$tag) {
                return $this->error('标签不存在');
            }
            
            // 删除标签关联关系
            ImageTagRelationModel::where('tag_id', $id)->delete();
            
            $tag->delete();
            
            return $this->success([], '标签删除成功');
        } catch (\Exception $e) {
            return $this->error('删除标签失败: ' . $e->getMessage());
        }
    }

    /**
     * 移动图片到其他分类
     * @return Response
     */
    public function moveCategory(): Response
    {
        try {
            $ids = request()->param('ids');
            $categoryId = (int)request()->param('category_id');
            
            if (empty($ids) || !is_array($ids)) {
                return $this->error('请选择要移动的图片');
            }
            
            if ($categoryId <= 0) {
                return $this->error('请选择目标分类');
            }
            
            // 验证目标分类是否存在
            $category = $this->categoryModel->find($categoryId);
            if (!$category) {
                return $this->error('目标分类不存在');
            }
            
            // 批量更新图片分类
            $this->fileModel->whereIn('file_id', $ids)
                           ->where('mime_type', 'like', 'image/%')
                           ->update(['category_id' => $categoryId]);
            
            return $this->success([], '图片移动成功');
        } catch (\Exception $e) {
            return $this->error('移动图片失败: ' . $e->getMessage());
        }
    }

    /**
     * 构建分类树形结构
     * @param  $categories
     * @param int $parentId
     * @return array
     */
    private function buildCategoryTree( $categories, int $parentId = 0): array
    {
        $tree = [];
        
        foreach ($categories as $category) {
            if ($category->parent_id == $parentId) {
                $children = $this->buildCategoryTree($categories, $category->category_id);
                if (!empty($children)) {
                    $category->children = $children;
                }
                $tree[] = $category;
            }
        }
        
        return $tree;
    }

    /**
     * 计算分类层级
     * @param int $parentId
     * @return int
     */
    private function calculateCategoryLevel(int $parentId): int
    {
        if ($parentId == 0) {
            return 1;
        }
        
        $parent = $this->categoryModel->find($parentId);
        return $parent ? $parent->level + 1 : 1;
    }

    /**
     * 获取图片完整URL
     * @param object $image
     * @return string
     */
    private function getImageUrl(object $image): string
    {
        // 如果已经有完整URL，直接返回
        if (!empty($image->url) && (str_starts_with($image->url, 'http://') || str_starts_with($image->url, 'https://'))) {
            return $image->url;
        }
        
        // 构建完整URL
        $baseUrl = request()->domain();
        $storagePath = $image->storage_path ?? '';
        
        if (!empty($storagePath)) {
            return $baseUrl . '/' . ltrim($storagePath, '/');
        }
        
        return $image->url ?? '';
    }
}
