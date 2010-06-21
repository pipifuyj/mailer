<?php
//set init parameters
ini_set("max_execution_time", "9999999999");
ini_set("max_input_time", "1000");
$task ="webpage";//make your description:webpage or pic

$web_link = "http://www.ustcif.org/default.php/page/58/";
$pic_link = "http://www.ustcif.org/global/files/File/FinancialReports/AnnualReports/2009/mail.JPG";

$email_title = "USTCIF Alumni Newsletter";

$email_body = "";
if($task == "webpage")
{
	$email_body = WebpageAsEmailbody($web_link);
}

if($task == "pic")
{
	$email_body = PicAsEmailbody($web_link,$pic_link);
}

if(count($argv) !== 2 || (count($argv) == 2 && $argv[1] !== "start" && $argv[1] !== "restart"&& $argv[1] !== "test")){ 
	exit( "usage: 'php xxx.php start' OR 'php xxx.php restart' OR 'php xxx.php test'\n");
}


if($argv[1] !== null && $argv[1] !== "test"){
	SendMail($email_title,$email_body, $argv[1]);
}
else if($argv[1] === "test"){
	testmailer($email_title,$email_body);
}
else{
	SendMail($email_title,$email_body, "restart");
}


/********************
function name: WebpageAsEmailbody
function goal: when user decide to use webpage as email body, given a web link, return the email body 
*********************/
function WebpageAsEmailbody($web_link)
{
	if($web_link != "")
	{
		$email_body = 'If you have difficulty viewing this message, please try the <a href="' . $web_link .'"> online version</a>.' . file_get_contents($web_link);
		return $email_body;
	}
	else
	{
		file_put_contents("result.txt","网页链接为空\n",FILE_APPEND);
		exit("网页链接为空\n");
	}
}


/********************
function name: PicAsEmailbody
function goal: when user decide to use pic as email body, given a pic link, return the email body 
*********************/
function PicAsEmailbody($web_link,$pic_link)
{
	if($web_link != "" && $pic_link != "")
	{
	//	$email_body = '<p>Forward to USTC friends, share the link-<a href="'.$web_link.'">'.$web_link.'</a></p>'.'<p><a href="'.$web_link.'"><img src="'.$pic_link.'"></a></p>';
              	$email_body = '<p>If you have difficulty viewing this message, please try the <a href="' . $web_link .'"> online version</a>.</p>'. '<p><a href="'.$web_link.'"><img src="'.$pic_link.'"></a></p>';
	return $email_body;
	}
	else
	{	
		file_put_contents("result.txt","网页链接或者图片链接为空\n",FILE_APPEND);
		exit("网页链接或者图片链接为空");
	}
}


/********************
function name: SendMail
function goal: given email title and email body, send the email to all the aluminus
*********************/
function SendMail($email_title,$email_body,$reboot)
{
	if($email_title == "" || $email_body == "" || $email_title == null ||  $email_body == null)
	{
		file_put_contents("result.txt","email body or title is null\n",FILE_APPEND);
		exit("email内容为空或者email标题为空\n");		
	}

	//load basic class: SMTPMailer
	require("SMTPMailer.php");

	if($reboot == "start"){//说明第一次启动程序
		file_put_contents("result.txt","");		
		//链接到数据库并获得要发送的邮件地址
		$connection = mysql_connect("localhost", "mydonor", "MYDONOR@))(") or die ("Unable toconnect!");
		mysql_select_db("mydonor") or die ("Unable to select database!"); 
		mysql_query("SET NAMES UTF8");
		$query = "SELECT distinct email_addr FROM if_donor_email WHERE priority >= 0 and deleted = 0 ORDER BY id"; 
		$result = mysql_query($query) or die ("Error in query: $query. " . mysql_error());
		//写下本次要发送的邮件地址到emaillist
		file_put_contents("emaillist.txt","");
		$i = 0;
		while($email = mysql_fetch_row($result)){
			file_put_contents("emaillist.txt",$i." ".$email[0]."\n",FILE_APPEND);	
			$i ++;
		}
		//计算本次要发送的邮件数目
		$email_num = mysql_num_rows($result);
		if($i !== $email_num) die ("出错：email数目和写入emaillist文件的email数不一致"); 
		//关闭数据库链接
		mysql_close($connection);

		//从emaillist.txt中读入本次待发送的邮件列表
		$file= "emaillist.txt";
		$emaillist=file($file,FILE_IGNORE_NEW_LINES);
		if($email_num !== count($emaillist)) die ("出错：email数目和emaillist文件总行数不一致"); 
		for($i = 0; $i < count($emaillist); $i ++){
			$email[$i] = preg_split("/\s+/",trim($emaillist[$i]));		
		}
		//开始发送邮件
		if(count($email) > 0){
				for($i = 0; $i < count($email); $i ++)
				{
					$mailer=new SMTPMailer();
					$mailer->Host="202.38.64.8";
					$mailer->UserName="";
					$mailer->Password="";
					$mailer->From="";
					$mailer->ContentType="text/html";
					$mailer->Subject=$email_title; 
					$mailer->Body=$email_body;
					$mailer->To=$email[$i][1];
					if($i !== intval($email[$i][0])) exit("当前发送email地址的id和emaillist.txt中的记录行号id不一致\n");
					if($mailer->Send()){
						file_put_contents("result.txt",$i." ".$email[$i][1]." 成功\n",FILE_APPEND);
					}
					else{
						file_put_contents("result.txt",$i." ".$email[$i][1]." ".$mailer->Error."\n",FILE_APPEND);
/*						if(strstr($mailer->Error,"Recipient") !== false){
							$connection = mysql_connect("localhost", "mydonor", "MYDONOR@))(") or die ("Unable toconnect!");
							mysql_select_db("mydonor") or die ("Unable to select database!"); 
							mysql_query("SET NAMES UTF8");
							$query = "update if_donor_email set priority = -1 where email_addr = '".$email[0]."'"; 
							$setResult = mysql_query($query);
							$error = mysql_error();
						}*/
					}
					file_put_contents("currpos.txt",$i);
					sleep(5);
				}
		}				
	}
	else if($reboot == "restart"){//说明属于重启
		$file= "currpos.txt";
		$currpos=file($file,FILE_IGNORE_NEW_LINES);
		if(count($currpos) === 0) exit("在重启模式下，currpos.txt不能为空\n");
		$from = intval($currpos[0])+1; //重启后应该从第几个email开始发送
		if($from < 1) exit("$from 值不对");
		
		//从emaillist.txt中读入本次待发送的邮件列表
		$file= "emaillist.txt";
		$emaillist =file($file,FILE_IGNORE_NEW_LINES);
		for($i = 0; $i < count($emaillist); $i ++){
			$email[$i] = preg_split("/\s+/",trim($emaillist[$i]));		
		}
		//开始发送邮件
		if(count($email) > 0){
				for($i = $from; $i < count($email); $i ++)
				{
					$mailer=new SMTPMailer();
					$mailer->Host="202.38.64.8";
					$mailer->UserName="";
					$mailer->Password="";
					$mailer->From="";
					$mailer->ContentType="text/html";
					$mailer->Subject=$email_title; 
					$mailer->Body=$email_body;
					$mailer->To=$email[$i][1];
					if($i !== intval($email[$i][0])) exit("当前发送email地址的id和emaillist.txt中的记录id不一致\n");
					if($mailer->Send()){
						file_put_contents("result.txt",$i." ".$email[$i][1]." 成功\n",FILE_APPEND);
					}
					else{
						file_put_contents("result.txt",$i." ".$email[$i][1]." ".$mailer->Error."\n",FILE_APPEND);
/*						if(strstr($mailer->Error,"Recipient") !== false){
							$connection = mysql_connect("localhost", "mydonor", "MYDONOR@))(") or die ("Unable toconnect!");
							mysql_select_db("mydonor") or die ("Unable to select database!"); 
							mysql_query("SET NAMES UTF8");
							$query = "update if_donor_email set priority = -1 where email_addr = '".$email[0]."'"; 
							$setResult = mysql_query($query);
							$error = mysql_error();
						}*/
					}
					file_put_contents("currpos.txt",$i);
					sleep(5);
				}
		}			
					
	}else{//既不属于第一次启动，也不属于重启，说明有问题
		exit("出错：/既不属于第一次启动，也不属于重启，说明有问题"); 	
	}	
}



