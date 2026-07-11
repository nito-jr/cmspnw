<?php
ini_set('display_errors',1);
session_start();
include 'config.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','operator']))
    die(json_encode(['status'=>'error','message'=>'Unauthorized']));

function getVal($k,$d,$m,$def=''){return isset($m[$k])&&isset($d[$m[$k]])?trim($d[$m[$k]]):$def;}

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error']!=0)
    die(json_encode(['status'=>'error','message'=>'No file uploaded.']));

$handle = fopen($_FILES['csv_file']['tmp_name'], "r");
$bom = fread($handle, 3);
if ($bom != "\xEF\xBB\xBF") rewind($handle);

$header = fgetcsv($handle, 1000, ",");
$map = [];
foreach ($header as $i => $col) {
    $c = strtolower(trim($col));
    if (strpos($c,'name')!==false && !isset($map['name'])) $map['name']=$i;
    if (strpos($c,'email')!==false)       $map['email']=$i;
    if (strpos($c,'phone')!==false)       $map['phone']=$i;
    if (strpos($c,'address')!==false)     $map['address']=$i;
    if (strpos($c,'profession')!==false)  $map['profession']=$i;
    if (strpos($c,'school')!==false)      $map['school']=$i;
    if (strpos($c,'grad')!==false)        $map['grad']=$i;
    if (strpos($c,'license')!==false)     $map['license']=$i;
    if (strpos($c,'trans_date')!==false)  $map['trans_date']=$i;
    if (strpos($c,'trans_amount')!==false)$map['trans_amount']=$i;
    if (strpos($c,'trans_type')!==false)  $map['trans_type']=$i;
    if (strpos($c,'trans_status')!==false)$map['trans_status']=$i;
}

$count_members=0; $count_trans=0; $errors=[];
$currentYear=date("Y"); $cache=[];

while (($row=fgetcsv($handle,1000,","))!==FALSE) {
    if (!count(array_filter($row))) continue;
    $name = getVal('name',$row,$map);
    if (empty($name)) continue;

    $email = getVal('email',$row,$map);
    if (empty($email)) $email="member_".uniqid()."@cmsp.temp";

    $phone   = getVal('phone',$row,$map);
    $address = getVal('address',$row,$map);
    $prof    = getVal('profession',$row,$map,'Other');
    $school  = getVal('school',$row,$map,'Unknown');
    $license = getVal('license',$row,$map,'');
    $grad    = getVal('grad',$row,$map,$currentYear);

    $uid = null;
    if (isset($cache[$email])) {
        $uid = $cache[$email];
    } else {
        $chk = $conn->query("SELECT id FROM users WHERE email='".$conn->real_escape_string($email)."'");
        if ($chk->num_rows>0) {
            $uid = $chk->fetch_assoc()['id'];
        } else {
            $pass   = password_hash('password123',PASSWORD_DEFAULT);
            $grad_int = intval($grad);
            $fees   = calculateFees($grad_int,$currentYear);
            $bal    = $fees['application']+$fees['dues']+$fees['platform_charge'];
            $plat   = $fees['platform_charge'];
            $currentYear_int = intval($currentYear);

            $stmt_ins = $conn->prepare("INSERT INTO users (name,email,phone,address,password,profession,school,year_graduation,status,license_number,year_registration,balance_due,platform_charge,role,photo)
                  VALUES (?,?,?,?,?,?,?,?,'approved',?,?,?,?,'member','default.jpg')");
            $stmt_ins->bind_param("sssssssisidd", $name, $email, $phone, $address, $pass, $prof, $school, $grad_int, $license, $currentYear_int, $bal, $plat);
            if ($stmt_ins->execute()) { $uid=$stmt_ins->insert_id; $count_members++; }
            else { $errors[]="Failed: $name: ".$stmt_ins->error; continue; }
        }
        $cache[$email]=$uid;
    }

    if (isset($map['trans_amount']) && isset($row[$map['trans_amount']]) && !empty($row[$map['trans_amount']])) {
        $tdate  = getVal('trans_date',$row,$map);
        $tamt   = floatval(getVal('trans_amount',$row,$map));
        $ttype  = getVal('trans_type',$row,$map,'dues');
        $tstat  = getVal('trans_status',$row,$map,'approved');
        if (!in_array($ttype,['application','dues','platform_charge'])) $ttype='dues';
        if ($uid && $tamt>0) {
            $s=$conn->prepare("INSERT INTO payments (user_id,amount,payment_type,status,created_at) VALUES (?,?,?,?,?)");
            $s->bind_param("idsss",$uid,$tamt,$ttype,$tstat,$tdate); $s->execute();
            if ($tstat==='approved') {
                $u=$conn->prepare("UPDATE users SET balance_due=balance_due-? WHERE id=?");
                $u->bind_param("di",$tamt,$uid); $u->execute();
            }
            $count_trans++;
        }
    }
}
fclose($handle);
$msg="Import complete: $count_members members added, $count_trans transactions restored.";
if (!empty($errors)) $msg.=" Errors: ".implode("; ",$errors);
echo json_encode(['status'=>'success','message'=>$msg]);
?>
