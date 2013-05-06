<?php
/**
*
* @package phpBB Pretty Urls
* @version $Id$
* @copyright (c) 2012 Sam Thompson <sam@emberlabs.org>
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

if (!defined('IN_PHPBB'))
{
   exit;
}

if (!defined('PHPBB_USE_BOARD_URL_PATH'))
{
	define('PHPBB_USE_BOARD_URL_PATH', true);
}

if (strpos($_SERVER['REQUEST_URI'], '?') !== false)
{
	parse_str(substr(strpos($_SERVER['REQUEST_URI'], '?'), $_SERVER['REQUEST_URI']), $query);
	foreach($query as $k => $v)
	{
		$_GET[$k] = $v;
	}
}

$forum_name = $topic_title = $script_name = '';
$forum_id = $topic_id = 0;

/**
 * Create a web friendly URL slug from a string.
 * 
 * Although supported, transliteration is discouraged because
 *     1) most web browsers support UTF-8 characters in URLs
 *     2) transliteration causes a loss of information
 *
 * @author Sean Murphy <sean@iamseanmurphy.com>
 * @copyright Copyright 2012 Sean Murphy. All rights reserved.
 * @license http://creativecommons.org/publicdomain/zero/1.0/
 *
 * @param string $str
 * @param array $options
 * @return string
 */
function url_slug($str, $options = array()) {
	// Make sure string is in UTF-8 and strip invalid UTF-8 characters
	$str = mb_convert_encoding((string)$str, 'UTF-8', mb_list_encodings());

	$defaults = array(
		'delimiter' => '-',
		'limit' => null,
		'lowercase' => true,
		'replacements' => array(),
		'transliterate' => false,
	);

	// Merge options
	$options = array_merge($defaults, $options);

	$char_map = array(
		// Latin
		'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE', 'Ç' => 'C', 
		'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 
		'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ő' => 'O', 
		'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ű' => 'U', 'Ý' => 'Y', 'Þ' => 'TH', 
		'ß' => 'ss', 
		'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c', 
		'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 
		'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ő' => 'o', 
		'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ű' => 'u', 'ý' => 'y', 'þ' => 'th', 
		'ÿ' => 'y',

		// Latin symbols
		'©' => '(c)',

		// Greek
		'Α' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D', 'Ε' => 'E', 'Ζ' => 'Z', 'Η' => 'H', 'Θ' => '8',
		'Ι' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M', 'Ν' => 'N', 'Ξ' => '3', 'Ο' => 'O', 'Π' => 'P',
		'Ρ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y', 'Φ' => 'F', 'Χ' => 'X', 'Ψ' => 'PS', 'Ω' => 'W',
		'Ά' => 'A', 'Έ' => 'E', 'Ί' => 'I', 'Ό' => 'O', 'Ύ' => 'Y', 'Ή' => 'H', 'Ώ' => 'W', 'Ϊ' => 'I',
		'Ϋ' => 'Y',
		'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'h', 'θ' => '8',
		'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => '3', 'ο' => 'o', 'π' => 'p',
		'ρ' => 'r', 'σ' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'ps', 'ω' => 'w',
		'ά' => 'a', 'έ' => 'e', 'ί' => 'i', 'ό' => 'o', 'ύ' => 'y', 'ή' => 'h', 'ώ' => 'w', 'ς' => 's',
		'ϊ' => 'i', 'ΰ' => 'y', 'ϋ' => 'y', 'ΐ' => 'i',

		// Turkish
		'Ş' => 'S', 'İ' => 'I', 'Ç' => 'C', 'Ü' => 'U', 'Ö' => 'O', 'Ğ' => 'G',
		'ş' => 's', 'ı' => 'i', 'ç' => 'c', 'ü' => 'u', 'ö' => 'o', 'ğ' => 'g', 

		// Russian
		'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh',
		'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
		'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
		'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sh', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu',
		'Я' => 'Ya',
		'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
		'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
		'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
		'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sh', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu',
		'я' => 'ya',

		// Ukrainian
		'Є' => 'Ye', 'І' => 'I', 'Ї' => 'Yi', 'Ґ' => 'G',
		'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g',

		// Czech
		'Č' => 'C', 'Ď' => 'D', 'Ě' => 'E', 'Ň' => 'N', 'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T', 'Ů' => 'U', 
		'Ž' => 'Z', 
		'č' => 'c', 'ď' => 'd', 'ě' => 'e', 'ň' => 'n', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ů' => 'u',
		'ž' => 'z', 

		// Polish
		'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'e', 'Ł' => 'L', 'Ń' => 'N', 'Ó' => 'o', 'Ś' => 'S', 'Ź' => 'Z', 
		'Ż' => 'Z', 
		'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z',
		'ż' => 'z',

		// Latvian
		'Ā' => 'A', 'Č' => 'C', 'Ē' => 'E', 'Ģ' => 'G', 'Ī' => 'i', 'Ķ' => 'k', 'Ļ' => 'L', 'Ņ' => 'N', 
		'Š' => 'S', 'Ū' => 'u', 'Ž' => 'Z',
		'ā' => 'a', 'č' => 'c', 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n',
		'š' => 's', 'ū' => 'u', 'ž' => 'z'
	);

	// Make custom replacements
	$str = preg_replace(array_keys($options['replacements']), $options['replacements'], $str);

	// Transliterate characters to ASCII
	if ($options['transliterate']) {
		$str = str_replace(array_keys($char_map), $char_map, $str);
	}

	// Replace non-alphanumeric characters with our delimiter
	$str = preg_replace('/[^\p{L}\p{Nd}]+/u', $options['delimiter'], $str);

	// Remove duplicate delimiters
	$str = preg_replace('/(' . preg_quote($options['delimiter'], '/') . '){2,}/', '$1', $str);

	// Truncate slug to max. characters
	$str = mb_substr($str, 0, ($options['limit'] ? $options['limit'] : mb_strlen($str, 'UTF-8')), 'UTF-8');

	// Remove delimiter from ends
	$str = trim($str, $options['delimiter']);

	return $options['lowercase'] ? mb_strtolower($str, 'UTF-8') : $str;
}

