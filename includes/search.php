<?php
global $wpdb;

ini_set("memory_limit", "1024M");

function getArticles($wpdb, $str = ''): array
{
	$query = "SELECT ID as id, post_name, post_title, post_content FROM $wpdb->posts WHERE `post_title` LIKE '%{$str}%'";
	
	$articles = $wpdb->get_results($query);
	return $articles;
}

function updateParagraph($wpdb, $str, $article_id)
{
	return $wpdb->query("UPDATE $wpdb->posts SET post_title = REPLACE(post_title, '{$str}', '')");
}

if(isset($_GET['str']) && isset($_GET['article_id'])) {
	updateParagraph($wpdb, $_GET['str'], $_GET['article_id']);
	$articles = getArticles($wpdb, $_GET['str']);
} elseif(isset($_GET['str'])) {
	$articles = getArticles($wpdb, $_GET['str']);
} else {
	$articles = getArticles($wpdb);
}


?>

<a href="/wp-admin/admin.php?page=wp_delete_double%2Fincludes%2Findex.php">Дубли заголовков</a>
<a href="/wp-admin/admin.php?page=wp_delete_double%2Fincludes%2Frand.php">Перемешать параграфы</a>
<a href="/wp-admin/admin.php?page=wp_delete_double%2Fincludes%2Fsearch.php">Поиск</a><br><br>
<h1>Поиск</h1>

<form method="get">
	<input type="hidden" name="page" value="wp_delete_double/includes/search.php">
	<input type="text" name="str" value="<?= $_GET['str'] ?? null ?>">
	<button type="submit">Найти</button>
</form>

<?php if(isset($_GET['str'])): ?>
<b>ВНИМАНИЕ! Что в строке поиска введено, то и будет удаляться из заголовков.</b>
<?php endif; ?>

<table style="margin-top: 30px;" class="wp-list-table widefat fixed striped">
	<thead>
		<th>Статья</th>
		<th>Действия</th>
	</thead>
	<tbody>
		<?php foreach($articles as $key => $article): ?>
			<tr>
				<td><a target="_blank" href="/<?= $article->post_name ?>/"><?= $article->post_title ?></a></td>
				<td><a href="/wp-admin/admin.php?page=wp_delete_double%2Fincludes%2Fsearch.php&article_id=<?= $article->id ?>&str=<?= $_GET['str'] ?>">Удалить</a></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>