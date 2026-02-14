<?php

namespace app\admin\controller\xiluxc\finance;

use app\common\controller\Backend;
use app\common\model\jj\FactoryWithdraw as FactoryWithdrawModel;
use app\common\model\jj\FactoryAccount;
use app\common\model\jj\FactoryFundLog;
use think\Db;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 工厂提现管理
 *
 * @icon fa fa-money
 */
class FactoryWithdraw extends Backend
{
    protected $model = null;
    protected $noNeedRight = ['*'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new FactoryWithdrawModel;
    }

    /**
     * 列表
     */
    public function index()
    {
        $this->request->filter(['strip_tags', 'trim']);
        if (false === $this->request->isAjax()) {
            return $this->view->fetch();
        }
        if ($this->request->request('keyField')) {
            return $this->selectpage();
        }

        [$where, $sort, $order, $offset, $limit] = $this->buildparams();
        $list = $this->model
            ->where($where)
            ->order($sort, $order)
            ->paginate($limit);

        $result = ['total' => $list->total(), 'rows' => $list->items()];
        return json($result);
    }

    /**
     * 审核通过（标记为处理中）
     */
    public function processing($ids = '')
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        $ids = $ids ?: $this->request->post('ids');
        if (!$ids) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }

        $count = 0;
        Db::startTrans();
        try {
            $list = $this->model->where('id', 'in', $ids)
                ->where('state', FactoryWithdrawModel::STATE_PENDING)
                ->lock(true)
                ->select();
            foreach ($list as $item) {
                $item->save([
                    'state'     => FactoryWithdrawModel::STATE_PROCESSING,
                    'checktime' => time(),
                ]);
                $count++;
            }
            Db::commit();
        } catch (PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($count) {
            $this->success('已标记为处理中');
        }
        $this->error(__('No rows were updated'));
    }

    /**
     * 确认已打款（标记为已处理）
     */
    public function done($ids = '')
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        $ids = $ids ?: $this->request->post('ids');
        if (!$ids) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }

        $count = 0;
        Db::startTrans();
        try {
            $list = $this->model->where('id', 'in', $ids)
                ->where('state', FactoryWithdrawModel::STATE_PROCESSING)
                ->lock(true)
                ->select();
            foreach ($list as $item) {
                $item->save([
                    'state'     => FactoryWithdrawModel::STATE_DONE,
                    'checktime' => time(),
                ]);
                $count++;
            }
            Db::commit();
        } catch (PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($count) {
            $this->success('已确认打款');
        }
        $this->error(__('No rows were updated'));
    }

    /**
     * 拒绝提现（退回金额到工厂余额）
     */
    public function refuse($ids = '')
    {
        if (!$this->request->isPost()) {
            $this->error(__('Invalid parameters'));
        }
        $ids = $ids ?: $this->request->post('ids');
        $reason = $this->request->post('reason', '');
        if (!$ids) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }
        if (!$reason) {
            $this->error('请填写拒绝原因');
        }

        $count = 0;
        Db::startTrans();
        try {
            $list = $this->model->where('id', 'in', $ids)
                ->where('state', FactoryWithdrawModel::STATE_PENDING)
                ->lock(true)
                ->select();
            foreach ($list as $item) {
                // 标记为已拒绝
                $item->save([
                    'state'     => FactoryWithdrawModel::STATE_REFUSED,
                    'reason'    => $reason,
                    'checktime' => time(),
                ]);

                // 退回金额到工厂余额
                FactoryWithdrawModel::refundOnRefuse($item->id);
                $count++;
            }
            Db::commit();
        } catch (PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }

        if ($count) {
            $this->success('已拒绝并退回金额');
        }
        $this->error(__('No rows were updated'));
    }

    /**
     * 禁止直接添加
     */
    public function add()
    {
        $this->error('提现记录由工厂端发起，不支持后台添加');
    }

    /**
     * 禁止直接删除
     */
    public function del($ids = null)
    {
        $this->error('提现记录不可删除');
    }
}
