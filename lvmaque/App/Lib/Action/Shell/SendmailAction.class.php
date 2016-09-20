<?php

/**
 * 批量创建用户回调
 */
Class SendmailAction extends AbstractmcqAction
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
		if (!isset($params['subject']) && !isset($params['body']) && !isset($params['user_ids'])) {
			return $ret;
		}
		$ret = $this->handle($params);
		$this->log('mcq', 'info', __METHOD__ . ", ret={$ret}, params=" . @var_export($params, true));
		return $ret;
	}

	private function handle($params)
	{
		$ret = false;
		if (!empty($params['user_ids'])) {
			try {
                header("content-type:text/html;charset=utf-8");
                import("ORG.Net.Phpmailer");
                
                $msgconfig = FS("Webconfig/msgconfig");
                $stmpport = $msgconfig['stmp']['port'];//25;
                $stmphost = $msgconfig['stmp']['server'];
                $stmpuser = $msgconfig['stmp']['user'];
                $stmppass = $msgconfig['stmp']['pass'];
                
                $mail = new PHPMailer(true);
                $mail->IsSMTP();
                $mail->CharSet='UTF-8'; //设置邮件的字符编码，这很重要，不然中文乱码
                $mail->SMTPAuth   = true;                  //开启认证
                $mail->Port       = $stmpport;//25;
                $mail->Host       = $stmphost;
                $mail->Username   = $stmpuser;
                $mail->Password   = $stmppass;
                $mail->AddReplyTo($stmpuser,$stmpuser);//回复地址
                $mail->From       = $stmpuser;
                $mail->FromName   = $stmpuser;
                

                $single_per = 2;
                $count = count($params['user_ids']);

                $batch_time = ceil($count/$single_per);
                for($i = 0; $i<$batch_time; $i++ ) {
                    $output = array_slice($params['user_ids'], $i*$single_per, $single_per); //每次处理n个
                    foreach( $output as $val ) {
                        $mail->AddAddress($val);
                    }
                }
                $mail->Subject  = $params['subject'];
                $mail->Body = $params['body'];
                $mail->AltBody    = "To view the message, please use an HTML compatible email viewer!"; //当邮件不支持html时备用显示，可以省略
                $mail->WordWrap   = 80; // 设置每行字符串的长度
                $mail->IsHTML(true);
                $ret = $mail->Send();
			} catch (Exception $e) {
				$this->log('mcq', 'fatal', __METHOD__ . ", error={$e->getMessage()}");
				$ret = false;
			}
		}
		return $ret;
	}
}