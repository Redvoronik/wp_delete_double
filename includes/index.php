<?php
global $wpdb;

ini_set("memory_limit", "1024M");

function checkHeader($content): array
{
	$doubles = [];

	$preg = '|<h[23]>(.+)</h[23]>|isU';
	preg_match_all($preg, $content, $headers);

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

function removeLastParagraph(string $content)
{
	$paragraphs = explode('<h', $content);
	unset($paragraphs[count($paragraphs)-1]);

	return implode('<h', $paragraphs);
}

function removeById(int $remove_id, $wpdb): bool
{
	$article = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE `ID` = '$remove_id'")[0];
	$doubles = checkHeader($article->post_content);
	$content = $article->post_content;

	foreach ($doubles as $header) {
		$header = str_replace('?', '\?', $header);
		$header = str_replace(',', '\,', $header);
		$header = str_replace('(', '\(', $header);
		$header = str_replace(')', '\)', $header);
		$header = str_replace(':', '\:', $header);
		$header = str_replace('-', '\-', $header);
		$header = str_replace('+', '\+', $header);
		$header = str_replace('\'', '"', $header);
		$preg = '|[23]>' . $header . '</h[23]>(.+)<h|isU';
		preg_match_all($preg, $content, $paragraphs);

		unset($paragraphs[1][0]);
		unset($paragraphs[0][0]);

		foreach ($paragraphs[0] as $paragraph) {
			$content = str_replace('<h' . mb_substr($paragraph, 0, -2), '', $content);
		}
	}

	$doubles = checkHeader($content);
	if(count($doubles) > 0) {
		$content = removeLastParagraph($content);
	}

	$content = str_replace('\'', '"', $content);

	return $wpdb->query("UPDATE $wpdb->posts SET post_content = '$content'  WHERE `ID` = '$remove_id'");
}

function getArticles($wpdb): array
{
	$articles = $wpdb->get_results("SELECT ID as id, post_name, post_title, post_content FROM $wpdb->posts WHERE `post_status` = 'publish' AND (`post_content` LIKE '%<h2%' OR `post_content` LIKE '%<h3%')");

	foreach ($articles as $key => $article) {
		$doubles = checkHeader($article->post_content);
		$articles[$key]->doubles = $doubles;
		if(empty($doubles)) unset($articles[$key]);
	}

	return $articles;
}

if(isset($_GET['remove_id']) && !empty($_GET['remove_id'])) {
	removeById($_GET['remove_id'], $wpdb);
} elseif(isset($_GET['remove_all']) && !empty($_GET['remove_all'])) {
	$articles = getArticles($wpdb);
	foreach ($articles as $article) {
		removeById($article->id, $wpdb);
	}
}

$articles = getArticles($wpdb);
?>

<h1>Дубли заголовков</h1>

<a onclick="return confirm('Очистить все дубли заголовков?')" href="/wp-admin/admin.php?page=wp_delete_double%2Fincludes%2Findex.php&remove_all=true">Очистить всё</a><br>
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
				<td><a target="_blank" href="/<?= $article->post_name ?>/"><?= $article->post_title ?></a></td>
				<td><?= implode('<br>', $article->doubles) ?></td>
				<td><a href="/wp-admin/admin.php?page=wp_delete_double%2Fincludes%2Findex.php&remove_id=<?= $article->id ?>">Очистить</a></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php else: ?>
	Отлично, дублей не найдено &#128526;
<?php endif; ?>