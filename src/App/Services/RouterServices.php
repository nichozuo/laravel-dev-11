<?php

namespace LaravelDev\App\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use LaravelDev\App\Exceptions\Err;
use LaravelDev\App\Helpers\DocBlockReader;
use LaravelDev\App\Middlewares\JsonWrapperMiddleware;
use LaravelDev\App\Models\Router\RouterActionModel;
use LaravelDev\App\Models\Router\RouterControllerModel;
use LaravelDev\App\Models\Router\RouterParamModel;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class RouterServices
{
    /**
     * @return void
     * @throws Err
     * @throws ReflectionException
     */
    public static function Cache(): void
    {
        Cache::store('file')->put('_dev_router', self::ReflectRoutersToModel());
    }

    /**
     * @return RouterControllerModel[]
     * @throws Err
     * @throws ReflectionException
     */
    public static function GetFromCache(): array
    {
        if (App::environment('local')) {
            return self::ReflectRoutersToModel();
        } else {
            return Cache::store('file')->rememberForever('_dev_router', function () {
                logger()->debug('RouterServices::GetFromCache... cache missed');
                return self::ReflectRoutersToModel();
            });
        }
    }

    /**
     * @param string $className
     * @return RouterControllerModel
     * @throws Err
     * @throws ReflectionException
     */
    public static function GetRouterModelByFullClassName(string $className): RouterControllerModel
    {
        $routers = self::GetFromCache();
        $filteredObject = array_filter($routers, function ($router) use ($className) {
            return $router->fullClassName === $className;
        });
        return reset($filteredObject);
    }

    /**
     * @return RouterControllerModel[]
     * @throws Err
     * @throws ReflectionException
     */
    public static function ReflectRoutersToModel(): array
    {
        $files = File::allFiles(app_path('Modules'));
        $routers = [];

        foreach ($files as $file) {
            if ($file->getFilename() == 'BaseController.php')
                continue;

            $controllerModel = new RouterControllerModel();

            $moduleNames = explode('/', $file->getRelativePathname());
            array_pop($moduleNames);

            $namespace = 'App\\Modules\\' . implode('\\', $moduleNames);
            $className = str_replace('.php', '', $file->getFilename());
            $fullClassName = $namespace . '\\' . $className;
            $name = str_replace('Controller.php', '', $file->getFilename());

            $ctrlRef = new ReflectionClass($fullClassName);
            $ctrlDoc = DocBlockReader::parse($ctrlRef->getDocComment());

            $controllerModel->namespace = $namespace;
            $controllerModel->className = $className;
            $controllerModel->fullClassName = $fullClassName;
            $controllerModel->name = $name;
            $controllerModel->moduleNames = $moduleNames;
            $controllerModel->intro = $ctrlDoc['intro'] ?? '';

            foreach ($ctrlRef->getMethods() as $methodRef) {
                if ($methodRef->class != $controllerModel->fullClassName || $methodRef->getModifiers() !== 1 || $methodRef->isConstructor())
                    continue;
                $actionDoc = DocBlockReader::parse($methodRef->getDocComment());
//                if ($methodRef->name === 'show')
//                    dd($actionDoc);
                $action = new RouterActionModel();
                $action->description = $actionDoc['intro'] ?? '';
                $action->uri = $methodRef->getName();
                $action->methods = ($actionDoc['methods'] ?? false) ? explode(',', $actionDoc['methods']) : ['POST'];
                $action->skipAuth = $actionDoc['skipAuth'] ?? false;
                $action->skipWrap = $actionDoc['skipWrap'] ?? false;
                $action->skipInRouter = $actionDoc['skipInRouter'] ?? false;
                $action->requestBody = self::getRequestBody($methodRef);
                $action->responseJson = $actionDoc['responseJson'] ?? null;
                $action->responseBody = $actionDoc['responseBody'] ?? null;
                $action->isDownload = str_contains($actionDoc['return'] ?? false, 'StreamedResponse');

                $controllerModel->actions[] = $action;
            }

            $routers[] = $controllerModel;
        }
        return $routers;
    }

    /**
     * @return void
     * @throws Err
     * @throws ReflectionException
     */
    public static function Register(): void
    {
        foreach (self::GetFromCache() as $api) {
            $arr = [...$api->moduleNames, $api->name];
            Route::prefix(implode('/', $arr))->group(function ($router) use ($api, $arr) {
                foreach ($api->actions as $action) {
                    if ($action->skipInRouter)
                        continue;

                    $middlewares = [];
                    $action->skipAuth ?: $middlewares[] = 'auth:' . $api->moduleNames[0];
                    $action->skipWrap ?: $middlewares[] = JsonWrapperMiddleware::class;

                    $router->match(
                        $action->methods,
                        $action->uri,
                        [$api->fullClassName, $action->uri]
                    )
                        ->name(implode('.', $arr) . ".$action->uri")
                        ->middleware($middlewares);
                }
            });
        }
    }

    /**
     * @param ReflectionMethod $methodRef
     * @return array
     * @throws Err
     */
    private static function getRequestBody(ReflectionMethod $methodRef): array
    {
        // 获得方法的源代码
        $startLine = $methodRef->getStartLine();
        $endLine = $methodRef->getEndLine();
        $length = $endLine - $startLine;
        $source = file($methodRef->getFileName());
        $lines = array_slice($source, $startLine, $length);

        // 解析每一行
        $strStart = ']);';
        $strEnd1 = '$params = $request->validate([';
        $strEnd2 = '$params = request()->validate([';
        $start = $end = false;
        $arr1 = [];
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t == $strStart) $end = true;
            if ($start && !$end)
                $arr1[] = $t;
            if ($t == $strEnd1 || $t == $strEnd2) $start = true;
        }

        // 解析参数
        $arr2 = [];
        foreach ($arr1 as $item) {
            if (Str::startsWith(trim($item), "//"))
                continue;
            $param = new RouterParamModel();
            $item = str_replace('//', '#', $item);
            $t1 = explode('\'', $item);
            if (count($t1) < 3) continue;
            $t2 = explode('|', $t1[3]);
            if (count($t2) < 2)
                ee("参数解析失败：$item");
            $t3 = explode('#', $t1[4]);
            $param->name = str_replace('.*.', '.\*.', $t1[1]);
            $param->required = $t2[0] == 'required';
            $param->type = $t2[1];
            $param->description = (count($t3) > 1) ? trim($t3[1]) : '-';
            $arr2[] = $param;
        }
        return $arr2;
    }
}