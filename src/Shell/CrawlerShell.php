<?php
namespace App\Shell;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\Log\Log;
use Psy\Shell as PsyShell;
use Sunra\PhpSimple\HtmlDomParser;

class CrawlerShell extends Shell
{
    private $response = [];
    private $targets = [];
    private $exeNum = 0;
    private $num = 0;
    const BASE_URL = "https://no1s.biz/";

    public function main()
    {
        $this->out("------------Crawler実行スタート------------");
        $url = self::BASE_URL;
        if (!$this->isCheckUrl($url)) {
            exit("\n------------エラー終了------------\n");
        }

        $this->execute($url, true);

        $this->dump();
        $this->out("------------Crawler実行終了------------");
    }

    /**
     * Crawler実行
     */
    private function execute($url, $isFirst = false)
    {
        echo ".";

        $dom = HtmlDomParser::file_get_html($url);
        $this->response[] = [
            'url' => $url,
            'title' => $dom->find('title', 0)->plaintext,
        ];

        if ($isFirst) {
            $this->targets[$this->num]['title'] = $dom->find('title', 0)->plaintext;
            $this->targets[$this->num]['url'] = $url;
            $this->targets[$this->num]['use'] = true;
            $this->num++;
        } else {
            $this->targets[$this->exeNum]['use'] = true;
        }

        foreach($dom->find('a') as $key => $elem) {
            $href = $elem->href;
            // ドメインがない場合はURLを生成する
            if (!preg_match("<^http>", $elem->href)) {
                $href = $this->generateUrl($url, $elem->href);
            }

            if ($this->isExcludeUrl($href)) {
                continue;
            }

            $this->targets[$this->num]['title'] = $elem->plaintext;
            $this->targets[$this->num]['url'] = $href;
            $this->targets[$this->num]['use'] = false;
            $this->num++;
        };
        $dom->clear();

        $this->exeNum++;
        if (!empty($this->targets[$this->exeNum]['url'])) {
            $this->execute($this->targets[$this->exeNum]['url']);
        }
    }

    /**
     * 実行できるかチェック
     */
    private function isCheckUrl($url)
    {
        // cURL設定
        $curl = curl_init();
        // オプション設定
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
        ];

        curl_setopt_array($curl, $options);

        // cURL実行
        $res_str = curl_exec($curl);
        $info    = curl_getinfo($curl);
        $errorNo = curl_errno($curl);
        curl_close($curl) ;

        // 200以外はエラー
        if ($info['http_code'] !== 200) {
            return false;
        }

        return true;
    }

    /**
     * URL生成
     */
    private function generateUrl($url, $href)
    {
        if (empty($href)) {
            return $url;
        }

        // url末尾とhref先頭に/がついていない場合
        if (!preg_match("</$>", $url) && !preg_match("<^/>", $href)) {
            $generateUrl = $url."/".$href;
            return $generateUrl;
        }

        // url末尾とhref先頭に/がついている場合
        if (preg_match("</$>", $url) && preg_match("<^/>", $href)) {
            $generateHref = ltrim($href, "/");
            $generateUrl = $url.$generateHref;
            return $generateUrl;
        }

        $generateUrl = $url.$href;
        return $generateUrl;
    }

    /**
     * 不要なURLか判定
     */
    private function isExcludeUrl($url)
    {
        // #header, mailto:(form)は除外
        if (preg_match("/mailto:/", $url) || (preg_match("/#/", $url))) {
            return true;
        }

        // 末尾がダブルスラッシュ(//)の場合除外
        if (preg_match("<//$>", $url)) {
            $url = rtrim($url, "/");
        }

        $tKey = array_search($url, array_column($this->targets, 'url'));
        $rKey = array_search($url."/", array_column($this->targets, 'url'));

        // Todo: define対応すること
        if ((!preg_match("<^https://no1s.biz/>", $url) || $tKey !== false || $rKey !== false)) {
            return true;
        }

        return false;
    }

    /**
     * 実行結果を出力
     */
    private function dump()
    {
        $total = count($this->response);
        $this->out("\ntotal:{$total}");
        $this->out("------------実行結果------------");
        foreach($this->response as $key => $val) {
            $mesagge = "{$val['url']} {$val['title']}";
            $this->out($mesagge);
        }
    }
}