function testmailer($email_title,$email_body)
{
	if($email_title == "" || $email_body == "")
	{
		file_put_contents("result.txt","email body or title is null\n",FILE_APPEND);
		exit("email body or title is null");		
	}

	require("SMTPMailer.php");

//	file_put_contents("result.txt","log report:\n");

	$emails = array(
	"pipifuyj@gmail.com",
        "otcjoeliu@yahoo.com.cn",
        "liuzhifeng@gmail.com"
	);

	$email_num = count($emails);
	$curr_num = 0;
	$sucess_num = 0;
	$fail_num = 0;
	for($i = 0; $i < $email_num; $i++)
	{
	       $mailer=new SMTPMailer();
			$mailer->Host="202.38.64.8";
			$mailer->UserName="";
			$mailer->Password="";
			$mailer->From="";
			$mailer->ContentType="text/html";
			$mailer->Subject=$email_title; 
			$mailer->Body=$email_body;
			$mailer->To=$emails[$i];
			$curr_num ++;
			if($mailer->Send()){
				$sucess_num ++;
				//file_put_contents("result.txt","($curr_num)$emails[$i] sucessful_sended! [$sucess_num sucess, $fail_num failed]\n",FILE_APPEND);
			}
			else{
				$fail_num ++;
				if(strstr($mailer->Error,"Recipient") !== false){
					$connection = mysql_connect("localhost", "mydonor", "MYDONOR@))(") or die ("Unable toconnect!");
					mysql_select_db("mydonor") or die ("Unable to select database!"); 
					mysql_query("SET NAMES UTF8");
					$query = "update if_donor_email set priority = -1 where email_addr = '".$emails[$i]."'"; 
					$result = mysql_query($query);
					$error = mysql_error();
					//file_put_contents("result.txt", "($curr_num) $emails[$i] ".$mailer->Error." [$sucess_num sucess, $fail_num failed][set_invalid_email_priority=-1: $result $error]\n",FILE_APPEND);
				}
				else{
					//file_put_contents("result.txt", "($curr_num) $emails[$i] ".$mailer->Error." [$sucess_num sucess, $fail_num failed]\n",FILE_APPEND);
				}

			}
			echo $mailer->Info;
			sleep(2);
	}

	//sumary of this task
//	file_put_contents("result.txt","sumary:\n",FILE_APPEND);
//	file_put_contents("result.txt","the number of emails to be send is: $email_num\n",FILE_APPEND);
//	file_put_contents("result.txt","the number of emails sended sucessfully is: $sucess_num\n",FILE_APPEND);
//	file_put_contents("result.txt","the number of emails failed to be sended is: $fail_num\n",FILE_APPEND);
}
?>
