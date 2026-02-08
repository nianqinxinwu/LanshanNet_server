<?php


namespace app\api\controller\xiluxc;


use app\common\controller\xiluxc\XiluxcApi;
use app\common\model\xiluxc\news\News AS NewsModel;

class News extends XiluxcApi
{
    protected $noNeedLogin = ["*"];


    /**
     * 列表
     */
    public function index(){
        $this->success("", NewsModel::lists());
    }

    /**
     * 详情
     */
    public function detail(){
        $newsId = $this->request->get('news_id');
        $news = $newsId ? NewsModel::normal()->field("id,name,view_num,createtime,content")->where("id",$newsId)->find() : null;
        if(!$news){
            $this->error("文章不存在");
        }
        $news->allowField(['view_num'])->setInc("view_num",1);
        $news->append(['createtime_text']);
        $this->success("",$news);
    }

}