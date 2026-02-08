<?php
namespace app\api\controller\xiluxc;

use app\common\controller\xiluxc\XiluxcApi;
use think\Db;
use think\Exception;
use app\common\model\xiluxc\comment\ServiceComment AS ServiceCommentModel;

class Comment extends XiluxcApi {

    protected $noNeedLogin = ['index'];


    /**
     * 评价列表
     * @throws \think\exception\DbException
     */
    public function index() {
        $shopId = $this->request->param('shop_id',null);
        $pagesize = $this->request->param('pagesize',10);

        $list = ServiceCommentModel::field("id,user_id,shop_id,images,content,avg_star,createtime")
            ->where('service_comment.shop_id',$shopId)
            ->with([
                'user'=>function($query){$query->withField('id,avatar,nickname');},
            ])
            ->where('service_comment.status','normal')
            ->order('service_comment.id','desc')
            ->paginate($pagesize)
            ->each(function ($row){
                $row->append(['createtime_text','images_text']);
                $row->shop && $row->shop->append(['image_text']);
            });
        $this->success('查询成功', $list);
    }

    /**
     * 我的评价列表
     * @throws \think\exception\DbException
     */
    public function my_comment() {
        $pagesize = $this->request->param('pagesize',10);

        $list = ServiceCommentModel::field("*")
            ->where('service_comment.user_id',$this->auth->id)
            ->with([
                'ordering'=>function($query){$query->withField('id,order_no');},
                'shop'=>function($query){$query->withField('id,image,name');},
            ])
            ->where('service_comment.status','normal')
            ->order('service_comment.id','desc')
            ->paginate($pagesize)
            ->each(function ($row){
                $row->append(['createtime_text','images_text']);
                $row->shop && $row->shop->append(['image_text']);
            });
        $this->success('查询成功', $list);
    }

    /**
     * 发布评论
     */
    public function add_comment() {
        $params = $this->request->post('');
        $params['user_id'] = $this->auth->id;
        Db::startTrans();
        try {
            $ret = ServiceCommentModel::addComment($params);
        }catch (Exception $e){
            Db::rollback();
            $this->error($e->getMessage());
        }
        Db::commit();
        $this->success('评论成功');

    }
}