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

    public function main()
    {
        $this->out('Crawler Start.');

        $url = "https://no1s.biz/";
        $this->execute($url, true);
        Log::debug($this->response);

        $this->dump();

        $this->out('Crawler End.');
    }

    private function execute($url, $isFirst = false)
    {
        echo ".";
        // debugでexecしたURLを出力しよう。
        // Todo: 実行中がもう少しわかるように出力すること
        // $debugCrawlerMsg = "========== start url:{$url} ==========";
        // Log::info($debugCrawlerMsg,'crawler');
        // $this->out("start target num: {$this->exeNum}");
        $dom = HtmlDomParser::file_get_html($url);

        // Todo: emptyかどうかチェックすること(エラー判定も実施すること)
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
            // Log::info("targets data:",'crawler');
            // Log::info($this->targets[$this->exeNum],'crawler');
        }

        // debug用
        $debugTargets = [];
        foreach($dom->find('a') as $key => $elem) {
            $href = $elem->href;
            // ドメインがない場合はURLを生成する。
            if (!preg_match("<^http>", $elem->href)) {
                $href = $this->generateUrl($url, $elem->href);
            }

            if ($this->isExcludeUrl($href)) {
                continue;
            }
            // -------------debug用
            // $debugTargets[$this->num]['title'] = $elem->plaintext;
            // $debugTargets[$this->num]['url'] = $href;
            // $debugTargets[$this->num]['use'] = false;
            // Log::info("debugTargets add:",'crawler');
            // Log::info($debugTargets[$this->num],'crawler');
            // -------------debug用(end)

            $this->targets[$this->num]['title'] = $elem->plaintext;
            $this->targets[$this->num]['url'] = $href;
            $this->targets[$this->num]['use'] = false;
            $this->num++;
        };
        $dom->clear();
        // Todo: 実行中がもう少しわかるように出力すること
        // $this->out("end target num: {$this->exeNum}");

        // $debugCrawlerMsg = "========== end url:{$url} ==========";
        // Log::info($debugCrawlerMsg,'crawler');

        $this->exeNum++;
        if (!empty($this->targets[$this->exeNum]['url'])) {
            $this->execute($this->targets[$this->exeNum]['url']);
        }

    }

    private function generateUrl($url, $href)
    {
        if (empty($href)) {
            // Log::info("targets href empty:",'crawler');
            // Log::info($url,'crawler');
            return $url;
        }

        // https://no1s.biz/recruit/new-graduatesnew-entry : errorになる(対応中)
        // → https://no1s.biz/recruit/new-graduates/new-entry
        // url末尾とhref先頭に/がついていない場合
        if (!preg_match("</$>", $url) && !preg_match("<^/>", $href)) {
            $generateUrl = $url."/".$href;
            // Log::info("targets href change:",'crawler');
            // Log::info($generateUrl,'crawler');
            return $generateUrl;
        }

        // https://no1s.biz/service//edtech-solution : errorにならない(未対応)
        // url末尾とhref先頭に/がついている場合
        if (preg_match("</$>", $url) && preg_match("<^/>", $href)) {
            $generateHref = ltrim($href, "/");
            $generateUrl = $url.$generateHref;
            // Log::info("targets href change rtrinm:",'crawler');
            // Log::info($generateUrl,'crawler');
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
            // Log::info("targets href mailto or #:exclude.", 'crawler');
            return true;
        }

        // 末尾がダブルスラッシュ(//)の場合除外
        if (preg_match("<//$>", $url)) {
            $url = rtrim($url, "/");
            // Log::info("targets href change rtrinm w:",'crawler');
            // Log::info($url,'crawler');
        }

        $tKey = array_search($url, array_column($this->targets, 'url'));
        $rKey = array_search($url."/", array_column($this->targets, 'url'));

        // Todo: define対応すること
        if ((!preg_match("<^https://no1s.biz/>", $url) || $tKey !== false || $rKey !== false)) {
            // Log::info("targets href is no domain:exclude.", 'crawler');
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

        // Todo: emptyかどうかチェックすること
        foreach($this->response as $key => $val) {
            $mesagge = "{$val['url']} {$val['title']}";
            $this->out($mesagge);
            // Log::info($mesagge);
        }
        $this->out("-------------------------------");
    }
}
