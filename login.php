<?php
require_once __DIR__.'/../db.php'; require_once __DIR__.'/../functions.php';
if(!empty($_SESSION['admin_id'])){header('Location: dashboard.php');exit;}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $username=trim($_POST['username']??'');$password=$_POST['password']??'';
 $stmt=$conn->prepare('SELECT id,username,password_hash FROM admins WHERE username=? LIMIT 1');$stmt->bind_param('s',$username);$stmt->execute();$admin=$stmt->get_result()->fetch_assoc();
 if($admin&&password_verify($password,$admin['password_hash'])){session_regenerate_id(true);$_SESSION['admin_id']=(int)$admin['id'];$_SESSION['admin_username']=$admin['username'];header('Location: dashboard.php');exit;}
 $error='Incorrect administrator username or password.';
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Administrator Login</title><link rel="stylesheet" href="../assets/style.css"><link rel="stylesheet" href="../assets/dashboard-theme.css"><style>.auth-wrap{min-height:100vh;display:grid;place-items:center;padding:24px}.auth-card{width:min(420px,100%);padding:30px}.error{background:#48202d;color:#ffc2d0;padding:11px;border-radius:8px}</style></head><body><main class="auth-wrap"><section class="panel auth-card"><p class="eyebrow">SYSTEM ADMINISTRATION</p><h1>Administrator login</h1><p class="muted">Manage reports and access codes.</p><?php if($error):?><p class="error"><?=e($error)?></p><?php endif;?><form method="post"><label>Username</label><input name="username" required autocomplete="username"><label style="margin-top:14px">Password</label><input type="password" name="password" required autocomplete="current-password"><button class="btn primary full" type="submit">Sign in</button></form><p><a href="../index.php">← Back to system</a></p></section></main></body></html>