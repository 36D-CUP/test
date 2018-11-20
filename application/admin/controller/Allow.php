<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Session;

Class Allow extends Controller
{
	public $dbt = 'lt_';								//表前缀
	public $dbimg = 'lt_img';							//文件表
	public $imgPath = '/lingtu/public/uploads/';		//普通上传的图片路径
	public $url     = '/lingtu/public/';		//普通上传的图片路径
	public $rootUrl = '/wamp64/www/lingtu/public/uploads/htmlimg/';	//根目录

	protected function initialize()
	{

        
        if(input('new_time')){
            $new_time   = input('new_time');          //选择时间 2018-08
            $start_time = strtotime($new_time);                                        //指定月份月初时间戳
            $end_time = mktime(23, 59, 59, date('m', strtotime($new_time))+1, 00);   //指定月份末时间戳
        }else{
            $thismonth = date('m');
            $thisyear = date('Y');
            $startDay  = $thisyear . '-' . $thismonth . '-1';
            $endDay  = $thisyear . '-' . $thismonth . '-' . date('t', strtotime($startDay));
            $start_time  = strtotime($startDay);//当前月的月初时间戳
            $end_time  = strtotime($endDay)+((24*3600)-1);//当前月的月末时间戳
        }

        $task_ico_where['cid'] = $this->cid;
        $task_ico_where['is_status'] = 1;
        $task_ico_where['del'] = 1;
        $task_ico = Db::table('jsf_task_ico')
                     ->where('start_time','between',[$start_time,$end_time])
                     ->where('cid=10 OR cids=10')
                     ->order('start_time')
                     ->select();

        //组合课程数据
        $task_ico_arr = array();        //定义数组
        for($i = 0 ;$i<count($task_ico) ; $i++){
            if(!in_array($task_ico[$i]['tid'], $task_ico_arr)){
                $task_ico_arr[] = $task_ico[$i]['tid'];
            }
        }

                        //为了判断下一节课判断
        //课程循环
        for($i = 0 ;$i<count($task_ico_arr) ; $i++){
            //子课程循环
            $o = 0;
            for($k = 0 ; $k<count($task_ico) ; $k++){
                //整理课程
                if($task_ico_arr[$i] == $task_ico[$k]['tid']){
                    if(!$data[$i]['title']){
                        $ico_data = Db::table('jsf_task')->where('id',$task_ico[$k]['tid'])->find();
                        $data[$i]['title'] = $ico_data['title'];
                        $data[$i]['time'] = $task_ico[$k]['start_time']?date('Y-m-d',$task_ico[$k]['start_time']):"";
                        //今天
                        $new_time = $ico_data['time']?strtotime(date('Y-m-d',$ico_data['time'])):"";
                        $old_time = strtotime(date('Y-m-d',time()));
                        $data[$i]['data_status'] = $new_time == $old_time?'1':'2';                  //2为今天,1为非今天
                        $data[$i]['old_time'] = $ico_data['time'];              //时间戳
                    }

                    // $about = Db::table('jsf_about')->find();

                    //是否过期
                    if($task_ico[$k]['start_time']+($task_ico[$k]['times']*60) <= time()){
                        $data[$i]['content'][$o]['is_time'] = 2;
                    }else{
                        $data[$i]['content'][$o]['is_time'] = 1;
                    }


                    $data[$i]['content'][$o]['id'] = $task_ico[$k]['id'];                                   //子课程id
                    $data[$i]['content'][$o]['tid'] = $task_ico[$k]['tid'];                                 //子课程id
                    $data[$i]['content'][$o]['start_time'] = date('H:i',$task_ico[$k]['start_time']); //开始时间
                    $data[$i]['content'][$o]['start_times'] = date('Y-m-d',$task_ico[$k]['start_time']); //开始时间
                    $data[$i]['content'][$o]['new_times'] = $task_ico[$k]['start_time']; //开始时间

                    //约课人数
                    $man = Db::table('jsf_reserve')->where('tiid',$task_ico[$k]['id'])->count();
                    $data[$i]['content'][$o]['man_num'] = $man;

                    $data[$i]['content'][$o]['times'] = $task_ico[$k]['times'];                         //时长

                    //判断状态
                    $time_status = $task_ico[$k]['start_time']+($task_ico[$k]['times']*60)>=time()?1:2;     //1为还未开始,2为一开始
  
                    $o++;
                }

            }

        }

        $datas = array();
        $new_arr = array();
        $t = 0;
        for($i = 0 ; $i<count($data) ;$i++){
        	for($k = 0 ; $k<count($data[$i]['content']) ; $k++){
        		if(!in_array($data[$i]['content'][$k]['start_times'], $new_arr)){
        			$new_arr[] = $data[$i]['content'][$k]['start_times'];
        			$data[$i]['time'] = $data[$i]['content'][$k]['start_times'];

                    $new_time = $data[$i]['time']?strtotime(date('Y-m-d',$data[$i]['time'])):"";
        			$old_time = strtotime(date('Y-m-d',time()));

        			$datas[$t]['title'] = $data[$i]['title'];
        			$datas[$t]['time'] = $data[$i]['content'][$k]['start_times'];
        			$datas[$t]['data_status'] = $new_time == $old_time?'1':'2';                  //2为今天,1为非今天
        			for($p = 0 ; $p<count($data[$i]['content']); $p++){
        				if($data[$i]['content'][$p]['start_times'] == $datas[$t]['time']){



                                $datas[$t]['content'][] = $data[$i]['content'][$p];                  //2为今天,1为非今天
        				}
        			}


        			$t++;
        		}
        	}
        }

        foreach ($datas as $key => $row)
{
$one[$key] = $row['time'];
}
array_multisort($one, SORT_ASC, $datas);
$is_status = 1; 
for($i = 0 ; $i<count($datas) ; $i++){
    for($k = 0 ; $k<count($datas[$i]['content']) ; $k++){
                                if($datas[$i]['content'][$k]['new_times']>=time()){
                                    $time_status = 1;
                                }else if($datas[$i]['content'][$k]['new_times']+($datas[$i]['content'][$k]['times']*60)>time() and $datas[$i]['content'][$k]['new_times']<time()){
                                    $time_status = 4;
                                }else{
                                    $time_status = 2;
                                }

                                if($time_status == 1 && $is_status == 1){
                                    $is_status = 2;
                                    $datas[$i]['content'][$k]['status'] = 3;                     //3为下一节课
                                }else{
                                    $datas[$i]['content'][$k]['status'] = $time_status;          //1为还未开始,2为一开始
                                }     
    }
}

// // 重组列
// foreach ($new_arr as $key => $row)
// {
// $one[$key] = $row['one'];
// }
// array_multisort($one, SORT_DESC, $two, SORT_ASC, $new_arr);


        // if (!empty($data)) {
        //     //重组排序
        //     foreach ($data as $key => $row)
        //     {
        //         $two[$key] = $row['old_time'];
        //     }
        //     array_multisort($two, SORT_ASC, $data);
        // }
        $arr['task_ico'] = $datas;
        $arr['empty'] = '无课程';
        $arr['day'] = input('new_time')?input('new_time'):date('Y-m',time());
        dump($new_arr);
        dump($datas);
        exit;
        // return $this->fetch('Index/course',$arr);


        $code = input('code');      //激活码
        $uid = input('uid');        //用户id
// exit;
        // $code_where['man_num_info']
        $maps['man_num'] = ['exp', "> man_num_info"];
        $maps['man_num'] = ['exp', Db::raw('< `man_num_info`')];
        
// $where['_string'] = '`man_num` < `man_num_info`';
        $data = Db::table('jsf_code')->where('man_num_info','exp',Db::raw('< `man_num`'))->select();
        dump($data);exit;
        // return $this->JSON_RETURN(1,$data,'22');


  $curTime='1541057439'; //时间戳
$y = $curTime-((date('w',$curtime)==0?7:date('w',$curtime))-1)*24*3600;	
$r = $curTime-((date('w',$curtime)==0?7:date('w',$curtime))-7)*24*3600;

 $y = mktime(0,0,0,date("m", $y),date("d", $y),date("Y", $y));		//周一时间戳
 $r = mktime(0,0,0,date("m", $r),date("d", $r),date("Y", $r))+(24*3600);		//周日时间戳

//选出这周的的子课程
 $task_ico_data = Db::table('jsf_task_ico')->where('start_time','between',[$y,$r])->field('tid,start_time,cid,uid,times')->where('is_status',1)->where('del',1)->select();
 $tids = array();

 for($i = 0 ; $i < count($task_ico_data) ; $i++){

 	$tids[] = $task_ico_data[$i]['tid'];
 }

//一周时间戳
 $days = ['一','二','三','四','五','六','日'];
 $time_num = array();
 for($i = 0 ; $i<7 ; $i++){
	$new_time = ($i*24*3600)+$y;
	$old_time = (($i+1)*24*3600)+$y;
 	for($k = 0 ; $k<count($task_ico_data) ; $k++){
 		if($task_ico_data[$k]['start_time']>=$new_time && $task_ico_data[$k]['start_time']<$old_time){
			$coach_name =  Db::table('jsf_coach')->field('name,phone')->where('id',$task_ico_data[$k]['cid'])->find();
			$task_ico_data[$k]['cid'] = $coach_name['name']?$coach_name['name']:"暂无教练";
			$task_ico_data[$k]['phone'] = $coach_name['phone']?$coach_name['phone']:"无联系电话";
			$task_ico_data[$k]['uid'] = $task_ico_data[$k]['uid'] != ""?count(explode(',',$task_ico_data[$k]['uid'])):0;

			$time_num[$i]['day'] =  $days[date('w',$task_ico_data[$k]['start_time']) != 0?date('w',$task_ico_data[$k]['start_time'])-1:6];		//获取总课程id;
			$time_num[$i]['tid'] = $task_ico_data[$k]['tid'];		//获取总课程id;
			$time_num[$i]['title'] = Db::table('jsf_task')->where('id',$task_ico_data[$k]['tid'])->field('title')->find()['title'];
 			$time_num[$i]['content'][] = $task_ico_data[$k];
 		}
 	}
 	if(!$time_num[$i]){
 		$time_num[$i] = "meiyou";
 	}
 }
dump($time_num);
dump(2);
exit;

$ceshis = array_unique($tids);
$ceshi = array();
$i = 0;
foreach ($ceshis as $key => $value) {
	$ceshi[] = $value;
	if($i == 0){
		$strs = $value;
	}else{
		$strs .= ','.$value;
	}
}
if ($strs) {
	$task_data = Db::table('jsf_task')->where('id in('.$strs.')')->select();
}

$new_data = array();
$days = ['一','二','三','四','五','六','日'];
for($i = 0 ; $i<count($task_ico_data) ; $i++){
	$coach_name =  Db::table('jsf_coach')->field('name,phone')->where('id',$task_ico_data[$i]['cid'])->find();
	$task_ico_data[$i]['cid'] = $coach_name['name']?$coach_name['name']:"暂无教练";
	$task_ico_data[$i]['phone'] = $coach_name['phone']?$coach_name['phone']:"无联系电话";
	$task_ico_data[$i]['uid'] = $task_ico_data[$i]['uid'] != ""?count(explode(',',$task_ico_data[$i]['uid'])):0;
	if($task_ico_data[$i]['start_time']){
		$timest = date('H:i',$task_ico_data[$i]['start_time']).'~'.date('H:i',$task_ico_data[$i]['start_time']+($task_ico_data[$i]['times']*60));
		$task_ico_data[$i]['start_time'] = $timest;
	}else{
		$task_ico_data[$i]['start_time'] = "";
	}
	for($k = 0 ; $k<count($time_num) ; $k++){
		if($task_ico_data[$i]['tid'] == $time_num[$k]){
			$new_data[$k]['day'] =  date('w',$task_ico_data[$i]['start_time'])-1;		//获取总课程id;
			$new_data[$k]['tid'] = $time_num[$k];		//获取总课程id;
			$new_data[$k]['title'] = Db::table('jsf_task')->where('id',$time_num[$k])->field('title')->find()['title'];


			$new_data[$k]['content'][] = $task_ico_data[$i];
		}
	}
dump($new_data);
dump(2);
exit;
}
		exit;
	    Session_start();       //使用SESSION前必须调用该函数。

		if(!isset($_SESSION['admin_id'])){
			return $this->redirect($this->url.'login/login');
	    }

    	$role = Db::table($this->dbt.'admin_dis')->alias('R')->join($this->dbt.'admin_node N','R.nid=N.id')->where('R.rid',$_SESSION['admin_level'])->select();

		foreach($role as $v){
			$arrCont[] = $v['cont'];
			$arrFun[]  = $v['fun'];
		}

		// foreach($role as $v){
		// 	if(!in_array(request()->controller(),$arrFun) and !in_array(request()->action(),$arrFun)){
		// 		$this->error('没有权限',$this->url.'admin/index');
		// 		$this->error('没有权限',$this->url.'admin/index');
		// 	}
		// }
		$val = "0";
		// dump(request()->controller());
		// dump(request()->action());
		// exit;
		for($i = 0 ; $i<count($role) ; $i++){
			if(request()->controller() == $role[$i]['cont'] && request()->action() == $role[$i]['fun']){
				$val = 1;
			}
		}

		if($val != 1){
				$this->error('没有权限',$this->url.'admin/index');

		}
	}

}
