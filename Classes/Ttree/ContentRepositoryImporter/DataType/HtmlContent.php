<?php
namespace Ttree\ContentRepositoryImporter\DataType;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Ttree.ArchitectesCh".   *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * String Data Type
 */
class HtmlContent extends DataType {

	protected $value;

	/**
	 * @param string $value
	 */
	protected function initializeValue($value) {
		$value = trim($value);

		$config = \HTMLPurifier_Config::createDefault();
		$config->set('HTML.AllowedElements', 'a,em,i,strong,b,blockquote,p,ul,ol,li');
		$config->set('HTML.AllowedAttributes', 'a.href,a.title');
		$config->set('HTML.TidyLevel', 'light');
		$purifier = new \HTMLPurifier($config);
		$value = $purifier->purify($value);

		$value = str_replace([
			'<ul>',
			'</ul>',
			'<ol>',
			'</ol>',
			'&nbsp;'
		], [
			PHP_EOL . '<ul>' . PHP_EOL,
			PHP_EOL . '</ul>' . PHP_EOL,
			PHP_EOL . '<ol>' . PHP_EOL,
			PHP_EOL . '</ol>' . PHP_EOL,
			' '
		], $value);

		$value = preg_replace([
			'#\[b\](.+)\[/b\]#i',
			'#\[i\](.+)\[/i\]#i',
			"/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/"
		], [
			'<strong>$1</strong>',
			'<em>$1</em>',
			"\n"
		], $value);

		$lines = preg_split("/\\r\\n|\\r|\\n/", $value);
		foreach ($lines as $key => $line) {
			# TODO Check for side effect
			$line = trim($line);
			$lines[$key] = preg_replace('#^<b>(.+)</b>$#', '<h3>$1</h3>', $line);
		}
		$value = implode(PHP_EOL, $lines);

		$value = preg_replace('#^(?!.*?(?:<.*ul>|<.*li>|<.*ol>|<.*h.*>))(.+)$#uim', '<p>$1</p>', $value);
		$value = str_replace(PHP_EOL, '', $value);

		$value = new String($value);

		$this->value = $value->getValue();
	}

}