<?php
namespace App\Shell;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\Log\Log;
use Psy\Shell as PsyShell;

class SheetsApiShell extends Shell
{
    const GOOGLE_SHEETS_API_URL = "https://sheets.googleapis.com/v4/spreadsheets/";
    const SHEETS_ID = "11BCnspCt2Mut3nhc4WMY6CYTd0zF9C3eCzsk1AEpKLM";
    const API_KEY = ""; // API_KEYを入力していください

    public function main()
    {
        $this->out("------------SheetsApi実行スタート------------");

        // GoogleSheetsApi実行
        $response = $this->getGoogleSheetsApiData();

        // Errorチェック
        if (empty($response) || empty($response["values"])) {
            exit("\n------------エラー終了------------\n");
        }

        // 出力
        foreach($response["values"] as $key => $row_data) {
            $message = "'".implode("','", $row_data)."',";
            $this->out($message);
        }

        $this->out("------------SheetsApi実行終了------------");
    }

    public function getGoogleSheetsApiData()
    {
        // 検索範囲
        $search_range = "sales!A1:E6";

        // リクエストURL
        $url = self::GOOGLE_SHEETS_API_URL.self::SHEETS_ID.'/values/'.$search_range.'?key='.self::API_KEY;

        // cURL設定
        $curl = curl_init();
        // オプション設定
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3
        ];
        curl_setopt_array($curl, $options);

        // cURL実行
        $res_str = curl_exec($curl);
        $info    = curl_getinfo($curl);
        $errorNo = curl_errno($curl);
        curl_close($curl) ;

        // 200以外はエラー
        if ($info['http_code'] !== 200) {
            return [];
        }

        return json_decode($res_str, true);
    }
}
