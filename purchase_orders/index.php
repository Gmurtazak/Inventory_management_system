<?php

require_once __DIR__.'/../config/app.php';
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/functions.php';

require_login();

$db = getDB();

$errors = [];
$editing = false;
$eid = 0;

/*----------------------------------------------------
DEFAULT VALUES
----------------------------------------------------*/

$po_no          = 'PO-'.date('YmdHis');
$purchase_date  = date('Y-m-d');

$supplier_id    = "";
$currency       = "PKR";
$exchange_rate  = 1;

$import_price   = 0;
$freight        = 0;
$carriage       = 0;
$insurance      = 0;
$duty           = 0;
$tax            = 0;
$other_charges  = 0;

$total_weight   = 0;
$destination    = "";
$remarks        = "";

$grand_total    = 0;
$cost_per_kg    = 0;

$status         = "Draft";

$search = trim($_GET['search'] ?? '');

/*====================================================
LOAD FOR EDIT
====================================================*/

if(isset($_GET['action']) &&
   $_GET['action']=="edit" &&
   isset($_GET['id'])){

    $eid=(int)$_GET['id'];

    $stmt=$db->prepare("SELECT * FROM purchase_orders WHERE id=?");
    $stmt->bind_param("i",$eid);
    $stmt->execute();

    $po=$stmt->get_result()->fetch_assoc();

    if($po){

        $editing=true;

        $po_no=$po['po_no'];
        $purchase_date=$po['purchase_date'];

        $supplier_id=$po['supplier_id'];

        $currency=$po['currency'];
        $exchange_rate=$po['exchange_rate'];

        $import_price=$po['import_price'];
        $freight=$po['freight'];
        $carriage=$po['carriage'];
        $insurance=$po['insurance'];
        $duty=$po['duty'];
        $tax=$po['tax'];
        $other_charges=$po['other_charges'];

        $destination=$po['destination'];
        $total_weight=$po['total_weight'];

        $grand_total=$po['grand_total'];
        $cost_per_kg=$po['cost_per_kg'];

        $status=$po['status'];
        $remarks=$po['remarks'];

    }

    $stmt->close();

}

/*====================================================
DELETE
====================================================*/

if(isset($_GET['action']) &&
   $_GET['action']=="delete" &&
   isset($_GET['id'])){

    $id=(int)$_GET['id'];

    $stmt=$db->prepare("DELETE FROM purchase_orders WHERE id=?");
    $stmt->bind_param("i",$id);

    if($stmt->execute()){

        $_SESSION['success']="Purchase Order Deleted Successfully.";

    }else{

        $_SESSION['error']=$stmt->error;

    }

    header("Location:index.php");
    exit;

}

/*====================================================
SAVE / UPDATE
====================================================*/

