<?php

namespace app\admin\controller\jj;

use app\common\controller\Backend;
use app\common\model\jj\FactoryAccount;
use app\common\model\jj\FactoryWithdraw as FactoryWithdrawModel;
use think\Db;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use think\response\Json;

/**
 * 工厂提现管理
 *
 * @icon fa fa-circle-o
 */
class FactoryWithdraw extends Backend
{
    protected $relationSearch = false;

    /**
     * @var \app\common\model\jj\FactoryWithdraw
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new FactoryWithdrawModel;
    }

    /**
     * 查看
     *
     * @return string|Json
     * @throws \think\Exception
     * @throws DbException
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
     * 审核通过（标记已处理 state=3）
     *
     * @param $ids
     * @return string
     * @throws DbException
     * @throws \think\Exception
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if (false === $this->request->isPost()) {
            $this->view->assign('row', $row);
            return $this->view->fetch();
        }
        $params = $this->request->post('row/a');
        if (empty($params)) {
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $result = false;
        // 只允许审核中或处理中的记录审核通过
        if (!in_array($row->state, [FactoryWithdrawModel::STATE_PENDING, FactoryWithdrawModel::STATE_PROCESSING])) {
            $this->error('当前状态不允许此操作');
        }
        Db::startTrans();
        try {
            $params['state'] = FactoryWithdrawModel::STATE_DONE;
            $params['checktime'] = time();
            $result = $row->allowField(true)->save($params);

            // 累加 total_withdraw
            $account = FactoryAccount::where('factory_id', $row->factory_id)->lock(true)->find();
            if ($account) {
                $account->save([
                    'total_withdraw' => bcadd($account->total_withdraw, $row->money, 2),
                ]);
            }

            Db::commit();
        } catch (PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if (false === $result) {
            $this->error(__('No rows were updated'));
        }
        $this->success();
    }

    /**
     * 标记处理中 state=2
     */
    public function processing($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ?: $this->request->post("ids");
        if (!$ids) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }

        $count = 0;
        Db::startTrans();
        try {
            $list = $this->model->where($this->model->getPk(), 'in', $ids)->select();
            foreach ($list as $item) {
                if ($item->state == FactoryWithdrawModel::STATE_PENDING) {
                    $count += $item->allowField(true)->isUpdate(true)->save([
                        'state' => FactoryWithdrawModel::STATE_PROCESSING,
                    ]);
                }
            }
            Db::commit();
        } catch (PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $this->success();
        }
        $this->error(__('No rows were updated'));
    }

    /**
     * 拒绝提现 state=4 + 退回余额
     */
    public function refuse($ids = "")
    {
        if (!$this->request->isPost()) {
            $this->error(__("Invalid parameters"));
        }
        $ids = $ids ?: $this->request->post("ids");
        $reason = $this->request->post('reason', '');
        if (!$ids) {
            $this->error(__('Parameter %s can not be empty', 'ids'));
        }
        if (!$reason) {
            $this->error('请填写拒绝提现理由');
        }

        $count = 0;
        Db::startTrans();
        try {
            $list = $this->model->where($this->model->getPk(), 'in', $ids)->select();
            foreach ($list as $item) {
                if (in_array($item->state, [FactoryWithdrawModel::STATE_PENDING, FactoryWithdrawModel::STATE_PROCESSING])) {
                    $count += $item->allowField(true)->isUpdate(true)->save([
                        'state'     => FactoryWithdrawModel::STATE_REFUSED,
                        'reason'    => $reason,
                        'checktime' => time(),
                    ]);
                    // 退回余额
                    FactoryWithdrawModel::refundOnRefuse($item->id);
                }
            }
            Db::commit();
        } catch (PDOException|Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($count) {
            $this->success();
        }
        $this->error(__('No rows were updated'));
    }

    public function add()
    {
        return;
    }

    public function del($ids = null)
    {
        return;
    }

    public function multi($ids = null)
    {
        return;
    }
}
