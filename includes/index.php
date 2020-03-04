<?php
global $wpdb;

function getDoubles($article): array
{
	return array_merge(checkHeader($article, 'h2'), checkHeader($article, 'h3'));
}

function checkHeader($article, string $headerTag): array
{
	$doubles = [];

	$preg = '|<' . $headerTag . '>(.+)</' . $headerTag . '>|isU';
	preg_match_all($preg, $article->post_content, $headers);

	if($headers && isset($headers[1])) {
		if(count($headers[1]) - count(array_unique($headers[1])) > 0) {
			foreach ($headers[1] as $key1 => $value1) {
				foreach ($headers[1] as $key2 => $value2) {
					if($value1 == $value2 && $key1 != $key2 && !in_array($value2, $doubles)) $doubles[] = $value2;
				}
			}
		}
	}

	return $doubles;
}

function removeById(int $remove_id, $wpdb): bool
{
	$article = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE `ID` = '$remove_id'")[0];
	$doubles = getDoubles($article);
	$content = $article->post_content;

	foreach ($doubles as $header) {
		preg_match_all('|<h2>' . $header . '</h2>(.+)<h|isU', $content, $paragraphsh2);
		preg_match_all('|<h3>' . $header . '</h3>(.+)<h|isU', $content, $paragraphsh3);

		$content = str_replace('<h2>' . $header . '</h2>' . end(array_filter($paragraphsh2)[1]), '', $content);
		$content = str_replace('<h3>' . $header . '</h3>' . end(array_filter($paragraphsh3)[1]), '', $content);
	}

	return $wpdb->query("UPDATE $wpdb->posts SET post_content = '$content'  WHERE `ID` = '$remove_id'");
}

function getArticles($wpdb): array
{
	$articles = $wpdb->get_results("SELECT ID as id, post_name, post_title, post_content FROM $wpdb->posts WHERE `post_status` = 'publish' AND (`post_content` LIKE '%<h2%' OR `post_content` LIKE '%<h3%')");

	foreach ($articles as $key => $article) {
		$doubles = getDoubles($article);
		$articles[$key]->doubles = $doubles;
		if(empty($doubles)) unset($articles[$key]);
	}

	return $articles;
}

if(isset($_GET['remove_id']) && !empty($_GET['remove_id'])) {
	removeById($_GET['remove_id'], $wpdb);
}

$articles = getArticles($wpdb);
?>

<h1>Дубли заголовков</h1>

<?php if(!empty($articles)): ?>
<table style="margin-top: 30px;" class="wp-list-table widefat fixed striped">
	<thead>
		<th>Статья</th>
		<th>Кол-во дублей</th>
		<th>Действия</th>
	</thead>
	<tbody>
		<?php foreach($articles as $key => $article): ?>
			<tr>
				<td><a target="_blank" href="/<?= $article->id ?>-<?= $article->post_name ?>.html"><?= $article->post_title ?></a></td>
				<td><?= implode('<br>', $article->doubles) ?></td>
				<td><a href="/wp-test/wp-admin/admin.php?page=wp_delete_double%2Fincludes%2Findex.php&remove_id=<?= $article->id ?>">Очистить</a></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php else: ?>
	Отлично, дублей не найдено &#128526;
<?php endif; ?>