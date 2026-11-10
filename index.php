<?php
require_once __DIR__.'/db.php'; require_once __DIR__.'/functions.php';
if (!empty($_SESSION['access_code_id'])) { header('Location: report.php'); exit; }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 $code=strtoupper(trim($_POST['access_code']??''));
 if ($code==='') $error='Enter the access code provided by your administrator.';
 else {
  $hash=hash('sha256',$code);
  $stmt=$conn->prepare('SELECT id, expires_at, is_revoked FROM access_codes WHERE code_hash=? LIMIT 1');
  $stmt->bind_param('s',$hash); $stmt->execute(); $row=$stmt->get_result()->fetch_assoc();
  if (!$row || (int)$row['is_revoked']===1 || ($row['expires_at']!==null && strtotime($row['expires_at'])<time())) $error='Invalid, expired, or revoked access code.';
  else { session_regenerate_id(true); $_SESSION['access_code_id']=(int)$row['id']; $u=$conn->prepare('UPDATE access_codes SET last_used_at=NOW() WHERE id=?'); $u->bind_param('i',$_SESSION['access_code_id']); $u->execute(); header('Location: report.php'); exit; }
 }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>School Lost & Found</title><link rel="stylesheet" href="assets/style.css"><link rel="stylesheet" href="assets/dashboard-theme.css"><style>.auth-wrap{min-height:100vh;display:grid;place-items:center;padding:24px}.auth-card{width:min(440px,100%);padding:30px}.auth-card h1{font-size:27px;margin:8px 0}.logo{width:52px;height:52px;border-radius:14px;display:grid;place-items:center;background:var(--blue);font-size:26px}.error{background:#401e2b;color:#ffc2d0;padding:11px;border-radius:8px;margin:14px 0}</style></head><body><main class="auth-wrap"><section class="panel auth-card"><div class="logo">⌕</div><p class="eyebrow">CAMPUS SERVICES</p><h1>School Lost &amp; Found</h1><p class="muted">Report missing belongings and help reunite found items with their owners.</p><?php if($error): ?><div class="error"><?=e($error)?></div><?php endif; ?><form method="post"><label for="access_code">ACCESS CODE</label><input id="access_code" name="access_code" placeholder="Enter your access code" required autocomplete="off"><button class="btn primary full" type="submit">Continue to system →</button></form><p class="muted small" style="margin-top:20px">Need access? Ask your school administrator for a code.</p><a class="small" href="admin/login.php">Administrator login</a></section></main></body></html>