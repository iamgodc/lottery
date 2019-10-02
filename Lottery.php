<?php

class Lottery extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array());
        $this->load->library(array('parser', 'session'));
        $this->load->helper(array('url'));
        date_default_timezone_set('Asia/Taipei');
    }

    // 爬蟲
    public function spider($lottery)
    {
        // 假設來源是資料庫
        // 範例先使用陣列
        // 所有號源
        // $urls = [
        //     1 => 'http://one.fake/v1?gamekey={gamekey}&issue={issue}',
        //     2 => 'https://two.fake/newly.do?code={code}'
        // ];
        // 號源所需參數
        $url_param = [
            1 => ['gamekey' => 'ssc', 'code' => 'cqssc'],
            2 => ['gamekey' => 'bjsyxw', 'code' => 'bj11x5']
        ];
        // 各遊戲的主要號源
        $main_url_list = [
            1 => ['url' => 'http://one.fake/v1?gamekey={gamekey}&issue={issue}', 'type' => 1],
            2 => ['url' => 'https://two.fake/newly.do?code={code}', 'type' => 2]
        ];
        // 副號源
        $sub_url_db_list = [
            1 => [['url' => 'https://two.fake/newly.do?code={code}', 'type' => 2]],
            2 => [['url' => 'http://one.fake/v1?gamekey={gamekey}&issue={issue}', 'type' => 1]]
        ];
        $sub_url_list = $sub_url_db_list[$lottery->gameId];

        // 主號源網址處理
        $main_url = '';
        switch ($lottery->gameId) {
            case 1:
                $main_url = $main_url_list[$lottery->gameId]['url'];
                $url = str_replace('{gamekey}', $url_param[$lottery->gameId]['gamekey'], $main_url);
                $url = str_replace('{issue}', $lottery->issue, $url);
                break;
            case 2:
                $main_url = $main_url_list[$lottery->gameId]['url'];
                $url = str_replace('{code}', $url_param[$lottery->gameId]['code'], $main_url);
                break;
        }

        $UserAgent = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506; .NET CLR 3.5.21022; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $UserAgent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $output = curl_exec($ch);
        curl_close($ch);

        $tmp = json_decode($output);

        $data = [];
        // 根據主號源格式決定處理方式
        switch ($main_url_list[$lottery->gameId]['type']) {
            case 1:
                $data['period'] = $tmp->result->data->gid;
                $data['num'] = $tmp->result->data->award;
                break;
            case 2:
                $data['period'] = $tmp->data[0]->expect;
                $data['num'] = $tmp->data[0]->opencode;
                break;
        }



        // 副號源處理
        $sub_url = [];
        foreach ($sub_url_list as $key => $value) {
            switch ($value['type']) {
                case 1:
                    $sub_url = str_replace('{gamekey}', $url_param[$lottery->gameId]['gamekey'], $value['url']);
                    $sub_url = str_replace('{issue}', $url_param[$lottery->gameId]['issue'], $sub_url);

                    $tmp = call_curl($sub_url);

                    if ($data['period'] != $tmp->result->data->gid) {
                        return false;
                    }

                    break;
                case 2:
                    $sub_url = str_replace('{code}', $url_param[$lottery->gameId]['code'], $value['url']);

                    $tmp = call_curl($sub_url);

                    if ($data['period'] != $tmp->data[0]->expect) {
                        return false;
                    }

                    break;
            }



            // $sub_url[$key] = $tmp;
        }

        return $data;
        // print_r($data);
    }

    public function call_curl($url)
    {
        // 副源號抓取
        $UserAgent = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506; .NET CLR 3.5.21022; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $UserAgent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $output = curl_exec($ch);
        $tmp = json_decode($output);
        curl_close($ch);

        return $tmp;
    }
}