if($_SERVER['REQUEST_METHOD']=="POST"){

    $po_no=trim($_POST['po_no']);
    $purchase_date=$_POST['purchase_date'];

    $supplier_id=(int)$_POST['supplier_id'];

    $currency=$_POST['currency'];
    $exchange_rate=(float)$_POST['exchange_rate'];

    $import_price=(float)$_POST['import_price'];
    $freight=(float)$_POST['freight'];
    $carriage=(float)$_POST['carriage'];
    $insurance=(float)$_POST['insurance'];
    $duty=(float)$_POST['duty'];
    $tax=(float)$_POST['tax'];
    $other_charges=(float)$_POST['other_charges'];

    $destination=trim($_POST['destination']);
    $total_weight=(float)$_POST['total_weight'];

    $remarks=trim($_POST['remarks']);

    $status=$_POST['status'];

    $grand_total=
        $import_price+
        $freight+
        $carriage+
        $insurance+
        $duty+
        $tax+
        $other_charges;

    $cost_per_kg=0;

    if($total_weight>0){

        $cost_per_kg=$grand_total/$total_weight;

    }

    if($editing){

        $stmt=$db->prepare("
        UPDATE purchase_orders SET

        supplier_id=?,
        purchase_date=?,

        currency=?,
        exchange_rate=?,

        import_price=?,
        freight=?,
        carriage=?,
        insurance=?,
        duty=?,
        tax=?,
        other_charges=?,

        destination=?,

        total_weight=?,

        grand_total=?,
        cost_per_kg=?,

        status=?,
        remarks=?

        WHERE id=?

        ");

        $stmt->bind_param(

        "issddddddddsdddssi",

        $supplier_id,
        $purchase_date,

        $currency,
        $exchange_rate,

        $import_price,
        $freight,
        $carriage,
        $insurance,
        $duty,
        $tax,
        $other_charges,

        $destination,

        $total_weight,

        $grand_total,
        $cost_per_kg,

        $status,
        $remarks,

        $eid

        );

    }else{

        $stmt=$db->prepare("

        INSERT INTO purchase_orders(

        po_no,
        supplier_id,
        purchase_date,

        currency,
        exchange_rate,

        import_price,
        freight,
        carriage,
        insurance,
        duty,
        tax,
        other_charges,

        destination,

        total_weight,

        grand_total,
        cost_per_kg,

        status,
        remarks

        )

        VALUES

        (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)

        ");

        $stmt->bind_param(

        "sissdddddddddsddss",

        $po_no,
        $supplier_id,
        $purchase_date,

        $currency,
        $exchange_rate,

        $import_price,
        $freight,
        $carriage,
        $insurance,
        $duty,
        $tax,
        $other_charges,

        $destination,

        $total_weight,

        $grand_total,
        $cost_per_kg,

        $status,
        $remarks

        );

    }

    if($stmt->execute()){

        $_SESSION['success']="Purchase Order Saved Successfully.";

        header("Location:index.php");

        exit;

    }else{

        $errors[]=$stmt->error;

    }

}

/*====================================================
LIST
====================================================*/

if($search!=""){

    $like="%".$search."%";

    $stmt=$db->prepare("

    SELECT

    p.*,
    s.name supplier_name

    FROM purchase_orders p

    LEFT JOIN suppliers s

    ON s.id=p.supplier_id

    WHERE

    p.po_no LIKE ?

    OR s.name LIKE ?

    ORDER BY p.id DESC

    ");

    $stmt->bind_param("ss",$like,$like);

    $stmt->execute();

    $result=$stmt->get_result();

}else{

    $result=$db->query("

    SELECT

    p.*,
    s.name supplier_name

    FROM purchase_orders p

    LEFT JOIN suppliers s

    ON s.id=p.supplier_id

    ORDER BY p.id DESC

    ");

}

require __DIR__.'/../includes/header.php';

?>
<style>
.po-grid-2{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:15px;
    margin-bottom:15px;
}

.po-grid-3{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:15px;
    margin-bottom:15px;
}

.po-section{
    border:1px solid #e5e7eb;
    border-radius:10px;
    margin-bottom:20px;
    background:#fff;
}

.po-section-header{
    background:#f8fafc;
    padding:12px 18px;
    border-bottom:1px solid #e5e7eb;
    font-weight:600;
    color:#374151;
}

.po-section-body{
    padding:20px;
}

@media(max-width:992px){

.po-grid-2,
.po-grid-3{

grid-template-columns:1fr;

}

}
</style>
<div class="card">

<div class="card-header">

<div class="card-title">

<i class="fa-solid fa-file-invoice-dollar"></i>

<?= $editing ? 'Edit Purchase Order' : 'New Purchase Order'; ?>

</div>

<?php if($editing){ ?>

<a href="index.php" class="btn btn-secondary btn-sm">

<i class="fa fa-times"></i>

Cancel

</a>

<?php } ?>

</div>

<form method="POST">

<?php if($editing){ ?>

<input type="hidden" name="id" value="<?=$eid?>">

<?php } ?>

<!-- ================================================= -->
<!-- Purchase Information -->
<!-- ================================================= -->

<div class="po-section">

<div class="po-section-header">

<i class="fa fa-file-invoice"></i>

Purchase Information

</div>

<div class="po-section-body">

<div class="po-grid-2">

<div class="form-group">

<label>Purchase Order No</label>

<input
type="text"
name="po_no"
class="form-control"
readonly
value="<?=htmlspecialchars($po_no)?>">

</div>

<div class="form-group">

<label>Purchase Date</label>

<input
type="date"
name="purchase_date"
class="form-control"
value="<?=$purchase_date?>">

</div>

</div>

<div class="po-grid-2">

<div class="form-group">

<label>Supplier</label>

<select
name="supplier_id"
class="form-control"
required>

<option value="">Select Supplier</option>

<?php

$suppliers=$db->query("SELECT id,name FROM suppliers ORDER BY name");

while($sup=$suppliers->fetch_assoc()){

?>

<option
value="<?=$sup['id']?>"
<?=$supplier_id==$sup['id']?'selected':'';?>>

<?=htmlspecialchars($sup['name'])?>

</option>

<?php } ?>

</select>

</div>

<div class="form-group">

<label>Destination</label>

<input
type="text"
name="destination"
class="form-control"
value="<?=htmlspecialchars($destination)?>">

</div>

</div>

<div class="po-grid-3">

<div class="form-group">

<label>Currency</label>

<select
name="currency"
class="form-control">

<option <?=$currency=="PKR"?'selected':''?>>PKR</option>
<option <?=$currency=="USD"?'selected':''?>>USD</option>
<option <?=$currency=="EUR"?'selected':''?>>EUR</option>
<option <?=$currency=="CNY"?'selected':''?>>CNY</option>

</select>

</div>

<div class="form-group">

<label>Exchange Rate</label>

<input
type="number"
step="0.0000"
name="exchange_rate"
class="form-control"
value="<?=$exchange_rate?>">

</div>

<div class="form-group">

<label>Status</label>

<select
name="status"
class="form-control">

<option value="Draft" <?=$status=="Draft"?'selected':''?>>Draft</option>

<option value="Approved" <?=$status=="Approved"?'selected':''?>>Approved</option>

<option value="Received" <?=$status=="Received"?'selected':''?>>Received</option>

</select>

</div>

</div>

</div>

</div>

<!-- ================================================= -->
<!-- Import Charges -->
<!-- ================================================= -->

<div class="po-section">

<div class="po-section-header">

<i class="fa fa-ship"></i>

Import Charges

</div>

<div class="po-section-body">

<div class="po-grid-3">

<div class="form-group">
<label>Import Price</label>
<input type="number" step="0.01" id="import_price" name="import_price" class="form-control calc" value="<?=$import_price?>">
</div>

<div class="form-group">
<label>Freight</label>
<input type="number" step="0.01" id="freight" name="freight" class="form-control calc" value="<?=$freight?>">
</div>

<div class="form-group">
<label>Carriage</label>
<input type="number" step="0.01" id="carriage" name="carriage" class="form-control calc" value="<?=$carriage?>">
</div>

</div>

<div class="po-grid-3">

<div class="form-group">
<label>Insurance</label>
<input type="number" step="0.01" id="insurance" name="insurance" class="form-control calc" value="<?=$insurance?>">
</div>

<div class="form-group">
<label>Duty</label>
<input type="number" step="0.01" id="duty" name="duty" class="form-control calc" value="<?=$duty?>">
</div>

<div class="form-group">
<label>GST / Tax</label>
<input type="number" step="0.01" id="tax" name="tax" class="form-control calc" value="<?=$tax?>">
</div>

</div>

<div class="po-grid-2">

<div class="form-group">
<label>Other Charges</label>
<input type="number" step="0.01" id="other_charges" name="other_charges" class="form-control calc" value="<?=$other_charges?>">
</div>

<div class="form-group">
<label>Total Weight (KG)</label>
<input type="number" step="0.01" id="weight" name="total_weight" class="form-control" value="<?=$total_weight?>">
</div>

</div>

</div>

</div>

<!-- ================================================= -->
<!-- Summary -->
<!-- ================================================= -->

<div class="po-section">

<div class="po-section-header">

<i class="fa fa-calculator"></i>

Summary

</div>

<div class="po-section-body">

<div class="po-grid-2">

<div class="form-group">

<label>Grand Total</label>

<input
type="text"
id="grand_total"
name="grand_total"
readonly
class="form-control"
value="<?=number_format($grand_total,2,'.','')?>">

</div>

<div class="form-group">

<label>Cost Per KG</label>

<input
type="text"
id="cost_per_kg"
name="cost_per_kg"
readonly
class="form-control"
value="<?=number_format($cost_per_kg,2,'.','')?>">

</div>

</div>

<div class="form-group">

<label>Remarks</label>

<textarea
name="remarks"
rows="3"
class="form-control"><?=htmlspecialchars($remarks)?></textarea>

</div>

</div>

</div>

<div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;">

<button type="submit" class="btn btn-primary">

<i class="fa fa-save"></i>

<?= $editing ? 'Update Purchase Order' : 'Save Purchase Order' ?>

</button>

<a href="print.php" class="btn btn-success">

<i class="fa fa-print"></i>

Print

</a>

</div>

</form>

</div>
<!-- ===================================================== -->
<!-- PURCHASE ORDER LIST -->
<!-- ===================================================== -->

<div class="table-card">

    <div class="table-toolbar">

        <div class="table-toolbar-title">
            <i class="fa-solid fa-file-invoice-dollar"></i>
            Purchase Order List
        </div>

        <div class="table-toolbar-actions">

            <form method="GET" style="display:flex;gap:10px;">

                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="Search PO No / Supplier..."
                    value="<?=htmlspecialchars($search)?>">

                <button class="btn btn-primary">

                    <i class="fa fa-search"></i>

                </button>

                <?php if($search!=""){ ?>

                <a href="index.php" class="btn btn-secondary">

                    Reset

                </a>

                <?php } ?>

            </form>

        </div>

    </div>

    <div style="padding:10px 20px;font-size:13px;color:#6b7280;">

        Total Records :
        <strong><?=mysqli_num_rows($result)?></strong>

    </div>

    <div class="tbl-scroll">

        <table class="table table-bordered table-hover">

            <thead>

            <tr>

                <th>#</th>

                <th>PO No</th>

                <th>Date</th>

                <th>Supplier</th>

                <th>Currency</th>

                <th>Import</th>

                <th>Freight</th>

                <th>Duty</th>

                <th>Tax</th>

                <th>Grand Total</th>

                <th>Cost/KG</th>

                <th>Status</th>

                <th width="170">Action</th>

            </tr>

            </thead>

            <tbody>

<?php

$i=1;

if(mysqli_num_rows($result)>0):

while($row=$result->fetch_assoc()):

?>

<tr>

<td><?=$i++?></td>

<td>

<strong>

<?=htmlspecialchars($row['po_no'])?>

</strong>

</td>

<td>

<?=date("d-m-Y",strtotime($row['purchase_date']))?>

</td>

<td>

<?=htmlspecialchars($row['supplier_name'])?>

</td>

<td>

<?=htmlspecialchars($row['currency'])?>

</td>

<td align="right">

<?=number_format($row['import_price'],2)?>

</td>

<td align="right">

<?=number_format($row['freight'],2)?>

</td>

<td align="right">

<?=number_format($row['duty'],2)?>

</td>

<td align="right">

<?=number_format($row['tax'],2)?>

</td>

<td align="right">

<strong>

<?=number_format($row['grand_total'],2)?>

</strong>

</td>

<td align="right">

<?=number_format($row['cost_per_kg'],2)?>

</td>

<td>

<?php

switch($row['status']){

case 'Approved':

echo '<span class="badge badge-success">Approved</span>';

break;

case 'Received':

echo '<span class="badge badge-info">Received</span>';

break;

default:

echo '<span class="badge badge-warning">Draft</span>';

}

?>

</td>

<td>

<a
href="print.php?id=<?=$row['id']?>"
class="btn btn-info btn-sm"
title="Print">

<i class="fa fa-print"></i>

</a>

<a
href="?action=edit&id=<?=$row['id']?>"
class="btn btn-warning btn-sm"
title="Edit">

<i class="fa fa-edit"></i>

</a>

<a
href="?action=delete&id=<?=$row['id']?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Delete this Purchase Order?')"
title="Delete">

<i class="fa fa-trash"></i>

</a>

</td>

</tr>

<?php

endwhile;

else:

?>

<tr>

<td colspan="13" style="text-align:center;padding:25px;">

No Purchase Orders Found.

</td>

</tr>

<?php endif; ?>

            </tbody>

        </table>

    </div>

</div>  
<script>
function calculate(){

var import_price=parseFloat(document.getElementById("import_price").value)||0;

var freight=parseFloat(document.getElementById("freight").value)||0;

var carriage=parseFloat(document.getElementById("carriage").value)||0;

var duty=parseFloat(document.getElementById("duty").value)||0;

var tax=parseFloat(document.getElementById("tax").value)||0;

var weight=parseFloat(document.getElementById("weight").value)||0;

var total=import_price+freight+carriage+duty+tax;

document.getElementById("grand_total").value=total.toFixed(2);

if(weight>0){

document.getElementById("cost_per_kg").value=(total/weight).toFixed(2);

}

}

document.querySelectorAll("input").forEach(function(i){

i.addEventListener("keyup",calculate);

});
</script>
<?php require __DIR__.'/../includes/footer.php'; ?>