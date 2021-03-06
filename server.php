<?php

include($_SERVER['DOCUMENT_ROOT'] . '/simple_html_dom_node.php');


$wechatObj = new wechatCallbackapiTest();
$wechatObj->responseMsg();


class wechatCallbackapiTest
{


    public function responseMsg()
    {

        //$this->get_access_token();

        $postStr = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : file_get_contents("php://input");

        if (!empty($postStr)) {

            libxml_disable_entity_loader(true);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);

            $keyword = trim($postObj->Content);
            

            if (ctype_digit($keyword) && strlen($keyword) == 6) {

                $arr = wechatCallbackapiTest::gather_information($keyword);

                if ($arr[0] != '') {

                    $contentStr[] = array("Title" => $arr[0],
                        "Description" => $arr[1],
                        "PicUrl" => $arr[2],
                        "Url" => 'http://stock.jiuzer.cn/stock/detail?code=' . $keyword);

                    $resultStr = $this->transmitNews($postObj, $contentStr);

                } else {
                    $contentStr = "抱歉！由于当前查询用户过多，请3秒后重试或点击蓝色部分进行咨询！！\n\n";
                    $resultStr = $this->transmitText($postObj, $contentStr);
                }
            } else {
                $contentStr = "请输入股票代码！";
                $resultStr = $this->transmitText($postObj, $contentStr);
            }

            echo $resultStr;


        } else {
            echo "";
            exit;
        }
    }


    private function get_access_token()
    {
        $appid = "wxd541bb818bb11d35";
        $appsecret = "xxx";
        $url = "https://sz.api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";

        $output = $this->https_request($url);
        $jsoninfo = json_decode($output, true);
print_r($jsoninfo);
        $access_token = $jsoninfo['access_token'];

        return $access_token;
    }

    private function https_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }


    private function transmitText($object, $content)
    {
        $textTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[text]]></MsgType>
            <Content><![CDATA[%s]]></Content>
         </xml>";
        $resultStr = sprintf($textTpl, $object->FromUserName, $object->ToUserName, $_SERVER['REQUEST_TIME'], $content);
        return $resultStr;
    }

    private function transmitNews($object, $arr_item)
    {

        if (!is_array($arr_item))
            return;

        $itemTpl = "<item>
               <Title><![CDATA[%s]]></Title>
                <Description><![CDATA[%s]]></Description>
                <PicUrl><![CDATA[%s]]></PicUrl>
                <Url><![CDATA[%s]]></Url>
           </item>";
        $item_str = "";
        foreach ($arr_item as $item) {
            $item_str .= sprintf($itemTpl, $item['Title'], $item['Description'], $item['PicUrl'], $item['Url']);
        }
        $newsTpl = "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[news]]></MsgType>
            <ArticleCount>%s</ArticleCount>
            <Articles>$item_str</Articles>
          </xml>";

        $resultStr = sprintf($newsTpl, $object->FromUserName, $object->ToUserName, $_SERVER['REQUEST_TIME'], count($arr_item));

        return $resultStr;
    }


    private static function gather_information($keyword)
    {
        $html = file_get_html('http://doctor.10jqka.com.cn/' . $keyword);

        if ($html) {

            foreach ($html->find('a') as $e)
                $e->outertext = '';
            foreach ($html->find('strong.title') as $e)
                $ret1 = mb_convert_encoding($e->innertext, 'UTF-8', 'GB2312');
            $arr1 = array('%' => '%%');
            $ret1 = strtr($ret1, $arr1);
            foreach ($html->find('p.cnt') as $e)
                $ret2 = mb_convert_encoding($e->innertext, 'UTF-8', 'GB2312');
            $arr2 = array('<strong>' => '', '</strong>' => '', "<span class='date'>" => "", "</span>" => "");
            $ret2 = strtr($ret2, $arr2);

            $html->clear();
        }

        $prefix_key = substr($keyword, 0, 3);
        switch ($prefix_key) {
            case '600':
            case '601':
            case '603':
                $prefix = 'sh';
                break;
            case '300':
            case '000':
            case '002':
                $prefix = 'sz';
                break;
            default:
                break;
        }
        $ret3 = 'http://image.sinajs.cn/newchart/min/n/' . $prefix . $keyword . '.gif';

        $info_array = array($ret1, $ret2, $ret3);

        return $info_array;
    }

} ?>

                                                                                                                                                                                                                                                                                                                                    