<?php
$title = "Import Metadata";
include('header.php');
if (!isset($_POST['step'])) $_POST['step'] = 1;

switch ($_POST['step']) {
	case '1':
?>
<form method='post' enctype="multipart/form-data">
		<input type='hidden' name='step' value='2'>
		<div class='step'>Step 1: Select File</div>
		<div class='instructions'> File must be a .CSV, have a header row to label each column, and at least one column must be the URL (page) associated with that row.</div>
		<div class='form-row'>
			<div class='form-label'>
				<label for='file'>Select File to Upload</label>: 
			</div>
			<div class='form-field'>
				<input type='file' name='file' id='file' value='blah'/>
			</div>
		</div>
		<div class='form-row'>
			<div class='form-label'> &nbsp;
			</div>
			<div class='form-field'>

				<input type='submit' value='Upload'>
			</div>
		</div>
<?php 
	break;
	case '2':
?>
<form method='post' enctype="multipart/form-data">
		<div class='step'>Step 1: Select File</div>
		
		<input type='hidden' name='step' value='3'>
		<div class='form-row'>
			<div class='form-label'>
				<label for='file'>Select File to Upload</label>: 
			</div>
			<div class='form-field'>
				<input type='text' name='file' id='file' value='<?php echo $_FILES['file']['name']; ?>' disabled /> 	<input type='button' value='Browse' disabled />
			</div>
		</div>
<?php
move_uploaded_file($_FILES['file']['tmp_name'], $_SERVER['TMP'] . '\dv.tmp');
$file = fopen($_SERVER['TMP'] . '\dv.tmp','r'); 
$fields = fgetcsv($file);
?>
	<div class='step'>Step 2: Select Fields</div>
	<div class='instructions'>Please indicate which column is the page's URL, and select which fields to import.</div>

		<div class='form-row'>
			<div class='form-label'>
				<label for='url'>Select URL Field</label>
			</div>
			<div class='form-field'>
				<select name='url' id='url'>
				<?php foreach ($fields as $key=>$field) { ?>
					<option value='<?php echo $key; ?>'><?php echo $field; ?></option>
				<?php } ?>
				</select>
			</div>
		</div>
		<div class='form-row'>
			<div class='form-label'>
				<label>Select Fields to Import</label>
			</div>
			<div class='form-field'>
				<?php foreach ($fields as $key=>$field) { ?>
					<input type='checkbox' id='field-<?php echo $key; ?>' name='field-<?php echo $key; ?>' checked='checked'/>
					<label for='field-<?php echo $key; ?>'><?php echo $field; ?></label><br />
				<?php } ?>
			</div>
		</div>
<div class='step'>Step 3: Label Your Dataset</div>
	<div class='instructions'>Please provide your dataset with a unique label (e.g. 3d Qtr. Report 2010).  This will allow you to update or remove the data in the future. <br /> If you would like to remove a previous dataset as you import this one, please indicate so below.</div>

		<div class='form-row'>
			<div class='form-label'>
				<label for='label'>Dataset Label</label>:
			</div>
			<div class='form-field'>
				<input type='text' name='label' id='label' size='50'>
			</div>
		</div>
		<div class='form-row'>
			<div class='form-label'>
				<label for='replace'>Replace Dataset:</label>:
			</div>
			<div class='form-field'>
				<select name='replace' id='replace'>
					<option value='0'>None</option>
					<?php foreach (get_sources() as $source) { ?>
					<option value='<?php echo $source['ID']; ?>'><?php echo $source['Name']; ?> <?php echo date('Y-m-d',$source['Date']); ?></option>
					<?php } ?>
				</select>
			</div>
		</div>				
		
<div class='step'>Step 4: Import</div>
	<div class='instructions'>There's no form validation yet, so make sure you did everything right.</div>

		<div class='form-row'>
			<div class='form-label'> &nbsp;
			</div>
			<div class='form-field'>
				<input type='submit' value='Import'>
			</div>
		</div>
	</form>
<?php 
	fclose($file); 
	break;
	case '3':
	if ($_POST['replace']) remove_source($_POST['replace']);
	
	$source = add_source($_POST['label']);
	$file = fopen($_SERVER['TMP'] . '\dv.tmp','r');
	$row = 0;
	while ($line = fgetcsv($file)) {
		$row++;
		if ($row == 1) {
			$fields = $line;
			continue;
		}
		
		$query = array('url'=>$line[$_POST['url']]);
		if (!mysql_exists("pages",$query)) $pageID = add_page($line[$_POST['url']]);
		else $pageID = get_page_ID($line[$_POST['url']]);	
		
		unset($line[$_POST['url']]);
		
		foreach ($line as $key=>$value) {
			if (!isset($_POST['field-' . $key])) continue;
			add_meta($pageID,$source,$fields[$key],$value);
		}
	}
	
	echo "<p>" . number_format($row) . " row";
	if ($row != 1) echo "s";
	echo " added sucesfully!</p>";
?>
	
<?php
	break;
 } ?>
 
<?php include('footer.php'); ?>
