<?php
header("Content-type: text/html; charset=UTF-8"); 
ini_set('max_execution_time', '0');
class ascii {
  /**
   * 将ascii码转为字符串
   * @param type $str 要解码的字符串
   * @param type $prefix 前缀，默认:&#
   * @return type
   */
  function decode($str, $prefix="&#") {
    $str = str_replace($prefix, "", $str);
    $a = explode(";", $str);
    foreach ($a as $dec) {
      if ($dec < 128) {
        $utf .= chr($dec);
      } else if ($dec < 2048) {
        $utf .= chr(192 + (($dec - ($dec % 64)) / 64));
        $utf .= chr(128 + ($dec % 64));
      } else {
        $utf .= chr(224 + (($dec - ($dec % 4096)) / 4096));
        $utf .= chr(128 + ((($dec % 4096) - ($dec % 64)) / 64));
        $utf .= chr(128 + ($dec % 64));
      }
    }
    return $utf;
  }
  /**
   * 将字符串转换为ascii码
   * @param type $c 要编码的字符串
   * @param type $prefix 前缀，默认：&#
   * @return string
   */
  function encode($c, $prefix="&#") {
  	$scill=NULL;
    $len = strlen($c);
    $a = 0;
    while ($a < $len) {
      $ud = 0;
      if (ord($c{$a}) >= 0 && ord($c{$a}) <= 127) {
        $ud = ord($c{$a});
        $a += 1;
      } else if (ord($c{$a}) >= 192 && ord($c{$a}) <= 223) {
        $ud = (ord($c{$a}) - 192) * 64 + (ord($c{$a + 1}) - 128);
        $a += 2;
      } else if (ord($c{$a}) >= 224 && ord($c{$a}) <= 239) {
        $ud = (ord($c{$a}) - 224) * 4096 + (ord($c{$a + 1}) - 128) * 64 + (ord($c{$a + 2}) - 128);
        $a += 3;
      } else if (ord($c{$a}) >= 240 && ord($c{$a}) <= 247) {
        $ud = (ord($c{$a}) - 240) * 262144 + (ord($c{$a + 1}) - 128) * 4096 + (ord($c{$a + 2}) - 128) * 64 + (ord($c{$a + 3}) - 128);
        $a += 4;
      } else if (ord($c{$a}) >= 248 && ord($c{$a}) <= 251) {
        $ud = (ord($c{$a}) - 248) * 16777216 + (ord($c{$a + 1}) - 128) * 262144 + (ord($c{$a + 2}) - 128) * 4096 + (ord($c{$a + 3}) - 128) * 64 + (ord($c{$a + 4}) - 128);
        $a += 5;
      } else if (ord($c{$a}) >= 252 && ord($c{$a}) <= 253) {
        $ud = (ord($c{$a}) - 252) * 1073741824 + (ord($c{$a + 1}) - 128) * 16777216 + (ord($c{$a + 2}) - 128) * 262144 + (ord($c{$a + 3}) - 128) * 4096 + (ord($c{$a + 4}) - 128) * 64 + (ord($c{$a + 5}) - 128);
        $a += 6;
      } else if (ord($c{$a}) >= 254 && ord($c{$a}) <= 255) { //error
        $ud = false;
      }
      $scill .= $prefix.$ud.";";
    }
    return $scill;
  }
}
$c_ascii = new ascii;
$tv_tag=$_GET['tag'];//"英剧";
$page_start_cnt=$_GET['num'];//20;
$url_tv = "https://movie.douban.com/j/search_subjects?type=tv&tag=".$tv_tag."&sort=recommend&page_limit=20&page_start=".$page_start_cnt;
$url_tv_content = file_get_contents($url_tv);//抓取一页英剧或美剧目录列表
$arr_result = json_decode($url_tv_content,true);//将JSON转为Array
$tvinfo[$tv_tag][$page_start_cnt]=array();
for($j=0;$j<count($arr_result["subjects"]);$j++){
	$id=$arr_result["subjects"][$j]["id"];
	$tvinfo[$tv_tag][$page_start_cnt][$j]["id"]=(string)$id;
	$tvinfo[$tv_tag][$page_start_cnt][$j]["rate"]=(string)$arr_result["subjects"][$j]["rate"];
	$tvinfo[$tv_tag][$page_start_cnt][$j]["title"]=(string)$arr_result["subjects"][$j]["title"];
	//print_r($tvinfo);
	$start_cnt_num=5;
	$userinfo=array();
	for($k=0;$k<$start_cnt_num;$k++){
		$start_cnt = 20*$k;
		$url =  "https://movie.douban.com/subject/".$id."/comments?start=".$start_cnt."&limit=20&sort=new_score&status=P";
		$Content = file_get_contents($url);//抓取剧目的所有短评
		preg_match_all('/<div class="comment">([\s\S]*?)<\/div>/',$Content,$matches);
		//print_r($matches[1]);
    $userinfo[$start_cnt]=array();
		for($i=0;$i<count($matches[1]);$i++)
		{
			preg_match('/<span class="votes">(.*?)<\/span>/',$matches[1][$i],$votes);
			//echo "votes:".$votes[1]."</br>";
    		$userinfo[$start_cnt][$i]["votes"]=$votes[1];//评论得赞数
			preg_match('/<span class="comment-info">[\s\S]*?<a href=".*?" class=".*?">(.*?)<\/a>/',$matches[1][$i],$username);
			//echo "username:".$username[1]."</br>";
			if($username[1]=="_blank_"){
				$userinfo[$start_cnt][$i]["username"]=0;
			}else{
				$userinfo[$start_cnt][$i]["username"]=$c_ascii->encode($username[1]);//用户名
			}
			
			preg_match('/<span class="allstar(.*?) rating" title=".*?"><\/span>/',$matches[1][$i],$stars);
			//echo "stars:".$stars[1]."</br>";
			if(count($stars)!=0){
				$userinfo[$start_cnt][$i]["stars"]=$stars[1];//评分
		    }else{
		    	$userinfo[$start_cnt][$i]["stars"]=0;
		    }
			preg_match('/<span class="comment-time " title="(.*?)">/',$matches[1][$i],$date);
			//echo "date:".$date[1]."</br>";
			$userinfo[$start_cnt][$i]["date"]=$date[1];//评论日期
			preg_match('/<p class="">([\s\S]*?)<\/p>/',$matches[1][$i],$comment);
			if(preg_match('/<a class="source-icon" href=".*?" target="_blank">.*?<\/a>/',$comment[1])){
			  preg_match('/<p class="">([\s\S]*?)<a class="source-icon" href=".*?" target="_blank">/',$matches[1][$i],$comment);
			  $userinfo[$start_cnt][$i]["comment"]=$c_ascii->encode($comment[1]);//评论
			}else{
			  //echo "comment:".$comment[1]."</br>";
			  $userinfo[$start_cnt][$i]["comment"]=$c_ascii->encode($comment[1]);
			}
			
			//echo ".....................................</br>";
		}
		//echo $start_cnt."....................................."."</br>";
		//print_r($userinfo[$start_cnt]);
		//print_r(json_encode($userinfo,JSON_UNESCAPED_UNICODE));

	}
	//print_r($userinfo);
	$tvinfo[$tv_tag][$page_start_cnt][$j]["userinfo"]=$userinfo;
}
//echo "<pre>";
//print_r($tvinfo);
//echo "<pre>";
//var_dump($tvinfo);
//print_r($tvinfo);
print_r(json_encode($tvinfo,JSON_UNESCAPED_UNICODE));
$file = "y".$page_start_cnt.".json";
file_put_contents($file,json_encode($tvinfo,JSON_UNESCAPED_UNICODE));//写入json文件