/*
 * Make a slug
 */
function make_slug($type, $id, $text, $start = 0)
{
	return $type . $id . '-' . url_slug($text, array('transliterate' => true)) . ($start != 0 ? '_' . $start : '') . '.html';
}

/*
 * fix the href pointing to the page
 */
function fix_href($matches)
{
	global $forum_name, $topic_title, $topic_id, $forum_id, $script_name, $user, $phpbb_root_path, $phpEx;
	static $last_topic_name = '', $last_topic_id = 0;

	$url = parse_url($matches[1]);
	$matches[2] = strip_tags($matches[2]);
	$query = array();
	$hash = $replace = '';
	if ($url !== false && isset($url['path']) && strpos($url['path'], $phpbb_root_path) === 0 && strpos($url['path'], $phpEx) !== false)
	{
		$hash = isset($url['fragment']) ? '#' . $url['fragment'] : '';

		if (isset($url['query']))
		{
			foreach(explode('&', str_replace(array('&amp;', '&#38;', '&#59;', ';'), '&', $url['query'])) as $entry)
			{
				$entry = trim($entry);
				if (!empty($entry))
				{
					list($name, $value) = explode('=', $entry);
					$query[$name] = $value;
				}
			}
		}

		$this_script = substr($url['path'], strlen($phpbb_root_path), strlen($url['path']) - strlen($phpEx) - 1 - strlen($phpbb_root_path));
		switch($this_script)
		{
			case 'index':
				$replace = $phpbb_root_path;
				break;
			case 'viewforum':
				$replace = make_slug('f', $query['f'], $matches[2], isset($query['start']) ? $query['start'] : 0);

				unset($query['f']);
				unset($query['start']);

				break;
			case 'viewtopic':
				if ($script_name == 'viewtopic' && @$query['t'] == $topic_id)
				{
					$replace = make_slug('t', $topic_id, $topic_title, isset($query['start']) ? $query['start'] : 0);
				}
				else if ($script_name == 'viewforum' && isset($query['t']))
				{
					if ($last_topic_id != $query['t'])
					{
						$last_topic_id = $query['t'];
						$last_topic_name = $matches[2];
					}

					$replace = make_slug('t', $last_topic_id, $last_topic_name, isset($query['start']) ? $query['start'] : 0);
				}
				else
				{
					$id = isset($query['p']) ? 'p' : 't';
					$text = strlen($matches[2]) < 1 ? $user->lang['NEW_POSTS'] : $matches[2];

					$replace = make_slug($id, $query[$id], $text, isset($query['start']) && $id != 'p' ? $query['start'] : 0);
				}

				unset($query['t']);
				unset($query['f']);
				unset($query['p']);
				unset($query['start']);

				break;
			case 'memberlist':
				switch(@$query['mode'])
				{
					case 'leaders':
						$replace = 'leaders.html';
						unset($query['mode']);
						break;
					case 'viewprofile':
						$replace = make_slug('u', $query['u'], $matches[2]);
						unset($query['mode']);
						unset($query['u']);
						break;
					case 'group':
						$replace = make_slug('g', $query['g'], $matches[2]);
						unset($query['mode']);
						unset($query['g']);
						break;
				}
				break;
			case 'search':
				switch(@$query['search_id'])
				{
					case 'egosearch':
						$replace = 'egosearch.html';
						unset($query['search_id']);
						break;
					case 'unanswered':
						$replace = 'unanswered.html';
						unset($query['search_id']);
						break;
					case 'unreadposts':
						$replace = 'unreadposts.html';
						unset($query['search_id']);
						break;
					case 'newposts':
						$replace = 'newposts.html';
						unset($query['search_id']);
						break;
					case 'active_topics':
						$replace = 'active-topics.html';
						unset($query['search_id']);
						break;
				}
				break;
			case 'viewonline':
				$replace = isset($query['sg']) ? 'viewonline-sg.html' : 'viewonline.html';
				unset($query['sg']);
				break;
			case 'faq':
				$replace = 'faq.html';
				break;
		}

		if ($replace && sizeof($query))
		{
			$replace .= '?' . http_build_query($query);
		}

		$replace .= $hash;
	}

	return ($replace) ? str_replace($matches[1], $replace, $matches[0]) : $matches[0];
}

