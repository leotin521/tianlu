<?php

/**
 * 自动投标
 */
Class AutoinvestAction extends AbstractautoAction
{
	const DEBUG = false;

	protected $alias; // MCQ KEY 队列名称
	protected $proc_num; //操作对应队列(1,2,3....n)

	public function config()
	{
	}

	//核心处理方法，在Controller_Shell_Mcq_AbstractMcq类里有定义
	public function process($params)
	{
		$ret = true;
		if (!isset($params['borrow_id']) && !isset($params['duration_month']) ) {
			return $ret;
		}
		$ret = $this->handle($params);
		$this->log('mcq', 'info', __METHOD__ . ", ret={$ret}, params=" . @var_export($params, true));
		return $ret;
	}

	private function handle($params)
	{

		$ret = false;
		if (!empty($params['borrow_id'])) {
			try {
                $ret = autoInvest($params['borrow_id']);
			} catch (\Exception $e) {
				$this->log('mcq', 'fatal', __METHOD__ . ", error={$e->getMessage()}");
				$ret = false;
			}
		}
		return $ret;
	}
}