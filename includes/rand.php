<?php
global $wpdb;

ini_set("memory_limit", "1024M");

function getArticles($wpdb, $check = true): array
{
	$articles = $wpdb->get_results("SELECT ID as id, post_name, post_title, post_content FROM $wpdb->posts WHERE (`post_content` LIKE '%<h2%')");
	return $articles;
}

function randParagraphs($rand_id, $wpdb)
{
	$article = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE `ID` = '$rand_id'")[0];
	$content = $article->post_content;
	
	$preg = '|<h2>(.+)</h2>|isU';
	preg_match_all($preg, $content, $headers);

	$pars = [];

	foreach ($headers[1] as $key => $header) {
		$preg = '|2>' . $header . '</h2>(.+)<h2|isU';		
		preg_match_all($preg, $content, $paragraphs);
		if(isset($paragraphs[1][0])) {
			$pars[] = '<h2>' . $header . '</h2>' . $paragraphs[1][0];
		}
	}

	shuffle($pars);

	$content = implode('', $pars);

	return $wpdb->query("UPDATE $wpdb->posts SET post_content = '$content'  WHERE `ID` = '$rand_id'");
}

if(isset($_GET['rand_id']) && !empty($_GET['rand_id'])) {
	randParagraphs($_GET['rand_id'], $wpdb);
} elseif(isset($_GET['rand_all']) && !empty($_GET['rand_all'])) {
	$articles = getArticles($wpdb);
	foreach ($articles as $article) {
		randParagraphs($article->id, $wpdb);
	}
}

$articles = getArticles($wpdb);
?>

<a href="/wp-admin/admin.php?page=wp_delete_double%2Fincludes%2Findex.php">Дубли заголовков</a>
<a href="/wp-admin/admin.php?page=wp_delete_double%2Fincludes%2Frand.php">Перемешать параграфы</a>
<a href="/wp-admin/admin.php?page=wp_delete_double%2Fincludes%2Fsearch.php">Поиск</a><br><br>
<h1>Перемешать параграфы</h1>

<a onclick="return confirm('Перемешать все параграфы?')" href="/wp-admin/admin.php?page=wp_delete_double%2Fincludes%2Frand.php&rand_all=true">Перемешать всё</a>

<table style="margin-top: 30px;" class="wp-list-table widefat fixed striped">
	<thead>
		<th>Статья</th>
		<th>Действия</th>
	</thead>
	<tbody>
		<?php foreach($articles as $key => $article): ?>
			<tr>
				<td><a target="_blank" href="/<?= $article->post_name ?>/"><?= $article->post_title ?></a></td>
				<td><a href="/wp-admin/admin.php?page=wp_delete_double%2Fincludes%2Frand.php&rand_id=<?= $article->id ?>">Перемешать</a></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>