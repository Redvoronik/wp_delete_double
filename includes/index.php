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

function checkTable($content): bool
{
	$preg = '|<table(.+)</table>|isU';
	preg_match_all($preg, $content, $tables);
	$finded = false;

	foreach ($tables[1] as $table) {
		if(stristr($table, "</h") === FALSE) {
			$finded = true;
			break;
		}
	}

	return $finded;
}

function removeLastParagraph(string $content)
{
	$paragraphs = explode('<h', $content);
	unset($paragraphs[count($paragraphs)-1]);

	return implode('<h', $paragraphs);
}

function removeEmptyParagraphs($article, $wpdb, $forceUpdate = false)
{
	$content = $article->post_content;
	$article_id = (!empty($article->ID)) ? $article->ID : $article->id;
	$update = false;

	$preg = '|<h[23]>(.+)</h[23]>|isU';
	preg_match_all($preg, $content, $headers);

	foreach ($headers[1] as $key => $header) {
		$preg = '|[23]>' . $header . '</h[23]>(.+)<h|isU';		
		preg_match_all($preg, $content, $paragraphs);
		if(isset($paragraphs[1])) {
			$paragraphs = array_filter($paragraphs[1]);

			if(isset($paragraphs[0]) && strlen(strip_tags($paragraphs[0])) < 150) {
				$content = str_replace($headers[0][$key], '', $content);
				$update = true;
			}
		}
	}

	if($update && $forceUpdate) {
		return $wpdb->query("UPDATE $wpdb->posts SET post_content = '$content'  WHERE `ID` = '$article_id'");
	}

	return $update;
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
		$header = str_replace('.', '\.', $header);
		$header = str_replace('*', '\*', $header);
		
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

function getArticles($wpdb, $check = true): array
{
	$articles = $wpdb->get_results("SELECT ID as id, post_name, post_title, post_content FROM $wpdb->posts WHERE `post_status` = 'publish' AND (`post_content` LIKE '%<h2%' OR `post_content` LIKE '%<h3%')");

	if($check) {
		foreach ($articles as $key => $article) {
			$doubles = checkHeader($article->post_content);

			if(removeEmptyParagraphs($article, $wpdb)) $doubles[] = 'ПУСТЫЕ ЗАГОЛОВКИ';
			if(checkTable($article->post_content)) $doubles[] = 'ЗАГОЛОВОК В ТАБЛИЦЕ';

			$articles[$key]->doubles = $doubles;

			if(empty($doubles)) unset($articles[$key]);
		}
	}

	return $articles;
}

if(isset($_GET['remove_id']) && !empty($_GET['remove_id'])) {
	removeById($_GET['remove_id'], $wpdb);
	$remove_id = $_GET['remove_id'];
	$article = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE `ID` = '$remove_id'")[0];
	removeEmptyParagraphs($article, $wpdb, true);
} elseif(isset($_GET['remove_all']) && !empty($_GET['remove_all'])) {
	$articles = getArticles($wpdb);
	foreach ($articles as $article) {
		removeById($article->id, $wpdb);
	}
} elseif(isset($_GET['remove_empty']) && !empty($_GET['remove_empty'])) {
	$articles = getArticles($wpdb, false);
	foreach ($articles as $article) {
		removeEmptyParagraphs($article, $wpdb, true);
	}
}

$articles = getArticles($wpdb);
?>

<h1>Дубли заголовков</h1>

<a onclick="return confirm('Очистить все дубли заголовков?')" href="/wp-admin/admin.php?page=wp_delete_double%2Fincludes%2Findex.php&remove_all=true">Очистить всё</a>
  <a onclick="return confirm('Очистить пустые заголовки?')" href="/wp-admin/admin.php?page=wp_delete_double%2Fincludes%2Findex.php&remove_empty=true">Очистить пустые</a><br>
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