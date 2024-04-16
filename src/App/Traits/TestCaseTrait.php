<?php


namespace LaravelDev\App\Traits;


use Illuminate\Support\Facades\Http;

trait TestCaseTrait
{
    protected array $tokens = [];

    /**
     * @intro 发起接口的请求
     * @param string $method
     * @param array|null $params
     * @param array|null $headers
     * @param bool|null $showDetail
     * @return void
     */
    protected function go(string $method, ?array $params = [], ?array $headers = [], ?bool $showDetail = false): void
    {
        $modules = str()->of($method)->replace('Tests\\Modules\\', '')->explode('\\')->toArray();
        $ctrlAndAction = array_pop($modules);
        $modulesName = implode('/', $modules);

        $arr = explode('ControllerTest::test_', $ctrlAndAction);
        $ctrl = $arr[0];
        $action = $arr[1];

        $headers['Authorization'] = 'Bearer ' . str_replace('Bearer ', '', $this->tokens[$modulesName] ?? '');

        $url = env('APP_URL') . '/api/' . $modulesName . '/' . $ctrl . '/' . $action;
        if ($showDetail) dump('请求地址：', $url);
        if ($showDetail) dump('请求参数：', $params);
        $response = Http::withHeaders($headers)->post($url, $params);
        $json = $response->json();
        dump(json_encode($json));
        if ($showDetail) {
            dump("响应结果", $json);
        } else {
            dump($json);
        }
        $this->assertTrue($response->getStatusCode() == 200);
    }
}
