<?php
namespace App\Shell;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\Shell;
use Cake\Log\Log;
use Psy\Shell as PsyShell;

class TwitterImageShell extends Shell
{
    const CONSUMER_KEY = ""; // CONSUMER_KEYを入力
    const CONSUMER_SECRET = ""; // CONSUMER_SECRETを入力
    const ACCESS_TOKEN = ""; // ACCESS_TOKENを入力
    const ACCESS_TOKEN_SECRET = ""; // ACCESS_TOKEN_SECRETを入力
    const TWITTER_API_URL = "https://api.twitter.com/1.1/search/tweets.json";
    const OAUTH_VERSION = "1.0";
    const OAUTH_SIGNATURE_METHOD = "HMAC-SHA1";
    const TWEET_COUNT = 100; // ツイート数
    const OUTPUT_MAX = 10; // ダウンロード数

    public function main()
    {
        $response = $this->getTweetImageApi();
        if (empty($response)) {
            exit("------------エラー終了------------\n");
        }

        $dirname = dirname(__FILE__);
        $output_path = "{$dirname}/download";

        // 出力処理
        $exports = [];
        $this->out("------------出力スタート------------");
        foreach ($response['statuses'] as $key => $value) {
            if (!empty($value['extended_entities']['media'])) {
                foreach ($value['extended_entities']['media'] as $key => $media) {
                    $duplicateKey = array_search($media['media_url_https'], $exports);
                    if (!empty($media['media_url_https']) && $duplicateKey === FALSE) {
                        $this->out("{$media['media_url_https']}");
                        $exports[] = $media['media_url_https'];
                        $cnt = count($exports);
                        $image_data = file_get_contents($media['media_url_https']);
                        $extension = strstr(basename($media['media_url_https']), '.');
                        if (!file_exists($output_path)) {
                            mkdir($output_path, 0777);
                        }

                        $filename = "{$output_path}/dl{$cnt}{$extension}";
                        file_put_contents($filename, $image_data);
                        if (count($exports) == self::OUTPUT_MAX) {
                            break 2;
                        }
                    }
                }
            }
        }
        $this->out("------------出力終了------------");
        $this->out("画像ファイル出力先：{$output_path}");

        // デバック用
        Log::debug($response);
        Log::debug("========= exports ==========");
        Log::debug($exports);
    }

    public function getTweetImageApi()
    {
        //検索するキーワード
        $search_keyword = 'JustinBieber';

        $req_method = 'GET';
        $oauth_nonce = microtime();
        $oauth_timestamp = time();
        $oauth_signature = $this->generateOauthSignature($oauth_nonce, $oauth_timestamp, $req_method, $search_keyword);

        // Authorizationヘッダーの作成
        $req_oauth_header = [
            "Authorization: OAuth ".'count='.rawurlencode(self::TWEET_COUNT).
            ',oauth_consumer_key='.rawurlencode(self::CONSUMER_KEY).
            ',oauth_nonce='.str_replace(" ","+",$oauth_nonce).
            ',oauth_signature_method='.rawurlencode(self::OAUTH_SIGNATURE_METHOD).
            ',oauth_timestamp='.rawurlencode($oauth_timestamp).
            ',oauth_token='.rawurlencode(self::ACCESS_TOKEN).
            ',oauth_version='.rawurlencode(self::OAUTH_VERSION).
            ',q='.rawurlencode($search_keyword).
            ',oauth_signature='.rawurlencode($oauth_signature)
        ];

        // リクエストURL
        $url = self::TWITTER_API_URL.'?q='.rawurlencode($search_keyword).'&count='.rawurlencode(self::TWEET_COUNT);

        // cURL設定
        $curl = curl_init();
        // オプション設定
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_HEADER => false,
            CURLOPT_CUSTOMREQUEST => $req_method,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $req_oauth_header,
            CURLOPT_TIMEOUT => 5
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

    private function generateOauthSignature($oauth_nonce, $oauth_timestamp, $req_method, $search_keyword)
    {
        // パラメータ設定
        $oauth_signature_key = rawurlencode(self::CONSUMER_SECRET).'&'.rawurlencode(self::ACCESS_TOKEN_SECRET);
        $oauth_signature_param = 'count='.self::TWEET_COUNT.
            '&oauth_consumer_key='.self::CONSUMER_KEY.
            '&oauth_nonce='.rawurlencode($oauth_nonce).
            '&oauth_signature_method='.self::OAUTH_SIGNATURE_METHOD.
            '&oauth_timestamp='.$oauth_timestamp.
            '&oauth_token='.self::ACCESS_TOKEN.
            '&oauth_version='.self::OAUTH_VERSION.
            '&q='.rawurlencode($search_keyword);

        // データ部分の作成
        $oauth_signature_date = rawurlencode($req_method).'&'.rawurlencode(self::TWITTER_API_URL).'&'.rawurlencode($oauth_signature_param);
        // HMAC-SHA1方式のハッシュ値変換
        $oauth_signature_hash = hash_hmac('sha1', $oauth_signature_date, $oauth_signature_key, TRUE);
        // OAuth1.0認証の署名作成
        $oauth_signature = base64_encode($oauth_signature_hash);
        return $oauth_signature;
    }
}
