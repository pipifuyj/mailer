<?php 
	if(count($argv) != 2){
		echo "usage: php GeteEmailList.php <yourfilename>";
	}else{
		$filename = $argv[1];echo $filename;
		//链接到数据库并获得要发送的邮件地址
                $connection = mysql_connect("localhost", "", "") or die ("Unable toconnect!");
                mysql_select_db("mydonor") or die ("Unable to select database!"); 
                mysql_query("SET NAMES UTF8");
                $query = "SELECT distinct email_addr FROM if_donor_email WHERE priority >= 0 and deleted = 0 ORDER BY id"; 
                $result = mysql_query($query) or die ("Error in query: $query. " . mysql_error());
                //写下本次要发送的邮件地址到emaillist
                file_put_contents($filename,"");
                $i = 0;
                while($email = mysql_fetch_row($result)){
                        file_put_contents($filename,$i." ".$email[0]."\n",FILE_APPEND);   
                        $i ++;
                }
	} 

?>

