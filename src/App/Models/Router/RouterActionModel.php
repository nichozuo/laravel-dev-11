<?php

namespace LaravelDev\App\Models\Router;


class RouterActionModel
{
//    public string $intro;
    /**
     * 请求的路径
     * @var string
     */
    public string $uri;

    /**
     * 请求的方法
     * @var string[]
     */
    public array $methods;

    public string $summary;

    public string $description;

    /**
     * 路由参数，暂时没用到
     * @var RouterParamModel[]
     */
    public array $parameters;

    /**
     * 请求参数
     * @var RouterParamModel[]
     */
    public array $requestBody;

    /**
     * 响应参数
     * @var string|null
     */
    public ?string $responseBody;

    /**
     * 是否废弃
     * @var bool
     */
    public bool $deprecated;

    /**
     * 响应的json字符串
     * @var string|null
     */
    public ?string $responseJson;

    /**
     * @var string[]
     */
    public array $middlewares;

    public bool $skipInRouter;
    public bool $skipWrap;
    public bool $skipAuth;
    public string $return;
    public bool $isDownload;
}
