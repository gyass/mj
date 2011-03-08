<?php 
	define("ID_LENGTH",20);
	define("CHAR_SPACE",'0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
	define("TEMP_DIR", "temp/");
	define("DOWNLOAD_DIR", "downloads/");
	$globalID;
	$dlLink;
	$downloadingFile;
	$downloadAborted = false;
	
	function createRandomFilename(){
		$cs = CHAR_SPACE;
		$string = "";    

		for ($p = 0; $p < ID_LENGTH; $p++) 
			$string .= $cs[mt_rand(0, strlen(CHAR_SPACE)-1)];
		

		return $string;
	}

	function createDwonloadId($link){
		$dir = TEMP_DIR;
		
		$id = createRandomFilename();
		$filename = $dir.$id.".dld";
		$file = fopen($filename, "w");
		fwrite($file,"init_req\n");
		fwrite($file,$link);
		fclose($file);

		return $id;
	}

	if(isset($_POST["link"]) && isset($_POST["init"])){
		$id = createDwonloadId($_POST["link"]);

		if(!$id) 
			die("error");
		else
			die($id);			
	}
	
	if(isset($_POST["id"]) && isset($_POST["fin"])){
		$dir = TEMP_DIR;

		$filename = $dir.$_POST["id"].".dld";
		unlink($filename);
		return;
	}

	if(isset($_POST["id"]) && isset($_POST["abort"])){
		global $dlLink;

		$dir = TEMP_DIR;
		$filename = $dir.$_POST["id"].".dld";

		while(!$file = fopen($filename, "w"));
		
		fwrite($file, "abort"."\n".$dlLink."\n");			
		fclose($file);
		return;
	}


	if(isset($_POST["id"]) && isset($_POST["status"])){
		global $globalID,$dlLink,$downloadAborted;
		
		if($downloadAborted) die("busy");
		
		$dir = TEMP_DIR;
		
		$filename = $dir.$_POST["id"].".dld";
		$file = fopen($filename, "r");

		if(!$file) die("busy");		

		$fsize = filesize($filename);

		if(!$fsize) die("busy");				

		$args = split("\n", fread($file,filesize($filename)));
		fclose($file);
		
		if($args[0] == "init_req"){
			$file = fopen($filename, "w");
			fwrite($file, "0\n".$_POST["id"]);
			fclose($file);
			
			$globalID = $_POST["id"];
			
			startDownload($args[1]);
			die("init");
		}
		else{
			if($args[0] == 1){
				die("fin ".$args[1]);
			}
			if($args[0] == "abort"){
				die("abort");
			}
			else 	die("suc ".$args[0]);
		}
	
	}
	

	function startDownload($link){ 
		global $dlLink,$downloadingFile;
		
		$cache = preg_split("%/%",$link); //split url to get filename
		$filename = $cache[count($cache)-1]; 
		$dir = DOWNLOAD_DIR; 	
		
		$curlHandle = curl_init();
		$downloadingFile = fopen($dir.$filename, "w+");
		$dlLink = $dir.$filename; 
			
		curl_setopt ($curlHandle, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curlHandle,CURLOPT_NOPROGRESS,false);
		curl_setopt($curlHandle,CURLOPT_PROGRESSFUNCTION,'curlCallback');
		curl_setopt($curlHandle, CURLOPT_BUFFERSIZE, 262144);
		curl_setopt($curlHandle, CURLOPT_FILE,$downloadingFile);
		curl_setopt ($curlHandle, CURLOPT_URL,$link);
		
		curl_exec ($curlHandle);
		//curl_close($curlHandle);

		//fclose($file);

	}
	
	$callcount;
	
	function curlCallback($downloadSize, $downloaded, $uploadSize, $uploaded){
		global $globalID,$dlLink,$downloadingFile,$downloadAborted;
		set_time_limit(60);
		
		if($downloadAborted) return;

		$dir = TEMP_DIR;
		$filename = $dir.$globalID.".dld";
		
		$file = fopen($filename, "r");
		$args = split("\n", fread($file,filesize($filename)));
		fclose($file);
				
		if($args[0] == "abort"){			
			fclose($downloadingFile);
			unlink($dlLink);
			unlink($filename);
			
			$downloadAborted = true;
			return;
		}
	
		if($downloadSize != 0){
			$file = fopen($filename, "w");
			fwrite($file, $downloaded/$downloadSize."\n".$dlLink."\n");			
			fclose($file);
		}

		
	}
	
	function closeHandle(){
		global $curlHandle;
		echo $sdas;
		curl_close($curlHandle);
	}

?> 
