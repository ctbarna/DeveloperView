<?php
$title = "Usage Statistics";
include('header.php');

$users = mysql_array(mysql_query("SELECT ID, Name, (SELECT COUNT(*) FROM `notes` WHERE UserID = users.ID) as Notes, (SELECT COUNT(*) FROM `tags-pages` WHERE `UserID` = users.ID) as Tags FROM `users` ORDER BY `tags` DESC"));
?>
<style>
	th {font-weight: bold; border-bottom:1px solid black;}
	td {padding-right: 50px;}
	.gray {background: #ccc;}
	.name {font-weight: bold;}
</style>
<table>
	<tr>
		<th>User</th>
		<th>Pgs. Tagged</th>
		<th>Notes</th>
	</tr>
<?php 
$i=0;
foreach ($users as $user) { ?>
	<tr <?php if ($i&1) echo "class='gray'"; ?>>
		<td class='name'><?php echo clean_name($user['Name']); ?></td>
		<td><?php echo $user['Tags']; ?></td>
		<td><?php echo $user['Notes']; ?></td>
	</tr>
<?php $i++; } ?>
</table>	
<?php include('footer.php'); ?> 