/*
 * pretty_url_tpl_hook
 */
 
function pretty_url_tpl_hook(&$hook, $handle, $include_once = true, $template)
{
   global $script_name, $phpbb_root_path, $phpEx;

	$script_name = basename($_SERVER['SCRIPT_NAME']);
	$script_name = substr($script_name, 0, strlen($script_name) - 1 - strlen($phpEx));

	switch($script_name)
	{
		case 'viewforum':
			global $forum_data, $forum_name, $forum_id;
			$forum_name = $forum_data['forum_name'];
			$forum_id = $forum_data['forum_id'];
			break;
		case 'viewtopic':
			global $topic_data, $topic_title, $topic_id;
			$topic_title = $topic_data['topic_title'];
			$topic_id = $topic_data['topic_id'];

			$tp = isset($_REQUEST['p']) ? 'p' : 't';
			$id = $tp == 't' ? $topic_id : request_var('p', 0);
			$good_uri = make_slug($tp, $id, $topic_title, request_var('start', 0));
			list($req_uri) = explode('?', $_SERVER['REQUEST_URI']);
			if ($good_uri !== basename($req_uri))
			{
				redirect($phpbb_root_path . $good_uri);
			}
			break;
	}

	ob_start();
	$template->display($handle, $include_once);
	$output = ob_get_clean();

	$output = preg_replace_callback("#<a\s+.*?href=\"(.+?)\".*?>(.+?)</a>#i", 'fix_href', $output);

	echo $output;
	return true;
}

$phpbb_hook->register(array('template', 'display'), 'pretty_url_tpl_hook', 'last');