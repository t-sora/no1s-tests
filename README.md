# no1s-tests

## 環境構築
以下のように環境構築してください。

cd ~

git clone https://github.com/t-sora/no1s-tests.git

cd ~/no1s-tests

composer install

chmod 777 bin/cake

## 使い方
### Twitter
cd ~/no1s-tests

bin/cake TwitterImage

(※)CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRETをセットしてから実施してください

### Crawler
cd ~/no1s-tests

bin/cake Crawler
