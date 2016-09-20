<?php
/**
 *
 */
abstract class AbstractautoAction extends AbstractAction
{
	/**
	 * MCQ KEY
	 * @var string
	 */
	protected $alias;

	/**
	 * 消息队列总数
	 * @var int
	 */
	protected $proc_total;

	/**
	 * 当前消息队列进程数
	 * @var string
	 */
	protected $proc_num;

	/**
	 * 该alias是否使用mysql连接
	 * @var string
	 */
	protected $use_db;

	/**
	 * 每次获取数据行数
	 * @var int
	 */
	protected $limit = 10;

	/**
	 * 解析数据函数名称('json', '', 'serialize')
	 * @var string
	 */
	protected $parse = 'json';

	/**
	 * 默认获取不到数据睡多长时间
	 * @var type
	 */
	protected $sleep = 10;

	/**
	 * 心跳最小间隔时间，秒
	 * @var int
	 */
	protected $min_beat_time = 60;

    public function _initialize()
    {
        $this->index();
    }
	//开始执行
	final public function index()
	{
		$argv = $_SERVER['argv'];
		$arr = explode("/", $argv[1]);
		$this->alias = $arr[count($arr) - 1];

		$this->proc_num = empty($_GET['proc_num']) ? 1 : $_GET['proc_num'];

		//如果总进程数没定义，默认为1
//		$conf = Comm_Config::get("mcq.{$this->alias}");
		$this->proc_total = isset($conf['proc_total']) ? $conf['proc_total'] : 1;
		$this->use_db = isset($conf['use_db']) ? $conf['use_db'] : 0;

		if ($this->proc_num > $this->proc_total)
			die('proc_num >proc_total');

		$this->config();

		if (!$this->alias) {
			throw new Exception('The Object->$alias is empty.');
		}

        while (true) {
            $Mcq = new McqModel('auto');

            //从MCQ中获取
            $data = array();
            $Mcq->getQueueLength();
            $tmp = $Mcq->get($this->limit); //TODO: 暂不管成功失败，都删除掉,取出来自动删除掉了

            foreach( $tmp as $key=>$val ) {
                if (empty($val)) {
                    continue;
                }
                switch ($this->parse) {
                    case 'json' :
                        $tmp = json_decode($val, true);
                        break;
                    case 'serialize' :
                        $tmp = unserialize($val, true);
                        break;
                }
                $data[$key] = $tmp;
            }

            if (!$data) { //一条数据也没有,开始睡觉！
                echo "\033[34mRead data empty [{$this->alias}] " . date('Y-m-d H:i:s') . "\033[0m\r\n";
                sleep($this->sleep);
            } else { //开始处理
                try {
                    /*
                    * 缺陷：
                    * 一次时间连接时间过长，Comm_Db_PdoMysql类的mysql connection 会 gone away
                    * 需要强制重新连接
                    * 后续需要再做考虑
                    */
                    /*					if ($this->use_db === 1) {
                                            Db::clear_all();
                                            Db::auto_configure_pool();
                                        }*/
                    foreach ($data as $value) {
                        $re = $this->process($value);
                        sleep(1);
//						if ($re ) {
//                            $Mcq->deQueue();
//                        }

                        if (!$re || $re === 'do now') {
//							Mcq::mcqWrite($this->alias, $value);
                        }
                    }
                } catch (\Exception $e) {
//					Mcq::mcqWrite($this->alias, $value);
                    $this->on_exception($e, $data);
                }
            }
        }
		$time = date('Y-m-d H:i:s');
	}

	/**
	 * 初始配置
	 */
	protected function config()
	{
	}

	/**
	 * 核心处理方法
	 * @param array $datas 数据集
	 */
	abstract protected function process($datas);

	/**
	 * 异常处理
	 * @param Exception $e
	 * @param mixed $data
	 */
	protected function on_exception($e, $data)
	{
        Log::write('|MCQ-LOG|EXCEPTION|', __METHOD__ . ", error:{$e->getMessage()}, data:{$data}");
	}
}
