<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.0.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Shell;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\Log\Log;
use Psy\Shell as PsyShell;
use Sunra\PhpSimple\HtmlDomParser;

/**
 * Simple console wrapper around Psy\Shell.
 */
class CrawlerShell extends Shell
{
    private $response = [];
    private $targets = [];
    private $exeNum = 0;
    private $num = 0;

    public function main()
    {
        $this->out('Hello world.');
        $url = "https://no1s.biz";
        // $num = 0;

        $this->setTargets($url, true);
        Log::debug($this->response);
var_dump($this->targets);
var_dump("end");
    }

    private function setTargets($url, $isFirst = false) {
        $this->out("start target num: {$this->exeNum}");
        $dom = HtmlDomParser::file_get_html($url);
        $this->response[] = [
            'url' => $url,
            'title' => $dom->find('title', 0)->plaintext,
        ];
        if (!$isFirst) {
            $this->targets[$this->exeNum]['use'] = true;
        }

        foreach($dom->find('a') as $key => $elem) {
            if ($this->isExcludeUrl($elem->href)) {
                continue;
            }
            $this->targets[$this->num]['title'] = $elem->plaintext;
            $this->targets[$this->num]['url'] = $elem->href;
            $this->targets[$this->num]['use'] = false;
            $this->num++;
        };
        $this->out("end target num: {$this->exeNum}");

        $this->exeNum++;
        if (!empty($this->targets[$this->exeNum]['url'])) {
            $this->setTargets($this->targets[$this->exeNum]['url']);
        }

    }

    /**
     * 不要なURLか判定
     */
    private function isExcludeUrl($url) {
        $tKey = array_search($url, array_column($this->targets, 'url'));
        $rKey = array_search($url."/", array_column($this->targets, 'url'));

        // if ($url == "/" || !preg_match("<^https://no1s.biz/>", $url)) {
        // if (!preg_match("<^https://no1s.biz/>", $url)) {
        if ((!preg_match("<^https://no1s.biz/>", $url) || $tKey !== false || $rKey !== false)) {
            return true;
        }

        return false;
    }

    /**
     * Display help for this console.
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser()
    {
        $parser = new ConsoleOptionParser('console');
        $parser->setDescription(
            'This shell provides a REPL that you can use to interact ' .
            'with your application in an interactive fashion. You can use ' .
            'it to run adhoc queries with your models, or experiment ' .
            'and explore the features of CakePHP and your application.' .
            "\n\n" .
            'You will need to have psysh installed for this Shell to work.'
        );

        return $parser;
    }
}
